<?php
require_once realpath(dirname(__FILE__) .'/../../libs/TestHelper.php');
class DataCashApiWrapper extends DataCash_Api  {
	
	function getConfig() {
		return $this->_config;
	}
}
/**
 * DataCashApi Testcase.
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage Tests_DataCashApi
 *
 * $LastChangedBy: yomi $
 */
class DataCashApiTest extends PHPUnit_Framework_TestCase {
	private $_apiWrapper;
	private $_api;
	
	function setUp() {
		$this->_api = new DataCash_Api();
		$this->_apiWrapper = new DataCashApiWrapper();
	}
	
	function tearDown() {
		unset($this->_apiWrapper);
	}
	/**
	 * We want to check that we get the expected structure from
	 * xmlwriter & an idea of how it works, once we have this
	 * down we can start working on the actual methods needed
	 * to authenticate.
	 *
	 */
	function testXMLWriterCanCreateOurNeededXML() {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'Authentication');
		xmlwriter_write_element($xml,'client','blah');
		xmlwriter_write_element($xml,'password','blah');
		xmlwriter_end_element($xml);
		$result = xmlwriter_output_memory($xml,true);
		$expected = '<Authentication><client>blah</client><password>blah</password></Authentication>';
		$this->assertContains('Authentication',$result);
		$this->assertXmlStringEqualsXmlString($expected,$result);
	}
	
	function testConstrutor() {
		$this->assertNotNull(new DataCash_Api());
	}
	
	function testDataCashApiHasConfigProperty() {
		$this->assertClassHasAttribute('_config','DataCash_Api');
	}
	
	function testDataCashConfigPropertyIsNullWhenNotSet() {
		$this->assertNotNull($this->_apiWrapper->getConfig());
		$this->assertType('Zend_Config',$this->_apiWrapper->getConfig());
	}
	
	/**
	 * All configuration settings pertaining Datacash should be stored in the config
	 * property of the object, which will be used to retrieve all the configuration based
	 * settings of datacash.
	 *
	 */
	function testDataCashApiConfigCanSpecifyConfigPathAndConfigHasExpectedData() {
		$datacash_api = new DataCashApiWrapper('/../../configs','settings.ini');
		$this->assertNotNull($datacash_api);
		$config = $datacash_api->getConfig();
		$this->assertType('Zend_Config',$config);
		$this->assertEquals('https://testserver.datacash.com/Transaction',$config->host);
		$this->assertNotEquals(null,$config->timeout);
		$this->assertNotEquals(null,$config->logging);
		$this->assertNotEquals(null,$config->logfile);
		$this->assertNotEquals(null,$config->cacert_location);
		$this->assertNotEquals(null,$config->deposit->client);
		$this->assertNotEquals(null,$config->deposit->password);
		$this->assertNotEquals(null,$config->withdrawal->client);
		$this->assertNotEquals(null,$config->withdrawal->password);
	}
}