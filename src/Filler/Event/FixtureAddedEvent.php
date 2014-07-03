<?php

namespace Filler\Event;

use Symfony\Component\EventDispatcher\Event;

class FixtureAddedEvent extends Event
{
    /**
     * @var
     */
    private $fixture;

    /**
     * @param mixed $fixture The persisted fixture
     */
    function __construct($fixture)
    {
        $this->fixture = $fixture;
    }

    /**
     * @return mixed
     */
    public function getFixture()
    {
        return $this->fixture;
    }
}