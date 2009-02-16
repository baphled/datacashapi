<?php
require_once realpath(dirname(__FILE__) .'/../../libs/TestHelper.php');
class DataCashApiWrapper extends DataCash_Api  {
	
	function getConfig() {
		return $this->_config;
	}
}

class DatacashApiConfigWrapper extends DataCash_Api {
	function __construct() {
		$this->_config->datacash = null;
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
	
	private $_fixture;
	private $_xmlFixture;
	private $_api;
	private $_apiWrapper;
	private $_apiConfigWrapper;
	
	
	function setUp() {
		$this->_fixture = new DataCashFixtures();
		$this->_xmlFixture = new DataCashXMLFixtures();
		$this->_api = new DataCash_Api();
		$this->_apiWrapper = new DataCashApiWrapper();
		$this->_apiConfigWrapper = new DatacashApiConfigWrapper();
	}
	
	function tearDown() {
		unset($this->_xmlFixture);
		unset($this->_fixture);
		unset($this->_api);
		unset($this->_apiWrapper);
		unset($this->_apiConfigWrapper);
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
	
	function testSetResponseThrowsExceptionIfParametersArrayIsEmpty() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setRequest(array());
	}
	
	function testSetRequestThrowsExceptionIfParametersNotPassed() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setRequest();
	}
	
	function testSetResponsesThrowsExceptionIfPanArrayDoesntExist() {
		$fixture = $this->_fixture->find('invalidCardRequestPanMispelt');
		$this->setExpectedException('Zend_Exception');
		$this->_api->setRequest($fixture);
	}
	

	function testSetResponsesThrowsExceptionIfExpiryDateArrayDoesntExist() {
		$fixture = $this->_fixture->find('invalidCardRequestExpiryMispelt');
		$this->setExpectedException('Zend_Exception');
		$this->_api->setRequest($fixture);
	}
	
function testSetResponsesThrowsExceptionIfNoMethod() {
		$fixture = $this->_fixture->find('NoMethodRequest');
		$this->setExpectedException('Zend_Exception');
		print_r($this->_api->setRequest($fixture));
	}
	
	function testSetResponsesGensIssueNumAndStartDateIfSupplied() {
		$fixture = $this->_fixture->find('RequestWithIssueNumAndStartDate');
		$result = $this->_api->setRequest($fixture);
		$this->assertContains('startdate',$result);
		$this->assertContains('issuenumber',$result);
	}
	
	function testSetRequestReturnsString() {
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$expected = $this->_xmlFixture->find('DepositTransactionRequest');
		$result = $this->_api->setRequest($fixture);
		$this->assertType('string',$result);
		$this->assertEquals($expected[0],$result);
	}
	
	function testSetRequestWithdrawalReturnsExpectedRequest() {
		$fixture = $this->_fixture->find('CompleteWithdrawalRequest');
		$result = $this->_api->setRequest($fixture,'withdrawal');
		$this->assertContains('passwordWithdrawal', $result);
	}
	
	/**
	 * CV2Avs checks will be executed if the settings require it
	 * if this the the case the results will be placed inside
	 * the Card element of our DataCash requests.
	 *
	 */
	function testSetCV2AvsCheckReturnsFalseByDefault() {
		$fixture = $this->_fixture->find('TestCV2AvsRequest');
		$result = $this->_api->_cv2avsCheck($fixture);
		$this->assertFalse($result);
	}
	// We need to add CV2Avs checks to our requests
	function testCV2AvsCheckThrowsExceptionIfConfigSettingsNotPresent() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('TestCV2AvsRequest');
		$this->_apiConfigWrapper->_cv2avsCheck($fixture);
	}
	
	/**
	 * We'll use a fixture that doesn't have a AV2Cvs array element,
	 * we'll then make sure we have each mandatory field.
	 *
	 */
	function testCV2AvsCheckThrowsExceptionIfNoCv2DataIsPassed() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->_api->_cv2avsCheck($fixture);
	}
	
	/**
	 * We need to setup the statement address which consists of a 
	 * max of for street_address fields & a postcode.
	 * 
	 * The documentation says that these are all conditional so
	 * we shouldn't throw any exceptions if the data is empty.
	 */
	function testSetCv2AddressResultIsNotNull() {
		$fixture = $this->_fixture->find('TestCV2AvsSingleStreetAddressRequest');
		$result = $this->_api->_setCV2Address($fixture['CV2Avs']);
		$this->assertNotNull($result);
		$this->assertContains('CV2Avs',$result);
		$this->assertContains('street_address1',$result);
	}
	
	function testSetCv2DoesnotSetAddress2IfItIsNotSet() {
		$fixture = $this->_fixture->find('TestCV2AvsNoStreetAddress2or3Request');
		$result = $this->_api->_setCV2Address($fixture['CV2Avs']);
		$this->assertNotNull($result);
		$this->assertNotContains('street_address2',$result);
		$this->assertNotContains('street_address3',$result);
	}
	
	function testSetCv2DoesNotHaveAddress() {
		$fixture = $this->_fixture->find('TestCV2AvsNoAddressRequest');
		$result = $this->_api->_setCV2Address($fixture['CV2Avs']);
		$this->assertNotNull($result);
		$this->assertNotContains('street_address2',$result);
		$this->assertNotContains('street_address3',$result);
		$this->assertContains('postcode',$result);
		print_r($result);
	}
	
	function testSetCv2AddressThrowsExceptionIfStreetAdress1KeyIsNotPresentOrSmispelt() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('TestCV2AvsNoStreetAddress1Request');
		$this->_api->_setCV2Address($fixture['CV2Avs']);
	}
}