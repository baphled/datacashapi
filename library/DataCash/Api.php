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
 * @subpackage Tests_DataCashApi
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
		if(null !== $configPath && null !== $file) {
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
	private function _setCardData($cardDataArray = array()) {
		if (empty($cardDataArray) ||
			 !array_key_exists('pan',$cardDataArray) ||
			 !array_key_exists('expirydate',$cardDataArray)) {
			throw new Zend_Exception('Need to pass array containing cards details');
		}
		
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Card');
		xmlwriter_write_element($xml,'pan',$cardDataArray['pan']);
		xmlwriter_write_element($xml,'expirydate',$cardDataArray['expirydate']);
		if( array_key_exists('startdate',$cardDataArray) && array_key_exists('issuenumber',$cardDataArray)) {
			xmlwriter_write_element($xml,'startdate',$cardDataArray['startdate']);
			xmlwriter_write_element($xml,'issuenumber',$cardDataArray['issuenumber']);
		}
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	function _cv2avsCheck($params) {
		if(!isset($this->_config->cv2avs->check)) {
			throw new Zend_Exception('No datacash cv2avs settings, please resolve.');
		}
		
		if(true === $this->_config->cv2avs->check || 
			!array_key_exists('CV2Avs',$params)) {
				throw new Zend_Exception('CV2 data not present');
		}
		return false;
	}
	
	function _setCV2Address($params) {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'CV2Avs');
		if(array_key_exists('street_address1',$params)) {
			xmlwriter_write_element($xml,'street_address1',$params['street_address1']);
		}
		if(array_key_exists('street_address2',$params) && !array_key_exists('street_address1',$params)) {
			throw new Zend_Exception('street_address1 not set');
		} elseif(array_key_exists('street_address2',$params)) {
			xmlwriter_write_element($xml,'street_address2',$params['street_address2']);
		}
		if(array_key_exists('street_address3',$params)  && 
				(!array_key_exists('street_address2',$params) || 
				!array_key_exists('street_address1',$params))) {
			throw new Zend_Exception('street_address1 or street_address2 not set');
		} elseif(array_key_exists('street_address3',$params)) {
			xmlwriter_write_element($xml,'street_address3',$params['street_address3']);
		}
		if(array_key_exists('postcode',$params)) {
			xmlwriter_write_element($xml,'postcode',$params['postcode']);
		}
	if(array_key_exists('cv2',$params)) {
			xmlwriter_write_element($xml,'cv2',$params['cv2']);
		}
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
		$card = $this->_setCardData($params);
		if(!array_key_exists('method',$params)) {
			throw new Zend_Exception('Must supply a transaction method');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'CardTxn');
		xmlwriter_write_raw($xml,$card);
		if(array_key_exists('authcode',$params)) {
			xmlwriter_write_element($xml,'authcode', $params['authcode']);
		}
		xmlwriter_write_element($xml,'method', $params['method']);
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	/**
	 * Sets out TxnDetails
	 *
	 * @param Array	 	$params Parameterrs need to create element
	 * @return String	$xml	Resulting TxnDetails element in XML format.
	 */
	private function _setTxnDetails($params = array()) {
		if(empty($params)) {
			throw new Zend_Exception('Parameters must be set');
		}
		if(!array_key_exists('merchantreference',$params) || !array_key_exists('amount',$params)) {
			throw new Zend_Exception('Must supply merchant reference & amount');
		}
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'TxnDetails');
		
		xmlwriter_write_element($xml,'merchantreference',$params['merchantreference']);
		xmlwriter_start_element($xml,'amount');
		if(array_key_exists('currency',$params)) {
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
	 * Sets our transaction request
	 *
	 * @param 	Array	 	$dataArray 	Our request array, holds all the relevant data to create the request XML
	 * @return 	String		$xml		The XML request we want to send to DataCash
	 */
	function setRequest($dataArray = array(), $method='deposit') {
		if(empty($dataArray)) {
			throw new Zend_Exception('Parameters must be in array format.');
		}
		$auth = $this->_setAuth($method);
		if(!array_key_exists('Card',$dataArray)) {
			throw new Zend_Exception('Must have card details.');
		}
		$cardTxn = $this->_setCardTxn($dataArray['Card']);
		if(!array_key_exists('Transaction',$dataArray)) {
			throw new Zend_Exception('Must have transaction details.');
		}
		$txnDetails = $this->_setTxnDetails($dataArray['Transaction']);
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml,'Request');
		xmlwriter_write_raw($xml,$auth);
		xmlwriter_start_element($xml,'Transaction');
		xmlwriter_write_raw($xml,$cardTxn);
		xmlwriter_write_raw($xml,$txnDetails);
		xmlwriter_end_element($xml);
		xmlwriter_end_element($xml);
		return xmlwriter_output_memory($xml,true);
	}
}