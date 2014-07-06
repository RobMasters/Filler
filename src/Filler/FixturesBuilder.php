<?php

namespace Filler;

use Filler\Exception\FixtureBuildingException;
use Filler\Persistor\PersistorInterface;

/**
 * Class that is provided to all fixture classes to facilitate all interactions regarding creating fixture data
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
class FixturesBuilder
{
    /**
     * @var Persistor\PersistorInterface
     */
    private $persistor;

    /**
     * @var DependencyManager
     */
    private $dependencyManager;

    /**
     * @var string|mixed
     */
    private $class;

    /**
     * @var mixed
     */
    private $instance;

    /**
     * @var string
     */
    private $instanceKey;

    /**
     * @param PersistorInterface $persistor
     * @param DependencyManager $dependencyManager
     */
    function __construct(PersistorInterface $persistor, DependencyManager $dependencyManager)
    {
        $this->dependencyManager = $dependencyManager;
        $this->persistor = $persistor;
    }

    /**
     * Begin building using a given class or class name
     *
     * @param $class
     * @return $this
     */
    public function build($class)
    {
        if ($this->instance) {
            return $this->end()->build($class);
        }

        $this->class = $class;

        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @return mixed
     */
    public function getInstanceKey()
    {
        return $this->instanceKey;
    }

    /**
     *
     */
    public function preLoad()
    {
        $this->persistor->preLoad();
    }

    /**
     *
     */
    public function postLoad()
    {
        $this->persistor->postLoad();
    }

    /**
     *
     */
    public function abortLoad()
    {
        $this->persistor->abortLoad();
    }

    /**
     * @param string $instanceKey
     * @throws \RuntimeException
     * @return $this
     */
    public function add($instanceKey = '')
    {
        if (empty($this->class)) {
            throw new \RuntimeException('Cannot add a new record - there is no current class');
        }

        if ($this->instance) {
            return $this->end()->add();
        }

        $this->instanceKey = $instanceKey;
        $this->instance = is_string($this->class) ? new $this->class : clone $this->class;

        return $this;
    }

    /**
     * @throws FixtureBuildingException
     * @return $this
     */
    public function end()
    {
        if (empty($this->class)) {
            throw new FixtureBuildingException('Failed to end fixture building - nothing is currently being built');
        }

        if ($this->getInstance()) {
            $this->persistInstance();
            $this->instance = null;
        }

        return $this;
    }

    /**
     * @param callable $closure
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function depends(\Closure $closure)
    {
        $reflection = new \ReflectionFunction($closure);
        $arguments = $reflection->getParameters();

        $firstArgument = array_shift($arguments);
        if ($firstArgument->getClass() && $firstArgument->getClass()->getShortName() !== 'FixturesBuilder') {
            throw new \InvalidArgumentException('First argument must be an instance of the fixtures builder');
        }

        $dependencyResolver = $this->dependencyManager->createResolver($this, $closure);
        foreach ($arguments as $argument) {
            $argumentClass = $argument->getClass();
            if (!$argumentClass) {
                throw new \InvalidArgumentException('Dependencies must be typehinted');
            }

            $reference = $this->dependencyManager->createReference($argumentClass, $argument->getName());
            $dependencyResolver->depends($reference);
        }
        $this->dependencyManager->addResolver($dependencyResolver);

        return $this;
    }

    /**
     *
     */
    protected function persistInstance()
    {
        $this->persistor->persist($this->instance);
        if ($key = $this->getInstanceKey()) {
            $this->dependencyManager->set($key, $this->instance);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     */
    function __call($name, $arguments)
    {
        $setter = 'set' . ucfirst($name);
        call_user_func_array(array($this->instance, $setter), $arguments);

        return $this;
    }
}
