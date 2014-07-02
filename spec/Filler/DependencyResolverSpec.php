<?php

namespace spec\Filler;

use Filler\FixturesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ClosureInspector
{
    protected $executed = false;

    /**
     *
     */
    public function execute()
    {
        $this->executed = true;
    }

    /**
     * @return bool
     */
    public function isExecuted()
    {
        return $this->executed;
    }
}

class DependencyResolverSpec extends ObjectBehavior
{
    protected $inspector;

    function let(FixturesBuilder $builder)
    {
        $this->inspector = $inspector = new ClosureInspector();
        $this->beConstructedWith($builder, function() use ($inspector) { $inspector->execute(); });
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Filler\DependencyResolver');
    }

    function it_stores_an_array_of_dependency_references()
    {
        $this->depends('User:george');
        $this->getDependencies()->shouldReturn(['User:george']);
    }

    function it_is_not_resolved_when_no_dependencies_have_not_been_provided()
    {
        $this->depends('User:george');
        $this->isResolved()->shouldReturn(false);
    }

    function it_is_not_resolved_when_some_dependencies_have_been_provided()
    {
        $this->depends('User:george');
        $this->resolve('User:george', new \stdClass());
        $this->depends('Animal:cat');
        $this->isResolved()->shouldReturn(false);
    }

    function it_can_be_resolved_by_providing_objects_for_all_dependencies()
    {
        $this->depends('User:george');
        $this->resolve('User:george', new \stdClass());
        $this->isResolved()->shouldReturn(true);
    }
}
