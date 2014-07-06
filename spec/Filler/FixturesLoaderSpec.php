<?php

namespace spec\Filler;

use Filler\Fixture;
use Filler\FixturesBuilder;
use Filler\FixturesLoader;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Finder\Finder;

class EmptyFixturesLoader extends FixturesLoader
{
    protected function getFixtures()
    {
        return array();
    }
}

class FixturesLoaderSpec extends ObjectBehavior
{
    function let(FixturesBuilder $builder)
    {
        $this->beConstructedWith($builder, null);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Filler\FixturesLoader');
    }

    function it_loads_configuration()
    {
        $this->getConfig()->shouldReturn([
            'filler' => [
                'fixtures_path' => 'fixtures'
            ]
        ]);
    }

    function it_calls_pre_and_post_load_methods_on_builder($builder)
    {
        $this->beAnInstanceOf('spec\Filler\EmptyFixturesLoader');
        $this->beConstructedWith($builder);

        $builder->preLoad()->shouldBeCalledTimes(1);
        $builder->postLoad()->shouldBeCalledTimes(1);

        $this->load();
    }
}
