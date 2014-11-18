<?
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

$userIds = $db->query("SELECT id from `".$config['db']['prefix']."Users` WHERE status >= '1' ORDER BY id ASC");
foreach($userIds as $uid){
	$accounts[$uid['id']] = new $act($uid['id']);
	
	
	/* Run the bot to update all pending loans according to account settings */	
	$accounts[$uid['id']]->bfx->bitfinex_updateMyLends();
	
	//print_r($accounts[$uid['id']]);
}


?>