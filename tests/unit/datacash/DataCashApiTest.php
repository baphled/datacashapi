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
	
	private $_fixture;
	private $_xmlFixture;
	private $_api;
	private $_apiWrapper;
	
	
	function setUp() {
		$this->_fixture = new DataCashFixtures();
		$this->_xmlFixture = new DataCashXMLFixtures();
		$this->_api = new DataCash_Api();
		$this->_apiWrapper = new DataCashApiWrapper();
	}
	
	function tearDown() {
		unset($this->_xmlFixture);
		unset($this->_fixture);
		unset($this->_api);
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
	
	function testDataCashApiCanSetupAuthenticationElementUsingGetAuth() {
		$xmlDeposit = $this->_api->getAuth();
		$xmlWithdrawal = $this->_api->getAuth('withdrawal');
		$this->assertContains('Authentication',$xmlDeposit);
		$this->assertContains('client',$xmlDeposit);
		$this->assertContains('password',$xmlDeposit);
		$this->assertNotEquals($xmlDeposit,$xmlWithdrawal);
		$this->assertType('string', $xmlDeposit);
		$this->assertType('string', $xmlWithdrawal);
	}
	
	/**
	 * We now need to setup card elements and make sure that we can get the
	 * expected format.
	 *
	 */
	function testSetCardDataThrowsExceptionIfParamsArrayIsEmpty() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setCardData(array());
	}
	
	function testSetCardDataParamsMustHaveKeysPanAndExpiryDateThrowsOtherwise() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setCardData($this->_fixture->find('invalidCard'));
	}
	
	function testSetCardDataCanTakePanAndExpiryAsMandatory() {
		$this->assertType('string',$this->_api->setCardData($this->_fixture->find('validCard')));
	}
	
	/**
	 * All card elements contain a pan & expiry element, so these should be part of the result
	 * regardless of the card type.
	 *
	 */
	function testSetCardDataReturnsCardElementOnSuccess() {
		$fixture = $this->_fixture->find('validCard');
		$expected = $this->_xmlFixture->find('cardPanAndStartDate');
		$this->assertContains('Card',$this->_api->setCardData($fixture));
		$this->assertEquals($expected[0],$this->_api->setCardData($fixture));
	}
	
	function testSetCardDataPopulatesStartDateAndIssueNumberIfItPassedWithParam() {
		$fixture = $this->_fixture->find('withIssueNum');
		$expected = $this->_xmlFixture->find('cardIssueNumAndStartDate');
		$this->assertEquals($expected[0],$this->_api->setCardData($fixture));
		
	}
	
	function testCardTxnThrowsExceptionIfParamsEmpty() {
		$this->setExpectedException('Zend_Exception');
		$this->assertFalse($this->_api->setCardTxn(array()));
	}
	
	function testSetResponseThrowsExceptionIfParametersArrayIsEmpty() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setRequest(array());
	}
	
	function testSetCardTxnThrowsExceptionIfMethodIsNotAValueInParams() {
		$this->setExpectedException('Zend_Exception');
		$fixture = $this->_fixture->find('NoMethodCardTxn');
		$this->assertContains('CardTxn',$this->_api->setCardTxn($fixture));
	}
	
	function testSetCardTxnDoesNotSupplyAnAuthCodeIfNonAreSupplied() {
		$fixture = $this->_fixture->find('NoAuthCodeCardTxn');
		$this->assertNotContains('authcode',$this->_api->setCardTxn($fixture));
	}
	
	function testSetCardTxnOnlySetsAuthcodeIfOneIsSupplied() {
		$fixture = $this->_fixture->find('NoAuthCodeCardTxn');
		$this->assertContains('CardTxn',$this->_api->setCardTxn($fixture));
	}
	
	/**
	 * We now need to focus on TxnDetails
	 *
	 */
	function testSetTxnDetailsThrowsExceptionIfParamsNotSet() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setTxnDetails(array());
	}
	
	function testSetTxnDetailsThrowsExceptionIfMerchantReferenceNotSupplied() {
		$params = array();
		$params['merchantreferenc'] = 'blah';
		$params['amount'] = 100.00;
		$this->setExpectedException('Zend_Exception');
		$this->_api->setTxnDetails($params);
	}
	
	function testSetTxnDetailsThrowsExceptionIfAmountNotSupplied() {
		$params = array();
		$params['merchantreference'] = 'blah';
		$params['amoun'] = 100.00;
		$this->setExpectedException('Zend_Exception');
		$this->_api->setTxnDetails($params);
	}
	
	function testSetTxnDetailsReturnsStringOnSuccess() {
		$fixture = $this->_fixture->find('TxnDetailsNoCurrency');
		$result = $this->_api->setTxnDetails($fixture);
		$this->assertType('string',$result);
		$this->assertContains('TxnDetails',$result);
		$this->assertContains('merchantreference',$result);
		$this->assertContains('amount',$result);
		$this->assertContains('GBP',$result);
	}
	
	function testSetTxnDetailsOnlySetsGBPIfNoCurrencyIsSet() {
		$fixture = $this->_fixture->find('TxnDetailsEUCurrency');
		$expected = $this->_xmlFixture->find('txnDetailsWithEUR');
		$result = $this->_api->setTxnDetails($fixture);
		$this->assertContains('EUR',$result);
		$this->assertEquals($expected[0],$result);
	}
	
	function testSetTxnDetailsReturnsWhatWeExpect() {
		$fixture = $this->_fixture->find('TxnDetailsNoCurrency');
		$expected = $this->_xmlFixture->find('TxnDetails');
		$result = $this->_api->setTxnDetails($fixture);
		$this->assertEquals($expected[0],$result);
	}
	
	function testSetRequestThrowsExceptionIfParametersNotPassed() {
		$this->setExpectedException('Zend_Exception');
		$this->_api->setRequest();
	}
	function testSetRequestReturnsString() {
		$fixture = $this->_fixture->find('CompleteRequest');
		$expected = $this->_xmlFixture->find('TransactionRequest');
		$result = $this->_api->setRequest($fixture);
		$this->assertType('string',$result);
		$this->assertContains('Request',$result);
		$this->assertContains('Authentication',$result);
		$this->assertContains('client',$result);
		$this->assertContains('password',$result);
		$this->assertContains('Transaction',$result);
		$this->assertContains('CardTxn',$result);
		$this->assertContains('Card',$result);
		$this->assertContains('pan',$result);
		$this->assertContains('expirydate',$result);
		$this->assertContains('authcode',$result);
		$this->assertContains('method',$result);
		$this->assertContains('TxnDetails',$result);
		$this->assertContains('merchantreference',$result);
		$this->assertContains('amount',$result);
		$this->assertContains('currency',$result);
		$this->assertEquals($expected[0],$result);
	}
}