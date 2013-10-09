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

use Gentle\AntDb\AntDb;

/**
 * @author  Alexandru G.    <alex@gentle.ro>
 */
class AntDbTest extends TestCase
{
    /**
     * @var AntDb
     */
    static private $db;

    public function setUp()
    {
        parent::setUp();

        if (!$this->getAntDb() instanceof AntDb) {
            self::$db = new AntDb(array(), $this->getPDOConnection());
        }
    }

    public function testIsConnected()
    {
        $this->assertTrue($this->getAntDb()->isConnected());
        $this->assertInstanceOf('\PDO', $this->getAntDb()->getConnection());
    }

    /*public function testDiscconnect()
    {
        $this->assertTrue($this->getAntDb()->isConnected());
        $this->getAntDb()->disconnect();
        $this->assertFalse($this->getAntDb()->isConnected());
    }*/

    public function testChangeFetchMode()
    {
        $this->assertEquals(\PDO::FETCH_OBJ, $this->getAntDb()->getOption('fetch.style'));
        $this->getAntDb()->setFetchMode(\PDO::FETCH_ASSOC);
        $this->assertEquals(\PDO::FETCH_ASSOC, $this->getAntDb()->getOption('fetch.style'));
    }

    public function testInsertStatement()
    {
        $this->assertEquals(1, $this->getAntDb()->insert(
                $GLOBALS['DB_TABLE'], array('username' => 'joe', 'password' => 'secret'))
        );
    }

    public function testUpdateStatement()
    {
        $this->assertEquals(1, $this->getAntDb()->update(
                $GLOBALS['DB_TABLE'], array('password' => 'secret'), array('username' => 'steve'))
        );
    }

    public function testCustomRead()
    {
        $result     = $this->getAntDb()->read('select * from '. $GLOBALS['DB_TABLE'])->fetch();
        $expected   = array(
            'id'        => '1',
            'username'  => 'steve',
            'password'  => 'steve_pass'
        );

        $this->assertEquals($expected, $result);
    }

    public function testDelete()
    {
        $this->assertEquals(1, $this->getAntDb()->delete($GLOBALS['DB_TABLE'], array('username' => 'steve')));
    }

    public function testFetchAssoc()
    {
        $result = $this->getAntDb()->fetchAssoc(
            'select id, username from '. $GLOBALS['DB_TABLE'] .' WHERE username = ?',
            array('nancy')
        );
        $expected = array(
            'id'        => '2',
            'username'  => 'nancy'
        );

        $this->assertEquals($expected, $result);
    }

    public function testFetchArray()
    {
        $result = $this->getAntDb()->fetchArray(
            'select id, username from '. $GLOBALS['DB_TABLE'] .' WHERE username = ?',
            array('nancy')
        );
        $expected = array(
            0           => '2',
            1           => 'nancy'
        );

        $this->assertEquals($expected, $result);
    }

    public function testCustomWrite()
    {
        $this->assertEquals(1, $this->getAntDb()->write(
            sprintf('INSERT INTO %s (username, password) VALUES (?, ?)', $GLOBALS['DB_TABLE']),
            array('amy', 'pink'),
            array(\PDO::PARAM_STR, \PDO::PARAM_STR)
        ));
    }

    public function testErrors()
    {
        $this->assertFalse( $this->getAntDb()->delete('non_existent_table', array('id' => 1000)) );
        $this->assertTrue($this->getAntDb()->hasError());
        $this->assertNotEmpty($this->getAntDb()->getErrors());
        $this->assertNotNull($this->getAntDb()->getLastError());
    }

    public function testDisconnectAndConnect()
    {
        $this->getAntDb()->disconnect();
        $this->assertFalse($this->getAntDb()->isConnected());
        $this->assertInstanceOf('\PDO', $this->getAntDb()->connect($this->getPDOConnection()));
        $this->assertTrue($this->getAntDb()->isConnected());
        $this->assertInstanceOf('\PDO', $this->getAntDb()->connect());
    }

    /**
     * @return AntDb
     */
    private function getAntDb()
    {
        return self::$db;
    }

}
