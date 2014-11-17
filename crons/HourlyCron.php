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

// * Get All Active BFX Accounts     * //
// * Create Account Objects for them * //

$userIds = $db->query("SELECT id from `BFXLendBotUsers` WHERE status >= '1' ORDER BY id ASC");
foreach($userIds as $uid){
	$accounts[$uid['id']] = new $act($uid['id']);
	/* Update their account history */	
	$accounts[$uid['id']]->bfx->bitfinex_updateHistory();
}


?>