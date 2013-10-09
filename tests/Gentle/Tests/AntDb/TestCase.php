<?php

/**
 * This file is part of the AntDb package.
 *
 * (c) Alexandru G. <alex@gentle.ro>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gentle\Tests\AntDb;

use PHPUnit_Extensions_Database_TestCase;

/**
 * @author  Alexandru G.    <alex@gentle.ro>
 */
abstract class TestCase extends PHPUnit_Extensions_Database_TestCase
{
    static private $pdo = null;
    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new \PDO(
                    sprintf('mysql:dbname=%s;host=%s', $GLOBALS['DB_NAME'], $GLOBALS['DB_HOST']),
                    $GLOBALS['DB_USER'], $GLOBALS['DB_PASS']
                );

                $this->resetSchema(self::$pdo);
            }

            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_NAME']);
        }

        return $this->conn;
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_files/users-seed.xml');
    }

    /**
     * @return \PDO
     */
    public function getPDOConnection()
    {
        return self::$pdo;
    }

    private function resetSchema(\PDO $pdo)
    {
        $pdo->exec( sprintf('DROP TABLE IF EXISTS %s', $GLOBALS['DB_TABLE']) );
        $pdo->exec( sprintf('
            CREATE TABLE %s (
                id int(11) NOT NULL AUTO_INCREMENT,
                username VARCHAR(255),
                password VARCHAR(255),
                PRIMARY KEY (`id`)
            ) charset=utf8', $GLOBALS['DB_TABLE'])
        );
    }
}
