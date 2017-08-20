<?
// file configs //
require_once("inc/config.php");
$alert = array();
$warning = array();

$configFile = getcwd().'/inc/config.php';
// This is a submit, lets do some stuff //
if($_REQUEST['doInstall']==1){
	//Install Step 1
	// First thing, lets make sure inc/config.php is writtable, if not, none of this is gonna work, so fail out //
	
	
	if (is_writable($configFile)) {
		// its writable, nifty
		
	
		// lets check their database out
		$mysqli = new mysqli($_REQUEST['installDBHost'], $_REQUEST['installDBUser'], $_REQUEST['installDBPassword'], $_REQUEST['installDBName']);
		if ($mysqli->connect_errno) {
			$warning[] = "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error.'<br>Please Fix Your Database Settings.';
		}
		else{
			$tablePre = $mysqli->real_escape_string($_REQUEST['installDBPrefix']);			
			// database connection worked, lets try to make some tables //
			$trackingSQL = 'CREATE TABLE `'.$tablePre.'Tracking` (
			  `id` int(12) NOT NULL AUTO_INCREMENT,
			  `user_id` smallint(4) DEFAULT NULL,
			  `trans_cur` varchar(10) DEFAULT NULL,
			  `trans_id` int(12) DEFAULT NULL,
			  `date` date DEFAULT NULL,
			  `dep_balance` decimal(12,8) DEFAULT NULL,
			  `swap_payment` decimal(12,8) DEFAULT NULL,
			  `average_return` decimal(8,6) DEFAULT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `uniquieKeys` (`user_id`,`trans_id`,`trans_cur`) USING BTREE
			) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1';
			
			if ( !$mysqli->query($trackingSQL) ){
				 $warning[] = "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
				}
			$usersSQL = 'CREATE TABLE `'.$tablePre.'Users` (
			  `id` int(4) NOT NULL AUTO_INCREMENT,
			  `name` varchar(256) DEFAULT NULL,
			  `email` varchar(256) DEFAULT NULL,
			  `password` varchar(256) DEFAULT NULL,
			  `bfxapikey` varchar(64) DEFAULT NULL,
			  `bfxapisec` varchar(64) DEFAULT NULL,
			  `status` tinyint(1) DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1';
			if ( !$mysqli->query($usersSQL) ){
				 $warning[] = "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
				}		
			
			$varsSQL = 'CREATE TABLE `'.$tablePre.'Vars` (
				  `id` smallint(4) NOT NULL AUTO_INCREMENT,
				  `userid` smallint(4) DEFAULT NULL,
				  `curType` varchar(10) DEFAULT NULL,
				  `minlendrate` varchar(12) DEFAULT NULL,
				  `spreadlend` varchar(12) DEFAULT NULL,
				  `USDgapBottom` varchar(12) DEFAULT NULL,
				  `USDgapTop` varchar(12) DEFAULT NULL,
				  `thirtyDayMin` varchar(12) DEFAULT NULL,
				  `highholdlimit` varchar(12) DEFAULT NULL,
				  `highholdamt` varchar(12) DEFAULT NULL,
				  `extractAmt` varchar(12) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `unqType` (`userid`,`curType`)
				) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=latin1';
			if ( !$mysqli->query($varsSQL) ){
				 $warning[] = "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
				}
				
			$cronsTableSQL = '
				CREATE TABLE  `'.$tablePre.'CronRuns` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `cron_id` tinyint(1) NOT NULL,
				  `lastrun` datetime NOT NULL,
				  `details` varchar(256) NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1';
			if ( !$mysqli->query($cronsTableSQL) ){
				 $warning[] = "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
				}
			$pairsTableSQL = '
				CREATE TABLE  `'.$tablePre.'CurPairs` (
				  `id` smallint(4) NOT NULL AUTO_INCREMENT,
				  `curSym` varchar(12) DEFAULT NULL,
				  `curName` varchar(100) DEFAULT NULL,
				  `status` tinyint(1) DEFAULT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1';
			if ( !$mysqli->query($pairsTableSQL) ){
				 $warning[] = "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
				}
			else{
				//prefill pairs table
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('1', 'USD', 'US Dollars', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('2', 'BTC', 'Bitcoin', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('3', 'IOT', 'Iota', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('4', 'ETH', 'Ethereum', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('5', 'OMG', 'OmiseGO', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('6', 'BCH', 'Bcash', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('7', 'EOS', 'EOS', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('8', 'ETC', 'Ethereum Classic', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('9', 'DSH', 'Dash', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('10', 'XMR', 'Monero', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('11', 'ZEC', 'Zcash', '1')");
				$mysqli->query("INSERT INTO `".$tablePre."CurPairs` VALUES ('12', 'XRP', 'Ripple', '1')");
				
			}
			
			if(count($warning)==0){
				// tables seemed to create ok, lets write the config file //
				
$configData = '<?php
date_default_timezone_set(\'America/Los_Angeles\');
setlocale(LC_MONETARY, \'en_US\');
session_start();
require_once(\'version_info.php\');

// Local Database Connection Info //
$config[\'db\'][\'host\'] = \''.$_REQUEST['installDBHost'].'\';
$config[\'db\'][\'dbname\'] = \''.$_REQUEST['installDBName'].'\';
$config[\'db\'][\'dbuser\'] = \''.$_REQUEST['installDBUser'].'\';
$config[\'db\'][\'dbpass\'] = \''.$_REQUEST['installDBPassword'].'\';

// this is included in front of each database table name
$config[\'db\'][\'prefix\'] = \''.$_REQUEST['installDBPrefix'].'\';

//Local Admin Email //
$config[\'admin_email\'] = \''.$_REQUEST['installEmail'].'\';

// Current Fees Charged by BFX for Margin Swaps (15% as of Nov. 2014)
$config[\'curFeesBFX\'] = '.$_REQUEST['installBFXFees'].';
?>';
			
				
				if (!$handle = fopen($configFile, 'w')) {
					 $warning[] = "Could Not open file ($configFile)";
					 //exit;
				}
				// Write to the config file //
				if (fwrite($handle, $configData) === FALSE) {
					$warning[] = "Cannot write to file ($configFile)";
					//exit;
				}
				else{
					// Looks like it worked! //
					$alert[] = "Database Settings Saved and Tables Created.";
					$_REQUEST['doInstall'] = 2;
				}
				
				fclose($handle);
				
				
			}
			
			
		}
		
	} else {
		$warning[] = "The Configuration File Doesn't Appear To Be Writable, please set ".$configFile." writable (chmod 777 ".$configFile." )";
	}
}
else if($_REQUEST['doInstall']==2){
	
	// db connectors and functions //
	require_once("inc/database.php");
	$db = new Database();

	// Lets use pHpass for password encryption, to insure compatibility with older version of php 5.
	require_once("inc/PasswordHash.php");
	$hasher = new PasswordHash(8, false);
	
	require_once("inc/ExchangeAPIs/bitfinex.php");
	
	/*
	installAdminUser
	installAdminEmail
	installAdminPassword
	installAdminBFXKey
	installAdminBFXSec
	*/
	
	
	// Check Everything Submitted to see if its valid //
	if(strlen($_REQUEST['installAdminUser']) < 3){$warning[] = 'Account Name must be at least 3 characters long';}
	if(strlen($_REQUEST['installAdminBFXKey']) != 43){$warning[] = 'Bitfinex API Keys are 43 Characters Long';}
	if(strlen($_REQUEST['installAdminBFXSec']) != 43){$warning[] = 'Bitfinex API Secrets are 43 Characters Long';}
	// Passwords should never be longer than 72 characters to prevent DoS attacks
	if (strlen($_REQUEST['installAdminPassword']) > 72){$warning[] = 'Passwords must be less than 72 Characters';}
	if(count($warning)==0){
		// Check it doesn't already exits...
		$userCheck = $db->query("SELECT name, bfxapikey FROM `".$config['db']['prefix']."Users` WHERE (name = '".$db->escapeStr($_REQUEST['installAdminUser'])."' OR bfxapikey = '".$db->escapeStr($_REQUEST['installAdminBFXKey'])."' ) LIMIT 1");
		if (count($userCheck) ==  1) {
			if($userCheck[0]['name'] == $_REQUEST['installAdminUser'] ){
				$warning[] = 'This user name already exists in our database';
			}
			if($userCheck[0]['bfxapikey'] == $_REQUEST['installAdminBFXKey'] ){
				$warning[] = 'This bitfinex key already exists in our database';
			}
		}
	}
	if(count($warning)==0){
		// test their bfx key and sec to see if we can pull data //
		$bfxTest = new Bitfinex(0, $_REQUEST['installAdminBFXKey'], $_REQUEST['installAdminBFXSec']);
		$bt = $bfxTest->bitfinex_get('account_infos');
		if($bt[0]['fees'][0]['pairs']!=''){
			// looks good //
			// Create The Account //
			
			// hash the password
			$passEnc = $hasher->HashPassword($_REQUEST['installAdminPassword']);
			// write account to db
			$sql = "INSERT into `".$config['db']['prefix']."Users` (`name`,`email`,`password`,`bfxapikey`,`bfxapisec`,`status` )
				 VALUES
				 ( '".$db->escapeStr($_REQUEST['installAdminUser'])."', '".$db->escapeStr($_REQUEST['installAdminEmail'])."', '".$db->escapeStr($passEnc)."',
				 '".$db->escapeStr($_REQUEST['installAdminBFXKey'])."', '".$db->escapeStr($_REQUEST['installAdminBFXSec'])."', '9' )";
			$newUser = $db->iquery($sql);
			
			if($newUser['id']!=0){
				//  Set default settings for the account //
				$sql = "INSERT into `".$config['db']['prefix']."Vars` (`userid`,`curType`,`minlendrate`,`spreadlend`,`USDgapBottom`,`USDgapTop`,`thirtyDayMin`,`highholdlimit`,`highholdamt` )
					 VALUES
					 ( '".$newUser['id']."', 'USD', '0.0650', '3', '25000', '100000', '0.1500', '0.3500', '0' )";
				$newActSettings = $db->iquery($sql);
				$sql = "INSERT into `".$config['db']['prefix']."Vars` (`userid`,`curType`,`minlendrate`,`spreadlend`,`USDgapBottom`,`USDgapTop`,`thirtyDayMin`,`highholdlimit`,`highholdamt` )
					 VALUES
					 ( '".$newUser['id']."', 'BTC', '0.0150', '2', '2', '10', '0.1500', '0.3500', '0' )";
				$newActSettings = $db->iquery($sql);
				
				// Success, tell them they need to login now //
				$alert[] = '<strong>User '.$_REQUEST['new_name'].'</strong> Account Created';
				$_REQUEST['doInstall']=3;

			}
		}
		else{
			if(stristr($bt['message'], "permission")){
				//API Key Not Set up for correct permissions
				$warning[] = 'Your Bitfinex API Key doesn\'t seem to have the correct permissions.<br>Make sure you allow the key "Read" access to Account Info, Account History, Orders, Margin Trading, Margin Funding, and Wallets.<br>Make sure you allow the key "Write" access to Margin Funding and Wallets.<br>Do NOT allow the key any access to Withdraw for security reasons.';
			}
			else{
				$warning[] = 'Something doesn\'t seem to be working.  Most likely you haven\'t set up your API Key correctly.<br>Make sure you allow the key "Read" access to Account Info, Account History, Orders, Margin Trading, Margin Funding, and Wallets.<br>Make sure you allow the key "Write" access to Margin Funding and Wallets.<br>Do NOT allow the key any access to Withdraw for security reasons.';
			}
			$_REQUEST['doInstall']==2;
		}
	}
	else{
		// something wasn't right, make them fix it....
		$warning[] = 'Something doesn\'t seem to be working.  Most likely you haven\'t set up your API Key correctly.<br>Make sure you allow the key "Read" access to Account Info, Account History, Orders, Margin Trading, Margin Funding, and Wallets.<br>Make sure you allow the key "Write" access to Margin Funding and Wallets.<br>Do NOT allow the key any access to Withdraw for security reasons.';
		$_REQUEST['doInstall']==2;
	}
}
	


// general functions //
require_once("inc/General.php");
$gen = new General();

echo '
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>'.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].' - Install</title>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jquery.formatCurrency-1.4.0.min.js"></script>
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/bootstrap-theme.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/styles.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">'.$config['app_name'].' '.$config['app_version'].'</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
			<li style="padding:8px 30px 0px 30px;">
            	<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#signUpModal">
                  Sign Up For Bitfinex 10% Off Fees
                </button>
            </li>
            <li style="padding:8px 30px 0px 30px;">
            	<button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#donateModal">
                  Support Development
                </button>
            </li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Feedback <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="mailto:'.$config['app_support_email'].'">Email</a></li>
                <li><a href="'.$config['app_support_url'].'">Forums</a></li>
              </ul>
            </li>
          </ul>
		  
        </div><!--/.nav-collapse -->
      </div>
    </nav>';
$gen->showSiteModals();
	
echo '	
<div class="container">
';



$gen->showWarnings($warning);
$gen->showAlerts($alert);

/*
We need to grab some information about the local system, write it to a config file, then create all the databases
*/

if($_REQUEST['doInstall']<=1){
	// set installDBHost to localhost by default //
	$_REQUEST['installDBHost'] = (!$_REQUEST['installDBHost'] ? 'localhost':$_REQUEST['installDBHost']);
	$_REQUEST['installDBPrefix'] = (!$_REQUEST['installDBPrefix'] ? 'BFXLendBot_':$_REQUEST['installDBPrefix']);
	
	echo '
	
		<div class="panel panel-default">
				<div class="panel-heading">Lets Install '.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].'</div>
				<div class="panel-body table-responsive">
					It looks like you haven\'t run through the '.$config['app_name'].' '.$config['app_version'].' Installer yet.  Lets do so now!
				</div>
			</div>
	
		<form action="install.php" method="post" autocomplete="off" >
			<input type="hidden" name="doInstall" value="1">
		
		<div class="panel panel-default">
				<div class="panel-heading">Step 1 - Local Server Settings</div>
				<div class="panel-body table-responsive">

					<table class="table table-striped table-bordered">
						<thead>
						<tr>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Host" data-content="Database Host Address.   Unless you\'re doing something weird, this is probably \'localhost\'.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Database Host
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Name" data-content="name of the database you\'ll be using.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Database Name
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database User" data-content="Database user account.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Database User
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Password" data-content="Database User Password.  Make sure this is a good secure password.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Database Password
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Prefix" data-content="This will be appended to the front of each database table. Useful for servers with more than 1 install of the bot. (Ex: BFXLendBot1_ would create the user table as BFXLendBot1_Users)">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Database Prefix
							</th>
						</tr>
						</thead>
						<tr>
							<td class="mid">
								<input type="text" id="inputDBHost" class="form-control"  required="" autofocus="" name="installDBHost"  autocomplete="off" value="'.$_REQUEST['installDBHost'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputDBName" class="form-control" placeholder="Database Name" autofocus="" name="installDBName"  autocomplete="off" value="'.$_REQUEST['installDBName'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputDBUser" class="form-control" placeholder="Database User" autofocus="" name="installDBUser"  autocomplete="off" value="'.$_REQUEST['installDBUser'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputDBPassword" class="form-control" placeholder="Database Password" autofocus="" name="installDBPassword"  autocomplete="off" value="'.$_REQUEST['installDBPassword'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputDBPrefix" class="form-control" placeholder="BFXLendBot_" autofocus="" name="installDBPrefix"  autocomplete="off" value="'.$_REQUEST['installDBPrefix'].'">
							</td>
						</tr>
						<tr>
							<th class="mid">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Site Email Address" data-content="This is shown as the error support address.  Its not really used for much yet.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Site Email Address
							</th>
							<th class="mid">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Bitfinex Margin Fees" data-content="The fee Bitfinex charges for margin lending.  It\'s used to do various calculations.  You can find the latest fee at https://www.bitfinex.com/pages/fees in the \'SWAPS\' section.  As of Nov 2014 it is 15%.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Margin Fee						
							</th>
							<td class="mid" colspan="3" rowspan="2">
								<button class="btn btn-lg btn-primary btn-block" type="submit" style="width:200px;margin: 20px auto;">Go To Step 2</button>
							</td>
						</tr>

						<tr>					
							<td class="mid">
								<input type="text" id="inputSiteEmail" class="form-control" placeholder="you@yourdomain.com" required="" autofocus="" name="installEmail"  autocomplete="off" value="'.$_REQUEST['installEmail'].'">
							</td>
							<td class="mid">
								<div class="input-group">
									<input type="text" name="installBFXFees" value="15.0000" class="form-control autoPercent">
									<span class="input-group-addon">%</span>
								</div>
							</td>
							
						</tr>
					</table>
				</div>
			</div>
			</form>
			
			';
}
else if($_REQUEST['doInstall']==2){
	echo '
		<form action="install.php" method="post" autocomplete="off" >
			<input type="hidden" name="doInstall" value="2">
		
		<div class="panel panel-default">
				<div class="panel-heading">Step 2 - Admin Account Details</div>
				<div class="panel-body table-responsive">
					<table class="table table-striped table-bordered">
						<thead>
						<tr>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Host" data-content="Database Host Address.   Unless you\'re doing something weird, this is probably \'localhost\'.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Admin Username
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Name" data-content="name of the database you\'ll be using.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Admin Email
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database User" data-content="Database user account.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Admin Password
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Password" data-content="Database User Password.  Make sure this is a good secure password.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Admin\'s Bitfinex API Key
							</th>
							<th class="mid" style="width:20%;">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Database Prefix" data-content="This will be appended to the front of each database table. Useful for servers with more than 1 install of the bot. (Ex: BFXLendBot1_ would create the user table as BFXLendBot1_Users)">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Admin\'s Bitfinex API Secret
							</th>
						</tr>
						</thead>
						<tr>
							<td class="mid">
								<input type="text" id="inputAdminUser" class="form-control"  required="" autofocus="" name="installAdminUser"  autocomplete="off" value="'.$_REQUEST['installAdminUser'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputAdminEmail" class="form-control" placeholder="you@yourdomain.com" autofocus="" name="installAdminEmail"  autocomplete="off" value="'.$_REQUEST['installAdminEmail'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputAdminPassword" class="form-control" placeholder="Password" autofocus="" name="installAdminPassword"  autocomplete="off" value="'.$_REQUEST['installAdminPassword'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputAdminBFXKey" class="form-control" placeholder="API Key" autofocus="" name="installAdminBFXKey"  autocomplete="off" value="'.$_REQUEST['installAdminBFXKey'].'">
							</td>
							<td class="mid">
								<input type="text" id="inputAdminBFXSec" class="form-control" placeholder="API Secret" autofocus="" name="installAdminBFXSec"  autocomplete="off" value="'.$_REQUEST['installAdminBFXSec'].'">
							</td>
						</tr>
						<tr>
							
							<td class="mid" colspan="5">
								<button class="btn btn-lg btn-primary btn-block" type="submit" style="width:200px;margin: 20px auto;">Complete Install</button>
							</td>
						</tr>
					</table>
				</div>
			</div>
			</form>
			
			';
}
else if($_REQUEST['doInstall']==3){
	$cronURL = 'http://'.$_SERVER["HTTP_HOST"].dirname($_SERVER['PHP_SELF']).'/crons/';
	
	echo '
		<div class="panel panel-default">
				<div class="panel-heading">Install Complete!</div>
				<div class="panel-body table-responsive">
					Looks like everything is ready to go!  Its a good idea to set your config file back to read only (chmod 655 '.$configFile.' ), and maybe even delete this file ( install.php ).<br><Br>
					
					Finally, if you want this bot to do anything useful, you\'re going to need to set up 2 cron jobs, one that runs crons/TenMinuteCron.php every 10 minutes, and one that runs crons/HourlyCron.php once an hour.  To do this on linux, set your crons to:
					<ul>
						<li>01,11,21,31,41,51 * * * * wget -qO- '.$cronURL.'TenMinuteCron.php >/dev/null 2>&1</li>
						<li>5 * * * * wget -qO- '.$cronURL.'HourlyCron.php >/dev/null 2>&1</li>
					</ul>
					
					Once you\'ve changed config to read only and added the 2 crons, <a href="index.php">Log In</a> to get started!
					<br>
					<button onClick="window.location=\'index.php\'" class="btn btn-lg btn-primary btn-block" type="submit" style="width:200px;margin: 20px auto;">Log In Now!</button>
					
				</div>
			</div>
			
			';
}

// Show Footer //
require_once('inc/footer.php');
?>