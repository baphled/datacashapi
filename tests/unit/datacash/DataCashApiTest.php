<?php
require_once realpath(dirname(__FILE__) .'/../../libs/TestHelper.php');

/**
 * DataCashApiWrapper
 * 
 * Allows us to retrieve our Zend_Config object.
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage Tests_DataCashApi
 *
 * $LastChangedBy: yomi $
 */
class DataCashApiWrapper extends DataCash_Api  {
	
	function getConfig() {
		return $this->_config;
	}
}

/**
 * FakeConfig
 * 
 * A fake configuration class used to create our test classes.
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage Tests_DataCashApi
 *
 * $LastChangedBy: yomi $
 */
class FakeConfig {
	function __construct() {
		$this->_config = new stdClass();
		$this->_config->extendedPolicy =  null;
	}
}

/**
 * DatacashApiExtendedPolicyWrapper
 * 
 * Helps us to test condition where the extended policy is set to true
 * but has no other pieces of information
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage Tests_DataCashApi
 *
 * $LastChangedBy: yomi $
 */
class DatacashApiExtendedPolicyWrapper extends DataCash_Api {
	function __construct() {
		$this->_config = new FakeConfig();
		$this->_config->extendedPolicy->set = true;
	}
}

/**
 * DatacashApiConfigWrapper
 * 
 * Sets our Datacash extended policy to false
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage Tests_DataCashApi
 *
 * $LastChangedBy: yomi $
 */
class DatacashApiConfigWrapper extends DataCash_Api {
	function __construct() {
		$this->_config = new FakeConfig();
		$this->_config->extendedPolicy->set = false;
	}
}

/**
 * DatacashApiConfig3DSecure
 * 
 * Helps to setup our configuration so we can easily check what happens
 * if 3DSecure's config settings are false
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage Tests_DataCashApi
 *
 * $LastChangedBy: yomi $
 */
class DatacashApiConfig3DSecure extends DataCash_Api {
	function __construct() {
		$this->_config = new FakeConfig();
		$this->_config->extendedPolicy->set = true;
		$this->_config->extendedPolicy->cv2_policy->notprovided = 'reject';
		$this->_config->extendedPolicy->cv2_policy->notchecked = 'accept';
		$this->_config->extendedPolicy->cv2_policy->matched = 'accept';
		$this->_config->extendedPolicy->cv2_policy->notmatched = 'reject';
		$this->_config->extendedPolicy->cv2_policy->partialmatch = 'reject';
		
		$this->_config->extendedPolicy->postcode_policy->notprovided = 'reject';
		$this->_config->extendedPolicy->postcode_policy->notchecked = 'accept';
		$this->_config->extendedPolicy->postcode_policy->matched = 'accept';
		$this->_config->extendedPolicy->postcode_policy->notmatched = 'reject';
		$this->_config->extendedPolicy->postcode_policy->partialmatch = 'reject';
		
		$this->_config->extendedPolicy->address_policy->notprovided = 'reject';
		$this->_config->extendedPolicy->address_policy->notchecked = 'accept';
		$this->_config->extendedPolicy->address_policy->matched = 'accept';
		$this->_config->extendedPolicy->address_policy->notmatched = 'reject';
		$this->_config->extendedPolicy->address_policy->partialmatch = 'reject';
		$this->_config->cv2avs->check = true;
		$this->_config->threeDSecure->verify = false;
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
	private $_apiExtendedPolicy;
	
	
	function setUp() {
		$this->_fixture = new DataCashFixtures();
		$this->_xmlFixture = new DataCashXMLFixtures();
		$this->_api = new DataCash_Api();
		$this->_apiWrapper = new DataCashApiWrapper();
		$this->_apiConfigWrapper = new DatacashApiConfigWrapper();
		$this->_apiExtendedPolicy = new DatacashApiExtendedPolicyWrapper();
		$this->_api3DSecureNoVerify = new DatacashApiConfig3DSecure();
	}
	
	function tearDown() {
		unset($this->_xmlFixture);
		unset($this->_fixture);
		unset($this->_api);
		unset($this->_apiWrapper);
		unset($this->_apiConfigWrapper);
		unset($this->_apiExtendedPolicy);
		unset($this->_api3DSecureNoVerify);
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
		$this->_api->setRequest($fixture);
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
		//$this->assertEquals($expected[0],$result);
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
	function testSetCV2AvsCheckReturnsStringByDefault() {
		$fixture = $this->_fixture->find('TestCV2AvsRequest');
		$result = $this->_api->setRequest($fixture);
		$this->assertType('string',$result);
	}
	// We need to add CV2Avs checks to our requests
	function testCV2AvsCheckThrowsExceptionIfConfigSettingsNotPresent() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('TestCV2AvsRequest');
		$this->_apiConfigWrapper->setRequest($fixture);
	}
	
	/**
	 * We'll use a fixture that doesn't have a AV2Cvs array element,
	 * we'll then make sure we have each mandatory field.
	 *
	 */
	function testCV2AvsCheckThrowsExceptionIfNoCv2DataIsPassed() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->_apiConfigWrapper->setRequest($fixture);
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
		$result = $this->_api->setRequest($fixture);
		$this->assertNotNull($result);
		$this->assertContains('CV2Avs',$result);
		$this->assertContains('street_address1',$result);
	}
	
