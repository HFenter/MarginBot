<?php
/*//////////////////////////////
JSON Return array of stats
Needs a logged in session in order to show anything
////////////////////////////////*/
// Turn off error reporting. This fixes the neverending ajax load graphic
// for servers that have display of notices enabled.
error_reporting(0);
// file configs //
require_once("../inc/config.php");

// db connectors and functions //
require_once("../inc/database.php");
$db = new Database();

// Lets use pHpass for password encryption, to insure compatibility with older version of php 5.
require_once("../inc/PasswordHash.php");
$hasher = new PasswordHash(8, false);

require_once('../inc/ExchangeAPIs/bitfinex.php');

// account functions //
require_once("../inc/Accounts.php");
$act = new Accounts($_SESSION['userid']);

if($_REQUEST['global']==1 && ( $_SESSION['user_lvl']==8 || $_SESSION['user_lvl']==9)){
	// admin requesting global stats //
	$accounts[$_SESSION['userid']] = $act;
	// grab all the other accounts as well, load them into an array //
	$act->getAllAccounts();
	foreach($accounts as $a){
		/* Get a Full Array of Stats */	
		$dataArray[$a->userid] = $a->getStatsArray(0,$_REQUEST['cur']);
	}
	foreach($dataArray as $da){
		foreach($da as $dd){
			$thisDate = strtotime($dd['date']).'000';
			$cleanerVals[$thisDate]['swap_payment'] += $dd['swap_payment'];
			$cleanerVals[$thisDate]['dep_balance'] += $dd['dep_balance'];
			// figuring out average overwriting for new totals each loop....
			$cleanerVals[$thisDate]['average_return'] = ($cleanerVals[$thisDate]['swap_payment'] / $cleanerVals[$thisDate]['dep_balance'])*100;
		}
	}
	foreach($cleanerVals as $key=>$val){
		$cleanArray[] = [ (string)$key , (float)($val['swap_payment']), (float)$val['average_return'], (float)$val['dep_balance']];
	}
	echo json_encode($cleanArray);
}
else if($_REQUEST['userid']!=0 && ( $_SESSION['user_lvl']==8 || $_SESSION['user_lvl']==9) ){
	// single user stats request
	$act2 = new Accounts($_REQUEST['userid']);
	$accounts[$_REQUEST['userid']] = $act2;
	/* Get a Full Array of Stats */	
	$thisArray = $accounts[$_REQUEST['userid']]->getStatsArray(0,$_REQUEST['cur']);
	
	foreach($thisArray as $ta){
		$tm = (string)(strtotime($ta['date'])).'000';
		$cleanArray[] = [ (string)$tm , (float)($ta['swap_payment']), (float)$ta['average_return'], (float)$ta['dep_balance'] ];
	}
	echo json_encode($cleanArray);
}
else if($_SESSION['userid']){
	// must not be an admin, can only see their own stats
	$accounts[$_SESSION['userid']] = $act;
	/* Get a Full Array of Stats */	
	$thisArray = $accounts[$_SESSION['userid']]->getStatsArray(0,$_REQUEST['cur']);
	
	foreach($thisArray as $ta){
		$tm = (string)(strtotime($ta['date'])).'000';
		$cleanArray[] = [ (string)$tm , (float)($ta['swap_payment']), (float)$ta['average_return'], (float)$ta['dep_balance'] ];
	}
	echo json_encode($cleanArray);
}

?>
