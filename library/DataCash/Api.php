<?php
/**
 * DataCashApi
 * 
 * Used to interact with DataCash's API.
 * 
 * As their API still uses references and other PHP4 functionality
 * this version will be PHP5 compliant & fully tested.
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage DataCashApi
 *
 * $LastChangedBy: yomi $
 */

class DataCash_Api extends DataCash_Base {
	
	/**
	 * Validation object used to validate DataCash information
	 *
	 * @var DataCash_Validate
	 * 
	 */
	protected $_validate;
	
	/**
	 * Gathers or configuration settings for DataCash
	 *
	 * @param string $configPath
	 * @param string $file
	 */
	function __construct($configPath = null,$file=null) {
		parent::__construct($configPath,$file);
		$this->_validate = new DataCash_Validate($configPath,$file);
	}
	
	/**
	 * Validates our DataCash requests parameters.
	 *
	 * @param	Array 	$dataArray	dataArray used to create our request body.
	 */
	private function _validateRequest($dataArray) {
		if (!isset($this->_datacash->extendedPolicy->set)) {
			throw new Zend_Exception('Must have extended policy setting in config file.');
		}
		if (empty($dataArray)) {
			throw new Zend_Exception('Parameters must be in array format.');
		}
		
		if (!array_key_exists('Card',$dataArray)) {
			throw new Zend_Exception('Must have card details.');
		}
		
		if (!array_key_exists('Transaction',$dataArray)) {
			throw new Zend_Exception('Must have transaction details.');
		}
	}
	
