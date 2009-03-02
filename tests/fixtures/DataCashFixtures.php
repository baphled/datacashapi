<?php
/**
 * DataCashFixtures
 * 
 * Used to store our datacash related fixtures.
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

class DataCashFixtures extends PHPUnit_Fixture {
	protected $_fixtures = array(
           array('ALIAS' => 'invalidCardRequestPanMispelt',
           		'Card' => array('pa'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'auth'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR')),
           array('ALIAS' => 'invalidCardRequestExpiryMispelt',
           		'Card' => array( 'pa'=>34342342424234,'expirydat'=>'01/09','authcode'=>123123,'method'=>'auth'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR')),
           array('ALIAS' =>'NoMethodRequest',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/12','startdate'=>'02/10','issuenumber'=>'01','authcode'=>123123,),
           		'Transaction' => array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR')),
           array('ALIAS' =>'RequestWithIssueNumAndStartDate', 
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/12','startdate'=>'02/10','issuenumber'=>'01','authcode'=>123123,'method'=>'auth'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'CompleteDepositRequest',
           		'Card' =>array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'auth'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'CompleteWithdrawalRequest',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'TestCv2AvsRequest',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'TestCv2AvsNoStreetAddress1Request',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'TestCv2AvsSingleStreetAddressRequest',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'TestCv2AvsNoStreetAddress2or3Request',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'TestCv2AvsNoPostcode3Request',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcoder'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'TestCv2AvsNoAddressRequest',
           		'Card' => array('pan'=>34342342424234,'expirydate'=>'01/09','authcode'=>123123,'method'=>'refund'),
           		'Transaction' =>array('merchantreference'=>'1232452342441242','amount'=>100.23,'currency'=>'EUR'),
           		'Cv2Avs' =>array('postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'AUVisaComplete',
           		'Card' => array('pan'=>'1000070000000001','expirydate'=>'02/11','method'=>'auth'),
           		'Transaction' =>array('merchantreference'=>'123523942494242','amount'=>100.23,'currency'=>'GBP'),
           		'Cv2Avs' =>array('street_address1'=>'Flat 7','street_address2'=>'89 Jumble Street','address_address3'=>'MyTown','postcode'=>'AV12FR','cv2'=>'123')),
           array('ALIAS' => 'ExtendedPolicyCheck',
           		'Card' => array('pan'=>'4444333322221111','expirydate'=>'02/11','method'=>'auth'),
           		'Transaction' =>array('merchantreference'=>'2341939335345','amount'=>1.98),
           		'Cv2Avs' =>array('street_address1'=>'Flat 12/3','street_address2'=>'45 Main Road','street_address3'=>'Sometown','street_address4'=>'Somecounty','postcode'=>'A98 7AA','cv2'=>'444'))
       );
}