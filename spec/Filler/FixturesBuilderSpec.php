<?php

namespace spec\Filler;

use Exception;
use Filler\DependencyManager;
use Filler\DependencyResolver;
use Filler\Persistor\PersistorInterface;
use ObjectKey;
use PhpSpec\ObjectBehavior;
use PropelPDO;
use Prophecy\Argument;

trait BaseObject
{
    public function getPrimaryKey() {}
    public function setPrimaryKey($primaryKey) {}
    public function delete(PropelPDO $con = null) {}
    public function save(PropelPDO $con = null) {}
}

class Person extends \BaseObject implements \Persistent
{
    use BaseObject;

    public function setName($name) {}
    public function setFavouriteAnimal(Animal $b) {}
}

class Animal extends \BaseObject implements \Persistent
{
    use BaseObject;

    public function setType($type) {}
}

class FixturesBuilderSpec extends ObjectBehavior
{
    function let(PersistorInterface $persistor, DependencyManager $dependencyManager)
    {
        $this->beConstructedWith($persistor, $dependencyManager);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Filler\FixturesBuilder');
    }

    function it_informs_persistor_before_building_first_fixture($persistor)
    {
        $persistor->preLoad()->shouldBeCalled();
        $this->preLoad();
    }

    function it_informs_persistor_after_building_last_fixture($persistor)
    {
        $persistor->postLoad()->shouldBeCalled();
        $this->postLoad();
    }

    function it_informs_persistor_if_an_error_occurs($persistor)
    {
        $persistor->abortLoad()->shouldBeCalled();
        $this->abortLoad();
    }

    function it_stores_class_name_when_build_called()
    {
        $this->build('spec\Filler\Person')->shouldReturn($this);
        $this->getClass()->shouldBe('spec\Filler\Person');
        $this->getInstance()->shouldBe(null);
    }

    function it_stores_object_when_build_called_with_object_instance(Person $person)
    {
        $this->build($person)->shouldReturn($this);
        $this->getClass()->shouldBeAnInstanceOf('spec\Filler\Person');
        $this->getInstance()->shouldBe(null);
    }

    function it_creates_instance_when_add_called_after_build()
    {
        $this->build('spec\Filler\Person')->shouldReturn($this);
        $this->add()->shouldReturn($this);
        $this->getInstance()->shouldBeAnInstanceOf('spec\Filler\Person');
    }

    function it_throws_exception_if_add_called_without_build()
    {
        $this->shouldThrow('RuntimeException')->duringAdd();
    }

    function it_sets_object_values_after_adding_new_instance(Person $person)
    {
        $person->setName('Barry')->shouldBeCalled();

        $this
            ->build($person)
                ->add()
                    ->name('Barry')->shouldReturn($this);
    }

    function it_saves_current_instance_when_end_is_called(Person $person, $persistor)
    {
        $person->setName('Barry')->shouldBeCalled();
        $persistor->persist($person)->shouldBeCalled();

        $this
            ->build($person)
                ->add()
                    ->name('Barry')
                ->end()->shouldReturn($this);
    }

    function it_saves_current_instance_when_a_new_instance_is_added(Person $person, $persistor)
    {
        $person->setName('Barry')->shouldBeCalled();
        $person->setName('Jane')->shouldBeCalled();
        $persistor->persist($person)->shouldBeCalledTimes(1);

        $this
            ->build($person)
                ->add()
                    ->name('Barry')
                ->add()
                    ->name('Jane');
    }

    function it_saves_current_instance_when_a_new_class_is_built(Person $barry, Person $jane, $persistor)
    {
        $barry->setName('Barry')->shouldBeCalled();
        $persistor->persist($barry)->shouldBeCalled();
        $persistor->persist($jane)->shouldNotBeCalled();

        $this
            ->build($barry)
                ->add()
                    ->name('Barry')
            ->build($jane);
    }

    function it_stores_reference_to_instance_when_key_specified_manually(Person $barry, $dependencyManager)
    {
        $dependencyManager->set(Argument::type('spec\Filler\Person'), 'barry')->shouldBeCalled();
        $this->build($barry)->add('barry')->name('Barry')->end();
    }

    function it_does_not_build_fixtures_when_dependency_not_met(Person $person, $dependencyManager, DependencyResolver $resolver, $persistor)
    {
        $persistor->persist($person)->shouldNotBeCalled();

        $dependencyManager->createResolver($this->object, Argument::type('Closure'))->willReturn($resolver);
        $dependencyManager->createReference(Argument::which('getShortName', 'Animal'), 'monkey')->willReturn('Animal:monkey');
        $resolver->depends('Animal:monkey')->shouldBeCalled();
        $dependencyManager->addResolver(Argument::type('Filler\DependencyResolver'))->shouldBeCalled();

        $this->depends(function($b, Animal $monkey) use ($person) {
            $b->build($person)->add()->favouriteAnimal($monkey)->end();
        });
    }

    function it_notifies_dependency_manager_when_object_with_reference_is_created(Person $person, $dependencyManager, DependencyResolver $resolver)
    {
        $dependencyManager->createResolver($this->object, Argument::type('Closure'))->willReturn($resolver);
        $dependencyManager->createReference(Argument::which('getShortName', 'Animal'), 'monkey')->willReturn('Animal:monkey');
        $resolver->depends('Animal:monkey')->shouldBeCalled();
        $dependencyManager->addResolver(Argument::type('Filler\DependencyResolver'))->shouldBeCalled();

        $this->depends(function($b, Animal $monkey) use ($person) {
            $b->build($person)->add()->favouriteAnimal($monkey)->end();
        });

        $dependencyManager->set(Argument::type('spec\Filler\Animal'), 'monkey')->shouldBeCalled();
        $this->build('spec\Filler\Animal')->add('monkey')->type('monkey')->end();
    }
}
