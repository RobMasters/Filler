<?php

namespace Filler\Persistor;

/**
 * Interface for a fixture persistor
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
interface PersistorInterface
{
    /**
     * Set up persistor before loading fixtures
     */
    public function preLoad();

    /**
     * Finish persisting after loading all fixtures
     */
    public function postLoad();

    /**
     * Abort persistance when an error occurs
     */
    public function abortLoad();

    /**
     * @param \Persistent $object
     * @return mixed
     */
    public function persist(\Persistent $object);
}