	function testSetCv2DoesnotSetAddress2IfItIsNotSet() {
		$fixture = $this->_fixture->find('TestCV2AvsNoStreetAddress2or3Request');
		$result = $this->_api->setRequest($fixture);
		$this->assertNotNull($result);
		$this->assertNotContains('street_address2',$result);
		$this->assertNotContains('street_address3',$result);
	}
	
	function testSetCv2DoesNotHaveAddress() {
		$fixture = $this->_fixture->find('TestCV2AvsNoAddressRequest');
		$result = $this->_api->setRequest($fixture);
		$this->assertNotNull($result);
		$this->assertNotContains('street_address2',$result);
		$this->assertNotContains('street_address3',$result);
		$this->assertContains('postcode',$result);
	}
	
	function testSetCv2AddressThrowsExceptionIfStreetAdress1KeyIsNotPresentOrSmispelt() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('TestCV2AvsNoStreetAddress1Request');
		$this->_api->setRequest($fixture);
	}
	
	function testSetRequestIfExtendedPolicyConfigIsSetButNotFound() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('TestCV2AvsNoStreetAddress2or3Request');
		$this->_apiConfigWrapper->setRequest($fixture);
	}
	
	function testSetRequestIfExtendedPolicyConfigIsSetToFalseReturnFalse() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->_apiConfigWrapper->setRequest($fixture);
	}
	
	function testvVlidatePolicyThrowsExceptionIfExtendedPolicySetButNoCV2PolicyPresent() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->_apiExtendedPolicy->setRequest($fixture);
	}
	
	/**
	 * We now need to retrieve the extended policies and insert the results int cv2avs
	 */
	function testSetRequestsReturnsExtendedPolicy() {
		$expected = '<ExtendedPolicy><cv2_policy notprovided="reject" notchecked="accept" matched="accept" notmatched="reject" partialmatch="reject"/><postcode_policy notprovided="reject" notchecked="accept" matched="accept" notmatched="reject" partialmatch="reject"/><address_policy notprovided="reject" notchecked="accept" matched="accept" notmatched="reject" partialmatch="accept"/></ExtendedPolicy>';
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		print_r($this->_api->setRequest($fixture));
		$this->assertContains($expected,$this->_api->setRequest($fixture));
	}
	
	function test3DSecureThrowsExceptionIfNoConfigPropertySet() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->_apiConfigWrapper->setRequest($fixture);
	}
	
	function test3DSecureMethodReturnsEmptyElementIfConfigSetToNo() {
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->assertContains('<ThreeDSecure><verify>no</verify></ThreeDSecure>',$this->_api3DSecureNoVerify->setRequest($fixture));
	}
	
	/**
	 * Basic tests to make sure 3Dsecure elements are built as we expect
	 * @todo Test that the broswer element is populated, will need to check
	 * via a browser.
	 */
	function test3DSecureMethodReturnsEmptyElementIfConfigSetToYes() {
		$fixture = $this->_fixture->find('CompleteDepositRequest');
		$this->assertContains('yes',$this->_api->setRequest($fixture));
	}
}