<?php

namespace Alchemy\orm;
use Alchemy\core\query\Query;


/**
 * Controller for performing DDL operations on the database
 */
class DDL {
    private $session;

    /**
     * Object constructor
     *
     * @param Session $session
     */
    public function __construct(Session $session) {
        $this->session = $session;
    }


    /**
     * CREATE the table for the given DataMapper class
     *
     * @param string $cls Class Name of DataMapper child
     */
    public function create($cls) {
        $create = Query::Create($cls::schema());
        $this->session->engine()->query($create);
    }


    /**
     * Find all subclasses of DataMapper and run {@see DDL::create()} on each
     * of them.
     */
    public function createAll() {
        $mappers = DataMapper::list_mappers();
        $created = array();

        while (count($mappers) > 0) {
            $mapper = array_pop($mappers);
            $table = $mapper::schema();
            $dependancies = $table->listDependancies();
            $dependancies = array_diff($dependancies, $created);

            if (count($dependancies) > 0) {
                array_unshift($mappers, $mapper);
            } else {
                $this->create($mapper);
                $created[] = $table->getName();
            }
        }
    }


    /**
     * DROP the table for the given DataMapper class
     */
    public function drop($cls) {
        $drop = Query::Drop($cls::schema());
        $this->session->engine()->query($drop);
    }


    /**
     * Find all subclasses of DataMapper and run {DDL::drop()} on each
     * of them.
     */
    public function dropAll() {
        $mappers = DataMapper::list_mappers();
        $dropped = array();

        while (count($mappers) > 0) {
            $mapper = array_pop($mappers);
            $table = $mapper::schema();
            $dependants = $table->listDependants();
            $dependants = array_diff($dependants, $dropped);

            if (count($dependants) > 0) {
                array_unshift($mappers, $mapper);
            } else {
                $this->drop($mapper);
                $dropped[] = $table->getName();
            }
        }
    }
}
