<?php
// Ajax Handler

// file configs //
require_once("../inc/config.php");
// db connectors and functions //
require_once("../inc/database.php");
$db = new Database();
// account functions //
require_once("../inc/Accounts.php");
$act = new Accounts();
require_once('../inc/ExchangeAPIs/bitfinex.php');


if($_REQUEST['doPause']==1){
	// flip the pause/unpause setting on an account //
	$userP = $db->iquery("UPDATE `".$config['db']['prefix']."Users` set status = (CASE
		WHEN status = 1 THEN 2
			WHEN status = 2 THEN 1
			WHEN status = 9 THEN 8
			WHEN status = 8 THEN 9
		END)
	WHERE id = '".$db->escapeStr($_REQUEST['uid'])."' LIMIT 1");
	if($userP['num']>0){
		echo '1';
	}
	else{
		echo '0';
	}
	
}
	
	



?>