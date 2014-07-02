<?php

namespace Filler\Persistor;

/**
 * Fixture persistor for Propel1 classes
 *
 * @author Rob Masters <robmasters87@gmail.com>
 */
class PropelPersistor implements PersistorInterface
{
    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @param \PropelPDO $connection
     */
    public function __construct(\PropelPDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Start a transaction before loading fixtures
     */
    public function preLoad()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transaction after loading all fixtures
     */
    public function postLoad()
    {
        $this->connection->commit();
    }

    /**
     * Roll back transaction if an error occurred
     */
    public function abortLoad()
    {
        $this->connection->rollBack();
    }

    /**
     * @param \Persistent $object
     * @return mixed|void
     */
    public function persist(\Persistent $object)
    {
        $object->save($this->connection);
    }
}
