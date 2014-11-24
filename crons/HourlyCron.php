<?
/*//////////////////////////////
  Hourly Cron Job				
  Should be run once an hour	
  Ex: 						
  5 * * * * wget -qO- http://yoursite.com/MarginBot/HourlyCron.php >/dev/null 2>&1
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



// * Get All Active BFX Accounts     * //
// * Create Account Objects for them * //

$userIds = $db->query("SELECT id from `".$config['db']['prefix']."Users` WHERE status >= '1' ORDER BY id ASC");
foreach($userIds as $uid){
	$accounts[$uid['id']] = new $act($uid['id']);
	/* Update their account history */	
	$accounts[$uid['id']]->bfx->bitfinex_updateHistory();
	// mark it in the crons table so we know its working
	$cronUpdates = $db->iquery("INSERT into `".$config['db']['prefix']."CronRuns` (`cron_id`, `lastrun`, `details`) VALUES ('1', NOW(), 'Updated User ".$uid['id']." History')");	
}


?>