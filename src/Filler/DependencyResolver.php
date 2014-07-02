<?php

namespace Filler;

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

    // TODO handle() method

    /**
     * @param $reference
     * @param $object
     */
    public function resolve($reference, $object)
    {
        // TODO check reference is a dependency

        $this->dependencies[$reference] = $object;
        $this->evaluate();
    }

    public function isResolved()
    {
        return !in_array(null, $this->dependencies);
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
