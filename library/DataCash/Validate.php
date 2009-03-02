<?php
class DataCash_Validate extends DataCash_Base {
	/**
	 * Gathers or configuration settings for DataCash
	 *
	 * @param string $configPath
	 * @param string $file
	 */
	function __construct($configPath = null,$file=null) {
		parent::__construct($configPath,$file);
	}

	/**
	 * Makes sure we have set the nessary 3DSecure properties
	 * in our configuration file.
	 *
	 */
	function validate3DSecure() {
		if(!isset($this->_datacash->threeDSecure->merchant_url) ||
			!isset($this->_datacash->threeDSecure->purchase_desc) ||
			!isset($this->_datacash->threeDSecure->device_category) ||
			!isset($this->_datacash->threeDSecure->accept_headers)) {
				throw new Zend_Exception('Need to set 3DSecure properies.');
			}
		if(!isset($this->_datacash->threeDSecure->verify)) {
			throw new Zend_Exception('Need to set 3DSecure verify property.');
		}
	}
	
	/**
	 * Determines whether our policy data is empty or not
	 *
	 * @param 	string 	$policy		Name of the extended policy
	 * @return 	bool				True is valid, false otherwise
	 */
	private function _policyEmpty($policy) {
		if(empty($this->_datacash->extendedPolicy->$policy->notprovided) ||
			 empty($this->_datacash->extendedPolicy->$policy->notchecked) ||
			 empty($this->_datacash->extendedPolicy->$policy->matched) ||
			 empty($this->_datacash->extendedPolicy->$policy->notmatched) ||
			 empty($this->_datacash->extendedPolicy->$policy->partialmatch)) {
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
		if(!isset($this->_datacash->extendedPolicy->$policy->notprovided) ||
			 !isset($this->_datacash->extendedPolicy->$policy->notchecked) || 
			 !isset($this->_datacash->extendedPolicy->$policy->matched) || 
			 !isset($this->_datacash->extendedPolicy->$policy->notmatched) || 
			 !isset($this->_datacash->extendedPolicy->$policy->partialmatch)) {
			 	return false;
			 }
		return true;
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
		if (0 !== $this->_datacash->extendedPolicy->set && 
			false === ($this->_policyCheckSet($policy) || $this->_policyEmpty($policy))) {
			return false;
		}
		return true;
	}
	
	/**
	 * Validates our parameters making sure we have card information.
	 *
	 * @param array $params
	 */
	function validateCard($params) {
		if (!array_key_exists('Card',$params)) {
			throw new Zend_Exception('No card data.');
		}
		$cardDataArray = $params['Card'];
		if (empty($cardDataArray) ||
			 !array_key_exists('pan',$cardDataArray) ||
			 !array_key_exists('expirydate',$cardDataArray)) {
			throw new Zend_Exception('Need to pass array containing cards details');
		}
		if (!array_key_exists('Cv2Avs',$params)) {
			throw new Zend_Exception('No datacash cv2avs settings, please resolve.');
		} 
	}
	
	/**
	 * Checks to see if we need to do a CV2 check, if so validates our params.
	 *
	 * @param array $params
	 * @return string	$xml or false if not doing cv2 checks.
	 * 
	 */
	function validateCV2Avs($params = array()) {
		if (empty($params) || !isset($this->_datacash->cv2avs->check)) {
			throw new Zend_Exception('No datacash cv2avs settings, please resolve.');
		} 
		if (true === $this->_datacash->cv2avs->check && !array_key_exists('Cv2Avs', $params)) {
			throw new Zend_Exception('CV2 data not present');
		}
	}

	/**
	 * Validate our policies, making sure that they are all set
	 *
	 */
	function validatePolicies() {
		if (false === ($this->_checkPolicy('cv2_policy') ||
			$this->_checkPolicy('postcode_policy') ||
			$this->_checkPolicy('address_policy'))) {
			throw new Zend_Exception('All policies settings should be accessible.');
		}
	}
	
	/**
	 * Validates our requests parameters.
	 *
	 * @param array $dataArray
	 */
	function validateRequest($dataArray) {
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
}