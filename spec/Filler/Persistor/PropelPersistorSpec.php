<?php

namespace spec\Filler\Persistor;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PropelPersistorSpec extends ObjectBehavior
{
    function let(\PropelPDO $connection)
    {
        $this->beConstructedWith($connection, null);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Filler\Persistor\PropelPersistor');
    }

    function it_begins_transaction_before_loading_fixtures($connection)
    {
        $connection->beginTransaction()->shouldBeCalled();
        $this->preLoad();
    }

    function it_commits_transaction_after_loading_fixtures($connection)
    {
        $connection->commit()->shouldBeCalled();
        $this->postLoad();
    }

    function it_rolls_back_transaction_when_load_aborted($connection)
    {
        $connection->rollBack()->shouldBeCalled();
        $this->abortLoad();
    }

    function it_can_save_propel_objects(\Persistent $object, $connection)
    {
        $object->save($connection)->shouldBeCalled();
        $this->persist($object);
    }
}
