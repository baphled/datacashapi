<?php
require_once realpath(dirname(__FILE__) .'/../../libs/TestHelper.php');
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
	
	function testConstrutor() {
		$this->assertNotNull(new DataCash_Api());
	}
}