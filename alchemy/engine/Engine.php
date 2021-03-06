<?php

namespace Alchemy\engine;
use Alchemy\core\query\IQuery;
use Alchemy\dialect\Compiler;
use Alchemy\util\Monad;
use PDO;


/**
 * Basic Engine implementation using PDO as it's DBAPI layer
 */
class Engine implements IEngine {
    protected $compiler;
    protected $connector;
    protected $echoQueries = false;
    protected $pendingTransaction = false;


    /**
     * Object constructor. Opens a connection to the database using PDO
     *
     * @param string $dsn See PDO documentation for DSN reference
     * @param string $username
     * @param string $password
     */
    public function __construct(Compiler $compiler, $dsn, $username = '', $password = '') {
        // Get connection
        $this->connector = new PDO($dsn, $username, $password);
        $this->connector->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->compiler = $compiler;
    }


    /**
     * Start an atomic transaction on the database. These should
     * generally not be held open very long in order to prevent
     * deadlocks
     */
    public function beginTransaction() {
        if (!$this->pendingTransaction) {
            $this->connector->beginTransaction();
            $this->pendingTransaction = true;
        }
    }


    /**
     * Commit a transaction as complete
     */
    public function commitTransaction() {
        if ($this->pendingTransaction) {
            $this->connector->commit();
            $this->pendingTransaction = false;
        }
    }


    /**
     * Get the PDO bind-parameter type (default PARAM_STR) for an object
     *
     * @param  object $obj
     * @return integer
     */
    protected function getBindParamType($obj) {
        static $map = array(
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'null'    => PDO::PARAM_NULL);

        $type = $obj->getTag('expr.value');
        return array_key_exists($type, $map) ? $map[$type] : PDO::PARAM_STR;
    }


    /**
     * Log a SQL statement if echo is enabled
     *
     * @param string $sql
     */
    protected function echoQuery($sql, $params) {
        if (!$this->echoQueries) {
            return;
        }

        if (is_callable($this->echoQueries)) {
            return $this->echoQueries($sql, $params);
        }

        $sql = preg_replace_callback("/:([\w\d]+)/sui", function($match) use ($params) {
            $v = $params[$match[1]];
            if (is_null($v)) {
                return 'NULL';
            }

            if (is_numeric($v)) {
                return $v;
            }

            if (mb_strlen($v) > 13) {
               $v = mb_substr($v, 0, 10) . '...';
            }
            return "'{$v}'";
        }, $sql);

        echo $sql . "\n";
    }


    /**
     * Compile and run a SQL expression on the database
     *
     * @param IQuery Query to compile
     * @return ResultSet
     */
    public function query(IQuery $query) {
        $sql = $this->compiler->compile($query);
        $sql = is_array($sql) ? $sql : array($sql);
        $params = $query->parameters();

        // Because of the limitations of some RDBMS' (*cough*SQLite)
        // some operations can not be performed in a single query.
        // When this is the case, the SQL compiler returns an array
        // of queries. Execute each of them and return the result set
        // of the last one. This behavior is fine because it only currently
        // applies to DDL operations, which don't have a useful return value
        // anyway
        $result = null;
        foreach ($sql as $q) {
            $result = $this->execute($q, $params);
        }

        return $result;
    }


    /**
     * Execute raw SQL on the database connection
     *
     * @param string $sql Statement string
     * @param array $params Params to bind to statement
     * @return ResultSet
     */
    public function execute($sql, $params = array()) {
        try {
            $statement = $this->connector->prepare($sql);
        } catch (Exception $e) {
            throw new Exception("Could not prepare query: " . $sql);
        }

        $paramLog = array();
        foreach ($params as $param) {
            $type = $this->getBindParamType($param);
            $alias = $this->compiler->alias($param);
            $value = $param->getValue();
            $paramLog[$alias] = $value;
            $statement->bindValue($alias, $value, $type);
        }

        $this->echoQuery($sql, $paramLog);

        $statement->execute();
        return new ResultSet($this->connector, $statement);
    }


    /**
     * Revert a pending transaction on the database
     */
    public function rollbackTransaction() {
        if ($this->pendingTransaction) {
            $this->connector->rollBack();
        }
    }


    /**
     * Optionally enable echo'ing of SQL run on the RDBMS.
     *
     * @param mixed $echoQueries False to disable. True to log to STDOUT. Callable to enable custom logging
     */
    public function setEcho($echoQueries) {
        $this->echoQueries = $echoQueries;
    }
}
