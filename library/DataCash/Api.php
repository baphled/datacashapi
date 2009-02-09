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
	function getAuth($type = 'deposit') {
		$xml = xmlwriter_open_memory();
		xmlwriter_start_element($xml, 'Authentication');
		xmlwriter_write_element($xml, 'client', $this->_config->$type->client);
		xmlwriter_write_element($xml, 'password', $this->_config->$type->password);
		xmlwriter_end_element($xml);

		return xmlwriter_output_memory($xml, true);
	}
	
	function setCardData($cardDataArray = array()) {
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
	
	function setRequest($dataArray = array()) {
		if(empty($dataArray)) {
			throw new Zend_Exception('Parameters must be in array format.');
		}
	}
}