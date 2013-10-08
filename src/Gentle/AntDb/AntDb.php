<?php

/**
 * This file is part of the AntDb package.
 *
 * (c) Alexandru G. <alex@gentle.ro>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gentle\AntDb;

use PDO;
use PDOException;
use PDOStatement;

/**
 * AntDb
 *
 * Thin PDO wrapper with a simple mission: make PDO accessible enough for PHP beginners,
 * that they will give up using `mysql_` extension.
 *
 * @author  Alexandru G.    <alex@gentle.ro>
 */
class AntDb
{
    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var array
     */
    protected $config = array(
        'host' => null,
        'user' => null,
        'pass' => null,
        'name' => null,
        'options' => array(
            'autoconnect' => true,
            'fetch.style' => PDO::FETCH_OBJ
        )
    );

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @param array $config
     * @param PDO   $connection
     */
    public function __construct(array $config, PDO $connection = null)
    {
        $this->config   = array_merge($this->config, $config);
        $this->pdo      = !is_null($connection)
            ? $connection
            : ($this->getOption('autoconnect') ? $this->connect() : null);
    }

    /**
     * Check if any error occurred.
     *
     * @access public
     * @return bool
     */
    public function hasError()
    {
        return (count($this->errors) > 0);
    }

    /**
     * Get last occurred error
     *
     * @access public
     * @return string|null
     */
    public function getLastError()
    {
        return ($this->hasError()) ? array_pop($this->errors) : null;
    }

    /**
     * Get all occurred errors
     *
     * @access public
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Open connection
     *
     * @access public
     * @param  \PDO     $connection (Optional) Existing PDO instance
     * @return PDO|bool False on failure
     */
    public function connect(PDO $connection = null)
    {
        if ($this->isConnected()) {
            return $this->pdo;
        }

        return $this->pdo = (!is_null($connection)) ? $connection : $this->factory($this->config);
    }

    /**
     * Close connection
     *
     * @return void
     */
    public function disconnect()
    {
        $this->pdo = null;
    }

    /**
     * Check if we are connected
     *
     * @access public
     * @return bool
     */
    public function isConnected()
    {
        return ($this->pdo instanceof PDO);
    }

    /**
     * Get config option value
     *
     * @access public
     * @param  string $name
     * @return mixed
     */
    public function getOption($name)
    {
        return isset($this->config['options'][$name]) ? $this->config['options'][$name] : null;
    }

    /**
     * @return PDO|bool
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Clean everything
     *
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Change fetch mode
     *
     * @access public
     * @param  int  $mode
     * @return self
     *
     * @see \PDO::FETCH_
     */
    public function setFetchMode($mode)
    {
        $this->config['options']['fetch.style'] = $mode;

        return $this;
    }

    /**
     * Execute an update statement on a table
     *
     * <code>
     * ->update('users',
     *      array('password' => 'secret'),
     *      array('id' => 1),
     *      array('password' => \PDO::PARAM_STR, 'id' => \PDO::PARAM_INT)
     * );
     * </code>
     *
     * @access public
     * @param  string   $table Table name
     * @param  array    $data  An associative array with column - value pairs
     * @param  array    $where An associative array with column - value pairs.
     * @param  array    $types An associative array with column - value type pairs of merged $data and $where
     * @return int|bool Number of affected rows or false on failure
     */
    public function update($table, array $data, array $where, array $types = array())
    {
        $sql = sprintf('UPDATE %s SET %s WHERE %s',
            $table,
            implode(' = ?, ', array_keys($data)) .' = ?',
            implode(' = ? AND ', array_keys($where)) .' = ?'
        );

        $params = array_merge(array_values($data), array_values($where));

        return $this->execWriteQuery($sql, $params, $types);
    }

