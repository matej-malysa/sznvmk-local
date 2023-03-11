<?php

namespace App\Model;

use Dibi\Connection;
use Nette\Schema\Processor;
use Nette\Schema\Schema;

abstract class BaseModel
{
    /** @var Connection */
    protected Connection $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    /**
     * @param Schema $schema
     * @param iterable $data
     * @return mixed
     */
    public function validate(Schema $schema, iterable $data)
    {
        $processor = new Processor();
        return $processor->process($schema, $data);
    }
}
