<?php

namespace Filler;
use Filler\Event\FixtureAddedEvent;
use Filler\Exception\FixtureBuildingException;

/**
 * Class to handle dependencies for a given closure
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
class DependencyResolver
{
    /**
     * @var array
     */
    protected $dependencies = array();
    /**
     * @var FixturesBuilder
     */
    private $builder;
    /**
     * @var \Closure
     */
    private $closure;

    /**
     * @param FixturesBuilder $builder
     * @param callable $closure
     */
    function __construct(FixturesBuilder $builder, \Closure $closure)
    {
        $this->builder = $builder;
        $this->closure = $closure;
    }

    /**
     * @param string $reference
     */
    public function depends($reference)
    {
        $this->dependencies[$reference] = null;
    }

    /**
     * @param bool $ignoreResolved
     * @return array
     */
    public function getDependencies($ignoreResolved = false)
    {
        $dependencies = $ignoreResolved
            ? array_filter($this->dependencies, function($value) { return is_null($value); })
            : $this->dependencies;

        return array_keys($dependencies);
    }

    /**
     * @param string $reference
     * @param mixed $object
     * @throws Exception\FixtureBuildingException
     */
    public function resolve($reference, $object)
    {
        if (!array_key_exists($reference, $this->dependencies)) {
            throw new FixtureBuildingException(sprintf('Provided reference, `%s`, is not a dependency', $reference));
        }

        $this->dependencies[$reference] = $object;
        if ($this->isResolved()) {
            $this->execute();
        }
    }

    /**
     * @return bool
     */
    public function isResolved()
    {
        return !in_array(null, $this->dependencies);
    }

    /**
     * @param FixtureAddedEvent $event
     */
    public function handle(FixtureAddedEvent $event)
    {
        $this->resolve($event->getReference(), $event->getObject());
    }

    /**
     * @throws Exception\FixtureBuildingException
     */
    public function execute()
    {
        if (!$this->isResolved()) {
            throw new FixtureBuildingException('Cannot execute unresolved dependency resolver');
        }

        $parameters = array_values($this->dependencies);
        array_unshift($parameters, clone $this->builder);
        call_user_func_array($this->closure, $parameters);
        $this->executed = true;
    }
}
