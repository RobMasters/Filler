<?php

namespace spec\Filler;

use Filler\FixturesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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
                'fixtures_path' => 'app/fixtures'
            ]
        ]);
    }

    function it_calls_pre_and_post_load_methods_on_builder($builder)
    {
        $builder->preLoad()->shouldBeCalledTimes(1);
        $builder->postLoad()->shouldBeCalledTimes(1);

        $this->load();
    }
}
