<?php

namespace Checkdomain;

require(__DIR__.'/../../src/Checkdomain/Toml.php');

/**
 * Testcases for \Checkdomain\Toml
 * 
 * @author benjaminpaap
 */
class TomlTest extends \PHPUnit_Framework_TestCase
{

	protected $toml;
	protected $file;
	
	public function setUp()
	{
		$this->toml = new \Checkdomain\Toml;
		$this->file = __DIR__.'/Resources/example.toml';
	}
	
	/**
	 * Data Provider for multiple TOML data formats
	 * 
	 * @return array
	 */
	public function valueProvider()
	{
		return array( /* $value, $expectation */
			array('"I\'m a string"', "I'm a string"),
			array('42', 42),
			array('4.2', 4.2),
			array('true', true),
			array('false', false),
			array('1979-05-27T07:32:00Z', new \DateTime('1979-05-27T07:32:00Z')),
			array('[ 8001, 8001, 8002 ]', array(8001, 8001, 8002)),
			array('[ [ 1, 2 ], ["a", "b", "c"] ]', array(array(1, 2), array("a", "b", "c"))),
			array('[ '.PHP_EOL.'1, 2, 3'.PHP_EOL.']', array(1, 2, 3))
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testValueConverter($value, $expectation)
	{
		$method = new \ReflectionMethod($this->toml, 'convertValue');
        $method->setAccessible(true);
        
		$this->assertEquals($expectation, $method->invoke($this->toml, $value));
	}
	
	/**
	 * Tests the parser directly through the constructor
	 */
	public function testConstructorParse()
	{
		$toml = new \Checkdomain\Toml(file_get_contents($this->file));
		$this->assertEquals('192.168.1.1', $toml->get('database.server'));
	}
	
	/**
	 * @expectedException Exception
	 */
	public function testArrayNotClosedException()
	{
		$method = new \ReflectionMethod($this->toml, 'convertValue');
        $method->setAccessible(true);
        
		$method->invoke($this->toml, '[ 1, 2, 3');
	}

	/**
	 * @expectedException Exception
	 */
	public function testPathNotFoundException()
	{
		$this->toml->get('this.path.does.not.exist');
	}
	
	/**
	 * Tests a toml file
	 */
	public function testTomlFile()
	{
		$result = $this->toml->parse(file_get_contents($this->file));

		$this->assertEquals('192.168.1.1', $result['database']['server']);
		$this->assertEquals(6, count($result['servers']['alpha']['ports']));
		$this->assertEquals(5, count($result['servers']['alpha']['ports'][5]));
		$this->assertEquals(3, count($result['servers']['beta']['ports']));
		$this->assertEquals("1,1", $result['servers']['alpha']['ports'][5][0]);
		$this->assertEquals('10.0.0.2', $result['servers']['beta']['ip']);
		
		$this->assertEquals('Benjamin Paap', $result['php']['parser']['author']);
		$this->assertEquals('Checkdomain GmbH', $result['php']['parser']['organization']);
		$this->assertEquals('www.checkdomain.de', $result['php']['parser']['website']);
		$this->assertTrue($result['php']['parser']['hiring']);
	}
	
	/**
	 * Tests the getter method
	 */
	public function testGetMethod()
	{
		$this->toml->parse(file_get_contents($this->file));
		
		$this->assertEquals('192.168.1.1', $this->toml->get('database.server'));
		$this->assertEquals('1,2', $this->toml->get('servers.alpha.ports.5.1'));
	}
	
}
