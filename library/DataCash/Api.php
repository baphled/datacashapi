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
class DataCash_Api {
	/**
	 * Will store outhentication element
	 *
	 * @var String	Authentication XML element.
	 */
	protected $_config;
	
	function __construct($configPath = null,$file=null) {
		if (null !== $configPath && null !== $file) {
			Zend_ConfigSettings::setUpConfig($configPath,$file);
		} else {
			Zend_ConfigSettings::setUpConfig();
		}
		$config = Zend_Registry::get('general');
		$this->_config = Zend_Registry::get($config->environment)->datacash;
	}
	
	/**
	 * sets up and gets our authentication information from our
	 * environments configuration file.
	 * 
	 * Will retrieve the appropriate credentials depending on whether the transaction
	 * to be is a withdrawal or a deposit.
	 *
	 * @param 	String 	$type	The type of request we are about to make.
	 * @return 	String	$xml	Our resulting XML element
	 */
	private function _setAuth($type = 'deposit') {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Authentication');
		xmlwriter_write_element($xml, 'client', $this->_config->$type->client);
		xmlwriter_write_element($xml, 'password', $this->_config->$type->password);
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Gets our card information and turns the data into the 
	 * needed XML element.
	 *
	 * @param 	Array 	$cardDataArray
	 * @return 	String	XML element.
	 */
	private function _setCardData($params = array()) {
		if (!array_key_exists('Card',$params)) {
			throw new Zend_Exception('No card data.');
		}
		
		$cardDataArray = $params['Card'];
		if (empty($cardDataArray) ||
			 !array_key_exists('pan',$cardDataArray) ||
			 !array_key_exists('expirydate',$cardDataArray)) {
			throw new Zend_Exception('Need to pass array containing cards details');
		}
		
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Card');
		xmlwriter_write_element($xml,'pan',$cardDataArray['pan']);
		xmlwriter_write_element($xml,'expirydate',$cardDataArray['expirydate']);
		if ( array_key_exists('startdate',$cardDataArray) && array_key_exists('issuenumber',$cardDataArray)) {
			xmlwriter_write_element($xml,'startdate',$cardDataArray['startdate']);
			xmlwriter_write_element($xml,'issuenumber',$cardDataArray['issuenumber']);
		}
		if (!array_key_exists('CV2Avs',$params)) {
			throw new Zend_Exception('No datacash cv2avs settings, please resolve.');
		} 
		xmlwriter_write_raw($xml,$this->_cv2avsCheck($params));
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Checks to determine whther we need to do a Cv2Av2 check.
	 *
	 * @param unknown_type $params
	 * @return string	$xml or false if not doing cv2 checks.
	 * 
	 */
	function _cv2avsCheck($params = array()) {
		if (empty($params) || !isset($this->_config->cv2avs->check)) {
			throw new Zend_Exception('No datacash cv2avs settings, please resolve.');
		} 
		if (true === $this->_config->cv2avs->check && !array_key_exists('CV2Avs', $params)) {
			throw new Zend_Exception('CV2 data not present');
		}
		return $this->_setCV2Address($params['CV2Avs']);
	}
	
	/**
	 * Sets our CV2Avs check inforamtion ready to send off to DataCash
	 *
	 * @param array $params
	 * @return string	$xml	Our resulting request body for Cv2Avs element.
	 * 
	 */
	private function _setCV2Address($params) {
		if (empty($params)) {
			throw new Zend_Exception('no Address details.');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'CV2Avs');
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
	private function _setCardTxn($params = array()) {
		$cardInfo = $params['Card'];
		$card = $this->_setCardData($params);
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
	 * Checks our policy
	 *
	 * @param string $policy
	 * @return bool
	 */
	private function _checkPolicy($policy = '') {
		if(empty($policy)) {
			throw new Zend_exception('Policy must be valid');
		}
		if (0 !== $this->_config->extendedPolicy->set && 
			false === ($this->_policyCheckSet($policy) && $this->_policyEmpty($policy))) {
			return false;
		}
		return true;
	}
	
	/**
	 * Determines whether our policy data is empty or not
	 *
	 * @param 	string 	$policy		Name of the extended policy
	 * @return 	bool				True is valid, false otherwise
	 */
	private function _policyEmpty($policy) {
		if(empty($this->_config->extendedPolicy->$policy->notprovided) ||
			 empty($this->_config->extendedPolicy->$policy->notchecked) ||
			 empty($this->_config->extendedPolicy->$policy->matched) ||
			 empty($this->_config->extendedPolicy->$policy->notmatched) ||
			 empty($this->_config->extendedPolicy->$policy->partialmatch)) {
			 	return false;
		}
		return true;
	}
	
	/**
	 * Determines whether our policy data is set or not
	 *
	 * @param 	string 	$policy		Name of the extended policy
	 * @return 	bool				True is valid, false otherwise
	 */
	private function _policyCheckSet($policy) {
		if(!isset($this->_config->extendedPolicy->$policy->notprovided) ||
			 !isset($this->_config->extendedPolicy->$policy->notchecked) || 
			 !isset($this->_config->extendedPolicy->$policy->matched) || 
			 !isset($this->_config->extendedPolicy->$policy->notmatched) || 
			 !isset($this->_config->extendedPolicy->$policy->partialmatch)) {
			 	return false;
			 }
		return true;
	}
	
	/**
	 * Creates our extended policy XML
	 *
	 * @param 	string 	$policy		Name of the extended policy
	 * @return 	bool				True is valid, false otherwise
	 */
	private function _writePolicy($policy = '') {
		if(empty($policy)) {
			throw new Zend_Exception('Invalid '.$policy .', unable to write.');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,$policy);
		xmlwriter_write_attribute($xml,'notprovided',$this->_config->extendedPolicy->$policy->notprovided);
		xmlwriter_write_attribute($xml,'notchecked',$this->_config->extendedPolicy->$policy->notchecked);
		xmlwriter_write_attribute($xml,'matched',$this->_config->extendedPolicy->$policy->matched);
		xmlwriter_write_attribute($xml,'notmatched',$this->_config->extendedPolicy->$policy->notmatched);
		xmlwriter_write_attribute($xml,'partialmatch',$this->_config->extendedPolicy->$policy->partialmatch);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	/**
	 * Checxks that we have all the needed extended policy data, throws exception if something goes wrong.
	 *
	 * @return bool
	 */
	private function _handleExtendedPolicy() {
		if (false === $this->_checkPolicy('cv2_policy')) {
			throw new Zend_Exception('Unable to set cv2_policy, all cv2 policy settings should be accessible.');
		}
		if (false === $this->_checkPolicy('postcode_policy')) {
			throw new Zend_Exception('Unable to set postcode_policy, all policy postcode policy settings should be accessible.');
		}
		if (false === $this->_checkPolicy('address_policy')) {
			throw new Zend_Exception('Unable to set address_policy, all policy address policy settings should be accessible.');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'ExtendedPolicy');
		xmlwriter_write_raw($xml, $this->_writePolicy('cv2_policy'));
		xmlwriter_write_raw($xml, $this->_writePolicy('postcode_policy'));
		xmlwriter_write_raw($xml, $this->_writePolicy('address_policy'));
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Sets out TxnDetails
	 *
	 * @param Array	 	$params Parameterrs need to create element
	 * @return String	$xml	Resulting TxnDetails element in XML format.
	 */
	private function _setTxnDetails($params = array()) {
		if (empty($params)) {
			throw new Zend_Exception('Parameters must be set');
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
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Creates our 3DSecure XML elements, need to do 3DSecure checks
	 * using DataCash
	 *
	 * @return String	$xml	3DSecure XML result.
	 */
	private function _threeDSecure() {
		if(!isset($this->_config->threeDSecure->verify)) {
			throw new Zend_Exception('Need to set 3dSecure property.');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'ThreeDSecure');
		if(1 == $this->_config->threeDSecure->verify) {
			xmlwriter_write_element($xml,'verify','yes');
			xmlwriter_write_element($xml,'merchant_url',$this->_config->threeDSecure->merchant_url);
			xmlwriter_write_element($xml,'purchase_desc',$this->_config->threeDSecure->purchase_desc);
			xmlwriter_write_element($xml,'purchase_datetime',date('Ymd H:i:s'));
			xmlwriter_start_element($xml,'Browser');
			xmlwriter_write_element($xml,'device_category',$this->_config->threeDSecure->device_category);
			xmlwriter_write_element($xml,'accept_headers',$this->_config->threeDSecure->accept_headers);
			xmlwriter_write_element($xml,'user_agent',$_SERVER['HTTP_USER_AGENT']);
			xmlwriter_end_element($xml);
		} else {
			xmlwriter_write_element($xml,'verify','no');
		}
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
	
	/**
	 * Sets our transaction request
	 *
	 * @param 	Array	 	$dataArray 	Our request array, holds all the relevant data to create the request XML
	 * @return 	String		$xml		The XML request we want to send to DataCash
	 */
	function setRequest($dataArray = array(), $method='deposit') {
		if (!isset($this->_config->extendedPolicy->set)) {
			throw new Zend_Exception('Must have extended policy setting in config file.');
		}
		if (empty($dataArray)) {
			throw new Zend_Exception('Parameters must be in array format.');
		}
		$auth = $this->_setAuth($method);
		if (!array_key_exists('Card',$dataArray)) {
			throw new Zend_Exception('Must have card details.');
		}
		$cardTxn = $this->_setCardTxn($dataArray);
		if (!array_key_exists('Transaction',$dataArray)) {
			throw new Zend_Exception('Must have transaction details.');
		}
		$txnDetails = $this->_setTxnDetails($dataArray['Transaction']);
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'Request');
		xmlwriter_write_raw($xml,$auth);
		xmlwriter_start_element($xml,'Transaction');
		xmlwriter_write_raw($xml,$cardTxn);
		xmlwriter_write_raw($xml,$txnDetails);
		xmlwriter_write_raw($xml,$this->_threeDSecure());
		xmlwriter_end_element($xml);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
}