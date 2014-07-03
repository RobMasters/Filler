<?php

namespace Filler;

use Filler\Event\FixtureAddedEvent;
use Filler\Event\FixtureEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class to manage resolving fixture dependencies
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
class DependencyManager
{
    /**
     * @var array
     */
    protected $cache = array();

    /**
     * @var array|DependencyResolver[]
     */
    protected $resolvers = array();

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param mixed $object Instance of ReflectionClass or an instance of the object itself
     * @param string $name
     * @return string
     */
    public function createReference($object, $name)
    {
        $reflectionClass = ($object instanceof \ReflectionClass) ? $object : new \ReflectionClass($object);

        return sprintf('%s:%s', $reflectionClass->getShortName(), $name);
    }

    /**
     * @param FixturesBuilder $builder
     * @param callable $closure
     * @return DependencyResolver
     */
    public function createResolver(FixturesBuilder $builder, \Closure $closure)
    {
        return new DependencyResolver($builder, $closure);
    }

    /**
     * @param $reference
     * @param $object
     */
    public function set($object, $reference)
    {
        if (!preg_match('/^[a-z0-9]+:[a-z0-9_-]+$/', $reference)) {
            $reference = $this->createReference($object, $reference);
        }

        $this->cache[$reference] = $object;

        $event = new FixtureAddedEvent($object);
        $this->dispatcher->dispatch(sprintf(FixtureEvents::RESOLVE_DEPENDENCY_PATTERN, $reference), $event);
    }

    /**
     * @param DependencyResolver $resolver
     */
    public function addResolver(DependencyResolver $resolver)
    {
        $dependencies = $resolver->getDependencies();
        foreach ($dependencies as $reference) {
            if (array_key_exists($reference, $this->cache)) {
                $resolver->resolve($reference, $this->cache[$reference]);
            } else {
                // Set up an event listener to notify the resolver when the dependency is provided
                $this->dispatcher->addListener(sprintf(FixtureEvents::RESOLVE_DEPENDENCY_PATTERN, $reference), array($resolver, 'handle'));
            }
        }

        // Only store resolvers that have outstanding dependencies so we can check if everything
        // was resolved at a later date
        if (!$resolver->isResolved()) {
            $this->resolvers[] = $resolver;
        }
    }

    /**
     * @return array|DependencyResolver[]
     */
    public function getResolvers()
    {
        return $this->resolvers;
    }
}
