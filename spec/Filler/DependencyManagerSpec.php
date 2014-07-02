<?php

namespace spec\Filler;

use Filler\DependencyResolver;
use Filler\FixturesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Car {}

class DependencyManagerSpec extends ObjectBehavior
{
    function let(EventDispatcher $dispatcher)
    {
        $this->beConstructedWith($dispatcher, null);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Filler\DependencyManager');
    }

    function it_creates_reference_from_instance_and_given_key()
    {
        $car = new Car();
        $this->createReference($car, 'kitt')->shouldReturn('Car:kitt');
    }

    function it_creates_reference_from_reflection_class_and_given_key(\ReflectionClass $reflectionClass)
    {
        $reflectionClass->getShortName()->willReturn('User');
        $this->createReference($reflectionClass, 'george')->shouldReturn('User:george');
    }

    function it_creates_dependency_resolvers(FixturesBuilder $builder)
    {
        $this->createResolver($builder, function() {})->shouldBeAnInstanceOf('Filler\DependencyResolver');
    }

    function it_does_not_store_resolvers_that_can_be_resolved_from_cache(DependencyResolver $resolver, $dispatcher)
    {
        $car = new Car();
        $this->set($car, 'kitt');

        $resolver->resolve('Car:kitt', $car)->shouldBeCalled();
        $resolver->getDependencies()->willReturn(['Car:kitt']);
        $resolver->isResolved()->willReturn(true);

        $dispatcher->addListener(Argument::cetera())->shouldNotBeCalled();

        $this->addResolver($resolver);
        $this->getResolvers()->shouldBe([]);
    }

    function it_creates_listener_when_adding_resolver_with_unmet_dependencies(DependencyResolver $resolver, $dispatcher)
    {
        $resolver->getDependencies()->willReturn(['Car:kitt']);
        $resolver->isResolved()->willReturn(false);

        $dispatcher->addListener(Argument::type('string'), Argument::type('callable'))->shouldBeCalled();

        $this->addResolver($resolver);
        $this->getResolvers()->shouldBe([$resolver]);
    }
}