    /**
     * Execute an insert statement on a table
     *
     * <code>
     * ->insert('users',
     *      array('username' => 'user1', 'password' => 'pass1'),
     *      array('username' => \PDO::PARAM_STR, 'password' => \PDO::PARAM_STR)
     * );
     * </code>
     *
     * @access public
     * @param  string   $table Table name
     * @param  array    $data  An associative array with column - value pairs
     * @param  array    $types An associative array with column - value type pairs
     * @return int|bool Number of affected rows or false on failure
     */
    public function insert($table, array $data, array $types = array())
    {
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_keys($data)),
            implode(', ', array_fill(0, count($data), '?'))
        );

        return $this->execWriteQuery($sql, array_values($data), $types);
    }

    /**
     * Execute an delete statement on a table
     *
     * @access public
     * @param  string   $table Table name
     * @param  array    $where An associative array with column - value pairs
     * @param  array    $types An associative array with column - value type pairs
     * @return int|bool Number of affected rows or false on failure
     */
    public function delete($table, array $where, array $types = array())
    {
        $sql = sprintf('DELETE FROM %s WHERE %s',
            $table,
            implode(' = ? AND ', array_keys($where)) .' = ?'
        );

        return $this->execWriteQuery($sql, array_values($where), $types);
    }

    /**
     * Prepare, execute SQL query and return the first row from result as
     * associative array
     *
     * @access public
     * @param  string     $sql    SQL query
     * @param  array      $params SQL query parameters
     * @return array|bool False on failure
     */
    public function fetchAssoc($sql, array $params = array())
    {
        return $this->execReadQuery($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Prepare, execute SQL query and return the first row from result as
     * numerically indexed array
     *
     * @access public
     * @param  string     $sql    SQL query
     * @param  array      $params SQL query paramenters
     * @return array|bool False on failure
     */
    public function fetchArray($sql, array $params = array())
    {
        return $this->execReadQuery($sql, $params)->fetch(PDO::FETCH_NUM);
    }

    /**
     * @access public
     * @param  string            $sql    SQL query
     * @param  array             $params SQL query parameters
     * @param  array             $types  An associative array with column - value type pairs
     * @return PDOStatement|bool False on failure
     */
    public function read($sql, array $params = array(), array $types = array())
    {
        return $this->execReadQuery($sql, $params, $types);
    }

    /**
     * @access public
     * @param  string   $sql    SQL query
     * @param  array    $params SQL query parameters
     * @param  array    $types  Parameters types
     * @return int|bool Affected rows number or false on failure
     */
    public function write($sql, array $params = array(), array $types = array())
    {
        return $this->execWriteQuery($sql, $params, $types);
    }

    /**
     * Used for statements that returns some data
     *
     * @access public
     * @param  string            $sql    SQL query
     * @param  array             $params SQL query parameters
     * @param  array             $types  An associative array with column - value type pairs
     * @return PDOStatement|bool False on failure
     *
     * @codeCoverageIgnore
     */
    protected function execReadQuery($sql, array $params = array(), array $types = array())
    {
        if (!$this->isConnected()) {
            $this->addError('No connection to database is open.');

            return false;
        }

        try {
            $stmt = $this->pdo->prepare($sql);

            if (!empty($types)) {
                foreach ($params as $key => $val) {
                    $type = isset($types[$key]) ? $types[$key] : PDO::PARAM_STR;
                    $stmt->bindValue($key+1, $val, $type);
                }
                $stmt->execute();
            } else {
                $stmt->execute($params);
            }

            $stmt->setFetchMode($this->getOption('fetch.style'));

            return $stmt;
        } catch (PDOException $e) {
            $this->addError($e->getMessage(), $e->getCode());

            return false;
        }
    }

    /**
     * Execute statement that doesn't return data
     *
     * @access public
     * @param  string   $sql    SQL query
     * @param  array    $params SQL query parameters
     * @param  array    $types  Parameters types
     * @return int|bool Affected rows number or false on failure
     *
     * @codeCoverageIgnore
     */
    protected function execWriteQuery($sql, array $params, array $types = array())
    {
        if (!$this->isConnected()) {
            $this->addError('No connection to database is open.');

            return false;
        }

        try {
            if (empty($params)) {
                $result = $this->pdo->exec($sql);
            } else {
                $stmt = $this->pdo->prepare($sql);

                if (!empty($types)) {
                    foreach ($params as $key => $val) {
                        $type = isset($types[$key]) ? $types[$key] : PDO::PARAM_STR;
                        $stmt->bindValue($key+1, $val, $type);
                    }
                    $stmt->execute();
                } else {
                    $stmt->execute($params);
                }

                $result = $stmt->rowCount();
            }

            return $result;
        } catch (PDOException $e) {
            $this->addError($e->getMessage(), $e->getCode());

            return false;
        }
    }

    /**
     * Create PDO instance
     *
     * @access protected
     * @param  array    $params
     * @return PDO|bool False on failure
     *
     * @codeCoverageIgnore
     */
    protected function factory(array $params)
    {
        try {
            return new PDO(
                sprintf(
                    'mysql:host=%s;dbname=%s', $params['host'], $params['name']
                ),
                $params['user'], $params['pass'],
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch (PDOException $e) {
            $this->addError($e->getMessage(), $e->getCode());

            return false;
        }
    }

    /**
     * Check if given array is multidimensional
     *
     * @access protected
     * @param  array $arr
     * @return bool
     *
     * @codeCoverageIgnore
     */
    protected function isRecursive(array $arr)
    {
        return ( count($arr) === count($arr, COUNT_RECURSIVE) ) ? false : true;
    }

    /**
     * Add an error to stack
     *
     * @access protected
     * @param  string $message Error message
     * @param  int    $code    Error code
     * @return void
     *
     * @codeCoverageIgnore
     */
    protected function addError($message, $code = 999)
    {
        $this->errors[] = sprintf('%d : %s', $code, $message);
    }

    // -----------------------------------------------------------------------------------------------------------------
}
