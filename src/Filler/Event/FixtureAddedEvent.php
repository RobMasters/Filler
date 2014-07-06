<?php

namespace Filler\Event;

use Symfony\Component\EventDispatcher\Event;

class FixtureAddedEvent extends Event
{
    /**
     * @var string
     */
    private $reference;

    /**
     * @var mixed
     */
    private $object;

    /**
     * @param $reference
     * @param mixed $object The persisted fixture object
     */
    function __construct($reference, $object)
    {
        $this->reference = $reference;
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }
}