<?
// file configs //
require_once("inc/config.php");


$alert = array();
$warning = array();
$notice = array();

// db connectors and functions //
require_once("inc/database.php");
$db = new Database();

// general functions //
require_once("inc/General.php");
$gen = new General();


echo '
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>'.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].' - Update</title>
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






// This is a submit, lets do some stuff //
if($_REQUEST['doUpdate']==2){
	// Run the Update
	
	
	// have they already run this update?
	// checking if CurPairs exist should be good enough, since it shouldn't exist in previous versions
	$exists = $db->query("SHOW TABLES like '".$config['db']['prefix']."CurPairs'");
	if(count($exists)>0){
		// it exists, lets let them know and bail out
		echo '
				<div class="panel panel-default">
					<div class="panel-heading">Update to '.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].' Already Done?</div>
					<div class="panel-body table-responsive">
						It seems you\'ve already updated to the latest version.  If you think this is an error,  contact us on the <a href="'.$config['app_support_url'].'">forums</a>, or by <a href="mailto:'.$config['app_support_email'].'">email</a> to get some help.  Otherwise click below to get started lending!<br><br>
						<a href="index.php" class="btn btn-success btn-lg active" role="button">Lets Get Started</a>
					</div>
				</div>
				';
		// Show Footer //
		require_once('inc/footer.php');
		exit;
	}
	
	
	// First Duplicate / Backup existing database tables
	$db->iquery("CREATE TABLE `".$config['db']['prefix']."CronRuns_BACKUP_v17` LIKE `".$config['db']['prefix']."CronRuns`");
	$updateWorked = $db->iquery("INSERT `".$config['db']['prefix']."CronRuns_BACKUP_v17` SELECT * FROM `".$config['db']['prefix']."CronRuns`");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table duplication failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	$db->iquery("CREATE TABLE `".$config['db']['prefix']."Tracking_BACKUP_v17` LIKE `".$config['db']['prefix']."Tracking`");
	$updateWorked = $db->iquery("INSERT `".$config['db']['prefix']."Tracking_BACKUP_v17` SELECT * FROM `".$config['db']['prefix']."Tracking`");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table duplication failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}			
	$db->iquery("CREATE TABLE `".$config['db']['prefix']."Users_BACKUP_v17` LIKE `".$config['db']['prefix']."Users`");
	$updateWorked = $db->iquery("INSERT `".$config['db']['prefix']."Users_BACKUP_v17` SELECT * FROM `".$config['db']['prefix']."Users`");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table duplication failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	$db->iquery("CREATE TABLE `".$config['db']['prefix']."Vars_BACKUP_v17` LIKE `".$config['db']['prefix']."Vars`");
	$updateWorked = $db->iquery("INSERT `".$config['db']['prefix']."Vars_BACKUP_v17` SELECT * FROM `".$config['db']['prefix']."Vars`");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table duplication failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	
	// Second, add new table
	$db->iquery("CREATE TABLE `".$config['db']['prefix']."CurPairs`  (
				  `id` smallint(4) NOT NULL AUTO_INCREMENT,
				  `curSym` varchar(12) DEFAULT NULL,
				  `curName` varchar(100) DEFAULT NULL,
				  `status` tinyint(1) DEFAULT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('1', 'USD', 'US Dollars', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('2', 'BTC', 'Bitcoin', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('3', 'IOT', 'Iota', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('4', 'ETH', 'Ethereum', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('5', 'OMG', 'OmiseGO', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('6', 'BCH', 'Bcash', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('7', 'EOS', 'EOS', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('8', 'ETC', 'Ethereum Classic', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('9', 'DSH', 'Dash', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('10', 'XMR', 'Monero', '1')");
	$db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('11', 'ZEC', 'Zcash', '1')");
	$updateWorked = $db->iquery("INSERT INTO `".$config['db']['prefix']."CurPairs` VALUES ('12', 'XRP', 'Ripple', '1')");

	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table creation failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}

	// Third, update existing tables
	$updateWorked = $db->iquery("ALTER TABLE `".$config['db']['prefix']."Vars`
									MODIFY COLUMN `id`  smallint(4) NOT NULL AUTO_INCREMENT FIRST,
									ADD COLUMN `curType`  varchar(10) NULL AFTER `id`,
									ADD COLUMN `extractAmt`  varchar(12) NULL AFTER `highholdamt`,
									ADD COLUMN `userid`  smallint(4) NULL AFTER `id`,
									ADD UNIQUE INDEX `unqType` (`userid`, `curType`)");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table updates failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	$updateWorked = $db->iquery("UPDATE `".$config['db']['prefix']."Vars` set `curType` = 'USD', `extractAmt` = '0.00', userid = id");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table updates failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	$updateWorked = $db->iquery("ALTER TABLE `".$config['db']['prefix']."Tracking`
									ADD COLUMN `trans_cur`  varchar(10) NULL AFTER `user_id`,
									MODIFY COLUMN `dep_balance`  decimal(12,8) NULL DEFAULT NULL AFTER `date`,
									MODIFY COLUMN `swap_payment`  decimal(12,8) NULL DEFAULT NULL AFTER `dep_balance`,
									DROP INDEX `uniquieKeys` ,
									ADD UNIQUE INDEX `uniquieKeys` (`user_id`, `trans_id`, `trans_cur`) USING BTREE");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table updates failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	$updateWorked = $db->iquery("UPDATE `".$config['db']['prefix']."Tracking` set `trans_cur` = 'USD'");
	if ( $updateWorked['num']<=0 ){
		 $warning[] = "Table updates failed: (" . $db->database_link->errno . ") " . $db->database_link->error;
		}
	
	// Finally, show them it worked, link them to the landing page.
	
	
		
		
		$gen->showWarnings($warning);
		$gen->showAlerts($alert);
		
		if(count($warning)==0){
			// tables seemed to create ok, lets write the config file //
			echo '
				<div class="panel panel-default">
					<div class="panel-heading">Update to '.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].' Complete</div>
					<div class="panel-body table-responsive">
						Everything seems to have gone well.  The update is complete and you are ready to start lending margin!<br><br>
						<a href="index.php" class="btn btn-success btn-lg active" role="button">Lets Get Started</a>
					</div>
				</div>
				';	
		}
		else{
			
			// Something failed.  Warn them, then let them try again //
			echo '
				<div class="panel panel-default">
					<div class="panel-heading">Update Failed</div>
					<div class="panel-body table-responsive">
						Something didn\'t go right.  Feel free to try again, or contact us on the <a href="'.$config['app_support_url'].'">forums</a>, or by <a href="mailto:'.$config['app_support_email'].'">email</a> to get some help.<br><br>
						<a href="update.php?doUpdate=2" class="btn btn-warning btn-lg active" role="button">Try The Update Again...</a>
					</div>
				</div>
				';	
		}

}






/*
databases need an update, let them know.
*/

else if($_REQUEST['doUpdate']<=1){
	
	
	$gen->showWarnings($warning);
	$gen->showAlerts($alert);
	
	echo '
		
			<div class="panel panel-default">
				<div class="panel-heading">Lets Update to '.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].'</div>
				<div class="panel-body table-responsive">
					It looks like you need to update your '.$config['app_name'].' install to version '.$config['app_version'].'.'.$config['app_version_minor'].' .  Lets do so now!<br><br>
					This will first create backup duplicates of your database tables, then alter them to support the new features in this version.<br>
					It is always a good idea to make your own backup of everything before you do this.<br><br>
					<a href="update.php?doUpdate=2" class="btn btn-success btn-lg active" role="button">Start The Update!</a>
				</div>
			</div>
			';
}

// Show Footer //
require_once('inc/footer.php');