	/**
	 * sets up and gets our authentication information from our
	 * environments configuration file.
	 * 
	 * Will retrieve the appropriate credentials depending on whether the transaction
	 * to be is a withdrawal or a deposit.
	 *
	 * @param 	String 	$type	The type of request we are about to make.
	 * @return 	String	$xml	Our authentication XML element
	 */
	private function _handleAuth($type = 'deposits') {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Authentication');
		xmlwriter_write_element($xml, 'client', $this->_datacash->$type->client);
		xmlwriter_write_element($xml, 'password', $this->_datacash->$type->password);
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Gets our card information and turns the data into the 
	 * needed XML element.
	 *
	 * @param 	Array 	$params	CardData parmeters
	 * @return 	String	$xml	CardData XML element.
	 * 
	 */
	private function _handleCardData($params = array()) {
		$this->_validate->validateCard($params);
		$this->_validate->validateCv2Avs($params);
		$cardDataArray = $params['Card'];
		
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Card');
		xmlwriter_write_element($xml,'pan',$cardDataArray['pan']);
		xmlwriter_write_element($xml,'expirydate',$cardDataArray['expirydate']);
		if ( array_key_exists('startdate',$cardDataArray) && array_key_exists('issuenumber',$cardDataArray)) {
			xmlwriter_write_element($xml,'startdate',$cardDataArray['startdate']);
			xmlwriter_write_element($xml,'issuenumber',$cardDataArray['issuenumber']);
		}
		
		xmlwriter_write_raw($xml,$this->_handleCV2Address($params['Cv2Avs']));
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Sets out TxnDetails
	 *
	 * @param Array	 	$params Parameterrs need to create element
	 * @return String	$xml	Resulting TxnDetails element in XML format.
	 */
	private function _handleTxnDetails($params = array()) {
		if (empty($params)) {
			throw new Zend_Exception('TxnDetails parameters must be set');
		}
		if (!array_key_exists('merchantreference',$params) || !array_key_exists('amount',$params)) {
			throw new Zend_Exception('Must supply merchant reference & amount');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'TxnDetails');
		
		xmlwriter_write_element($xml,'merchantreference',$params['merchantreference']);
		xmlwriter_start_element($xml,'amount');
		if (array_key_exists('currency',$params)) {
			xmlwriter_write_attribute($xml,'currency',$params['currency']);
		} else {
			xmlwriter_write_attribute($xml,'currency','GBP');
		}
		xmlwriter_text($xml,$params['amount']);
		xmlwriter_end_element($xml);
		$threeDSecure = $this->_handleThreeDSecure();
		xmlwriter_write_raw($xml,$threeDSecure);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Handles our CV2 Address information
	 *
	 * @param 	String	$xml		XML we want to write to.
	 * @param 	Array 	$params		Address parameters.
	 * @return 	String	$xml		Our address element.
	 * 
	 */
	private function _handleAddress($xml, $params) {
		if (array_key_exists('street_address1',$params)) {
			xmlwriter_write_element($xml,'street_address1',$params['street_address1']);
		}
		if (array_key_exists('street_address2',$params) && !array_key_exists('street_address1',$params)) {
			throw new Zend_Exception('street_address1 not set');
		} elseif(array_key_exists('street_address2',$params)) {
			xmlwriter_write_element($xml,'street_address2',$params['street_address2']);
		}
		if (array_key_exists('street_address3',$params)  && 
				(!array_key_exists('street_address2',$params) || 
				!array_key_exists('street_address1',$params))) {
			throw new Zend_Exception('street_address1 or street_address2 not set');
		} elseif (array_key_exists('street_address3',$params)) {
			xmlwriter_write_element($xml,'street_address3',$params['street_address3']);
		}
		return $xml;
	}
	
	/**
	 * Sets our Cv2Avs check inforamtion ready to send off to DataCash
	 *
	 * @param 	Array 	$params	Adress information needed to create Cv2Avs element
	 * @return 	String	$xml	Our resulting request body for Cv2Avs element.
	 * 
	 */
	private function _handleCV2Address($params) {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'Cv2Avs');
		$xml = $this->_handleAddress($xml, $params);
		if (array_key_exists('postcode',$params)) {
			xmlwriter_write_element($xml,'postcode',$params['postcode']);
		}
		if (array_key_exists('cv2',$params)) {
			xmlwriter_write_element($xml,'cv2',$params['cv2']);
		}
		xmlwriter_write_raw($xml, $this->_handleExtendedPolicy());
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Sets out CardTxn element and returns the response.
	 *
	 * @param 	Array 	$params	Array storing data needed to make transaction.
	 * @return 	String	$xml	Our CardTxn XML element.
	 */
	private function _handleCardTxn($params = array()) {
		$cardInfo = $params['Card'];
		$card = $this->_handleCardData($params);
		if (!array_key_exists('method',$cardInfo)) {
			throw new Zend_Exception('Must supply a transaction method');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'CardTxn');
		xmlwriter_write_raw($xml,$card);
		if(array_key_exists('authcode',$cardInfo)) {
			xmlwriter_write_element($xml,'authcode', $cardInfo['authcode']);
		}
		xmlwriter_write_element($xml,'method', $cardInfo['method']);
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Checks that we have all the needed extended policy data, 
	 * throws exception if something goes wrong.
	 *
	 * @return String	$xml	XML ExtendedPolicy element
	 */
	private function _handleExtendedPolicy() {
		$this->_validate->validatePolicies();
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'ExtendedPolicy');
		xmlwriter_write_raw($xml, $this->_handlePolicy('cv2_policy'));
		xmlwriter_write_raw($xml, $this->_handlePolicy('postcode_policy'));
		xmlwriter_write_raw($xml, $this->_handlePolicy('address_policy'));
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Creates our 3DSecure XML elements, need to do 3DSecure checks
	 * using DataCash
	 *
	 * @return String	$xml	3DSecure XML result.
	 */
	private function _handleThreeDSecure() {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'ThreeDSecure');
		if(1 == $this->_datacash->threeDSecure->verify) {
			$this->_handleThreeDSecureVerify();
		} else {
			xmlwriter_write_raw($xml, xmlwriter_write_element($xml,'verify','no'));
		}
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Write 3DSecures element block.
	 * 
	 * Needed for 3DSecure verification.
	 *
	 * @return XML	$xml	3DSecure XML Element.
	 */
	private function _handleThreeDSecureVerify() {
		$this->_validate->validate3DSecure();
		$xml = xmlwriter_open_memory();
		xmlwriter_write_element($xml, 'verify', 'yes');
		xmlwriter_write_element($xml, 'merchant_url', $this->_datacash->threeDSecure->merchant_url);
		xmlwriter_write_element($xml, 'purchase_desc', $this->_datacash->threeDSecure->purchase_desc);
		xmlwriter_write_element($xml, 'purchase_datetime', date('Ymd H:i:s'));
		xmlwriter_start_element($xml, 'Browser');
		xmlwriter_write_element($xml, 'device_category', $this->_datacash->threeDSecure->device_category);
		xmlwriter_write_element($xml, 'accept_headers', $this->_datacash->threeDSecure->accept_headers);
		xmlwriter_write_element($xml, 'user_agent', $this->_datacash->browser);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Creates our extended policy XML
	 *
	 * @param 	string 	$policy		Name of the extended policy
	 * @return 	String	$xml		XML Extended policy element
	 * 
	 */
	private function _handlePolicy($policy = '') {
		if(empty($policy)) {
			throw new Zend_Exception('Invalid '.$policy .', unable to write.');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,$policy);
		xmlwriter_write_attribute($xml,'notprovided',$this->_datacash->extendedPolicy->$policy->notprovided);
		xmlwriter_write_attribute($xml,'notchecked',$this->_datacash->extendedPolicy->$policy->notchecked);
		xmlwriter_write_attribute($xml,'matched',$this->_datacash->extendedPolicy->$policy->matched);
		xmlwriter_write_attribute($xml,'notmatched',$this->_datacash->extendedPolicy->$policy->notmatched);
		xmlwriter_write_attribute($xml,'partialmatch',$this->_datacash->extendedPolicy->$policy->partialmatch);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}

	/**
	 * Writes our XML request which we need to send to DataCash
	 *
	 * @param 	String 	$auth		XML authorisation element.
	 * @param 	String 	$cardTxn	XML CardTxn element.
	 * @param 	String 	$txnDetails	XML TxnDetails element.
	 * @return 	String	$xml		XML Request element
	 */
	private function _writeRequest( $auth, $cardTxn, $txnDetails) {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Request');
		xmlwriter_write_raw($xml, $auth);
		xmlwriter_start_element($xml, 'Transaction');
		xmlwriter_write_raw($xml, $cardTxn);
		xmlwriter_write_raw($xml, $txnDetails);
		xmlwriter_end_element($xml);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Sets our transaction request
	 *
	 * @param 	Array	 	$dataArray 	Our request array, holds all the relevant data to create the request XML
	 * @return 	String		$xml		The XML request we want to send to DataCash
	 */
	function setRequest($dataArray = array(), $method='deposits') {
		$this->_validateRequest($dataArray);
		$auth = $this->_handleAuth($method);
		$cardTxn = $this->_handleCardTxn($dataArray);
		$txnDetails = $this->_handleTxnDetails($dataArray['Transaction']);
		return $this->_writeRequest($auth, $cardTxn, $txnDetails);
	}
	
	/**
	 * Sets our 3DSecure request to confirm the transaction.
	 *
	 * @param string $pares
	 * @param string $reference
	 * @param string $method
	 * @return SimpleXML
	 * 
	 */
	function set3DSecureAuthRequest($pares,$reference,$method='deposits') {
		if(is_null($pares) || empty($pares)) {
			throw new Zend_Exception('PaRes is not set');
		}
		if(strlen($reference) !== 16) {
			throw new Zend_Exception('Reference must be 16 characters long');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Request');
		$auth = $this->_handleAuth($method);
		xmlwriter_write_raw($xml, $auth);
		xmlwriter_start_element($xml, 'Transaction');
		xmlwriter_start_element($xml, 'HistoricTxn');
		xmlwriter_write_element($xml, 'reference', $reference);
		xmlwriter_start_element($xml, 'method');
		xmlwriter_write_attribute($xml,'tx_status_u','accept');
		xmlwriter_write_raw($xml,'threedsecure_authorization_request');
		xmlwriter_end_element($xml);
		xmlwriter_write_element($xml, 'pares_message', $pares);
		xmlwriter_end_element($xml);
		xmlwriter_end_element($xml);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
}