<?php

namespace Alchemy\expression;
use Alchemy\util\Monad;


/**
 * Abstract base class for representing a query
 */
abstract class Query implements IQuery {
    protected $columns = array();
    protected $joins = array();
    protected $where;


    /**
     * Returns an instance of the called query type wrapped
     * in an Monad
     *
     * @return Monad(Query)
     */
    public static function init() {
        $cls = get_called_class();
        return new Monad(new $cls());
    }


    /**
     * Add a column to the query
     *
     * @param Value $column
     */
    public function column(Value $column) {
       $this->columns[] = $column;
    }


    /**
     * Add multiple columns to the query by providing
     * multiple arguments. See {@link Query::column()}
     */
    public function columns() {
        $columns = func_get_args();
        $columns = is_array($columns[0]) ? $columns[0] : $columns;

        foreach ($columns as $column) {
            $this->column($column);
        }
    }


    /**
     * @see IQuery::getParameters()
     */
    public function getParameters() {
        return array();
    }


    /**
     * Add a join to the query
     *
     * @param Table $table
     * @param Expression $on
     * @param $direction Optional join direction
     * @param $type Optional join type
     */
    public function join(Table $table, Expression $on, $direction = null, $type = null) {
        $direction = $direction ?: Join::LEFT;
        $type = $type ?: Join::INNER;
        $this->joins[] = new Join($direction, $type, $table, $on);
    }


    /**
     * Shortcut for doing an OUTER JOIN
     *
     * @param Table $table
     * @param Expression $on
     * @param $direction Optional join direction
     */
    public function outerJoin(Table $table, Expression $on, $direction = null) {
        return $this->join($table, $on, $direction, Join::OUTER);
    }


    /**
     * Set the Query's WHERE expression. Calling this
     * multiple times will overwrite the previous expressions.
     * You should instead call this once with a CompoundExpression.
     *
     * @param Expression $expr
     */
    public function where(Expression $expr) {
       $this->where = $expr;
    }
}
