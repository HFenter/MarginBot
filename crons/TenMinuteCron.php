<?php
/*//////////////////////////////
  10 Minute Cron Job				
  Should be run very 10 minutes	
  Ex: 						
  01,11,21,31,41,51 * * * * wget -qO- http://yoursite.com/MarginBot/TenMinuteCron.php >/dev/null 2>&1
////////////////////////////////*/

// file configs //
require_once("../inc/config.php");

// db connectors and functions //
require_once("../inc/database.php");
$db = new Database();

// account functions //
require_once("../inc/Accounts.php");
$act = new Accounts();

require_once('../inc/ExchangeAPIs/bitfinex.php');

// * Get All Active BFX Accounts     * //
// * Create Account Objects for them * //

// check that the crons database exists //
$cronsTableSQL = '
	CREATE TABLE IF NOT EXISTS `'.$config['db']['prefix'].'CronRuns` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `cron_id` tinyint(1) NOT NULL,
	  `lastrun` datetime NOT NULL,
	  `details` varchar(256) NOT NULL,
	  PRIMARY KEY (`id`)
	)';
$rt = $db->iquery($cronsTableSQL);


$userIds = $db->query("SELECT id from `".$config['db']['prefix']."Users` WHERE status >= '1' AND ( status != '2' AND  status != '8' )  ORDER BY id ASC");
foreach($userIds as $uid){
	$accounts[$uid['id']] = new $act($uid['id']);
	/* Run the bot to update all pending loans according to account settings */	
	$accounts[$uid['id']]->bfx->bitfinex_updateMyLends();
	// mark it in the crons table so we know its working
	$cronUpdates = $db->iquery("INSERT into `".$config['db']['prefix']."CronRuns` (`cron_id`, `lastrun`, `details`) VALUES ('2', NOW(), 'Updated User ".$uid['id']." Current Loans')");	
}


?>