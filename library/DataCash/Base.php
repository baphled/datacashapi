<?php
/**
 * DataCash_Base
 * 
 * Used to abstract our DataCash commonalitise
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @package DataCashApi
 * @subpackage DataCashApi
 *
 * $LastChangedBy: yomi $
 */

abstract class DataCash_Base {
	/**
	 * Will store outhentication element
	 *
	 * @var String	Authentication XML element.
	 */
	protected $_datacash;
	
	/**
	 * Gathers or configuration settings for DataCash
	 *
	 * @param string $configPath
	 * @param string $file
	 */
	function __construct($configPath = null,$file=null) {
		if (null !== $configPath && null !== $file) {
			Zend_ConfigSettings::setUpConfig($configPath,$file);
		} else {
			Zend_ConfigSettings::setUpConfig();
		}
		$config = Zend_Registry::get('general');
		$this->_datacash = Zend_Registry::get($config->environment)->datacash;
	}
	
	function getFees() {
		return (float)$this->_datacash->fees;
	}
}