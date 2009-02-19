<?php
/**
 * DataCashXMLFixtures
 * 
 * Used to house our datacash XML requests and responses.
 * 
 * @author Yomi (baphled) Colledge <yomi@boodah.net> 2009
 * @version $Id$
 * @copyright 2009
 * @package DataCash
 * @subpackage Test_DataCash_Fixtures
 *
 * $LastChangedBy$
 */

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload ();

class DataCashXMLFixtures extends PHPUnit_Fixture {
	protected $_fixtures = array(
			array('ALIAS'=>'cardPanAndStartDate','<Card><pan>34342342424234</pan><expirydate>01/09</expirydate></Card>'),
           	array('ALIAS'=>'cardIssueNumAndStartDate', '<Card><pan>34342342424234</pan><expirydate>01/12</expirydate><startdate>02/10</startdate><issuenumber>01</issuenumber></Card>'),
           	array('ALIAS'=>'TxnDetails','<TxnDetails><merchantreference>1232452342441242</merchantreference><amount currency="GBP">100</amount></TxnDetails>'),
           	array('ALIAS'=>'txnDetailsWithEUR','<TxnDetails><merchantreference>1232452342441242</merchantreference><amount currency="EUR">100.23</amount></TxnDetails>'),
           	array('ALIAS'=>'DepositTransactionRequest','<Request><Authentication><client>clientNameDeposit</client><password>passwordDeposit</password></Authentication><Transaction><CardTxn><Card><pan>34342342424200</pan><expirydate>01/09</expirydate><CV2Avs><street_address1>Flat 7</street_address1><street_address2>89 Jumble Street</street_address2><postcode>AV12FR</postcode><cv2>123</cv2><ExtendedPolicy><cv2_policy notprovided="reject" notchecked="accept" matched="accept" notmatched="reject" partialmatch="reject"/><postcode_policy notprovided="reject" notchecked="accept" matched="accept" notmatched="reject" partialmatch="reject"/><address_policy notprovided="reject" notchecked="accept" matched="accept" notmatched="reject" partialmatch="reject"/></ExtendedPolicy></CV2Avs></Card><authcode>123123</authcode><method>auth</method></CardTxn><TxnDetails><merchantreference>1232452342441242</merchantreference><amount currency="EUR">100.23</amount></TxnDetails></Transaction></Request>'),
           	array('ALIAS'=>'CancelTransaction','<Request><Authentication><client>97868768</client><password>blahblah</password></Authentication><Transaction><HistoricTxn><reference>7986687475798</reference><method>cancel</method></HistoricTxn></Transaction></Request>')
       );
}