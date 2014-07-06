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
     * @return array
     */
    public function getDependencies()
    {
        return array_keys($this->dependencies);
    }

    /**
     * @param $reference
     * @param $object
     * @throws Exception\FixtureBuildingException
     */
    public function resolve($reference, $object)
    {
        if (!array_key_exists($reference, $this->dependencies)) {
            throw new FixtureBuildingException(sprintf('Provided reference, `%s`, is not a dependency', $reference));
        }

        $this->dependencies[$reference] = $object;
        $this->evaluate();
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
     *
     */
    protected function evaluate()
    {
        if ($this->isResolved()) {
            $parameters = array_values($this->dependencies);
            array_unshift($parameters, $this->builder);
            call_user_func_array($this->closure, $parameters);
        }
    }
}
