<?
// file configs //
require_once("config.php");
if($config['db']['host'] == ''){
	// not configured, we probably need to install //
	if (!headers_sent()){
		header('Location: install.php');
		exit;
	}
	// Header already sent.  Script redir them.
	else{
		echo '<script>window.location = "install.php";</script>';
		exit;
	}
	
}


$alert = array();
$warning = array();
$notice = array();

// db connectors and functions //
require_once("database.php");
$db = new Database();


// general functions //
require_once("General.php");
$gen = new General();

require_once("Pages.php");
$pages = new Pages();


// account functions //
require_once("Accounts.php");
$act = new Accounts();

require_once('ExchangeAPIs/bitfinex.php');

// Lets use pHpass for password encryption, to insure compatibility with older version of php 5.
require_once("PasswordHash.php");
$hasher = new PasswordHash(8, false);


if($_REQUEST['doLogout']==1){
	$act->doLogoutUser();
}
else{
	if($_REQUEST['login_email']){
		// attempt to log in user //
		$act->doLoginUser();
	}
	else if(!$act->checkLoggedUser()){
		/*
		 Check for a logged in user,
		 if not, set the url to the login page
		*/
		$pages->activePage = 'doLogin';
	}
	
	// ok, they're logged in, lets check for submits and load various account details //
	if($_SESSION['userid']){
		// lets create an array of account objects we can use
		//  level 1 accounts will only have their own details in the array,
		// but level 9 admin accounts will have all the accounts in the db in their array
		$accounts[$act->userid] = $act;
		// If the user is an admin account, grab all the other accounts as well, load them into an array //
		if($act->sts == 9){
			$act->getAllAccounts();
		}
		
		//  Form Submission Checks //
		// Add A New Account //
		if($_REQUEST['new_name']){
			$accStep = $act->doAddAccount();
		}
		// Update Account Settings //
		if($_REQUEST['doUpdate']==1){
			$accounts[$_REQUEST['userid']]->updateSettings();
		}
	}

}


$gen->checkCronStatus();


?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?=($pages->title ? $config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].' - '.$pages->title : $config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor']);?></title>

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jquery.formatCurrency-1.4.0.min.js"></script>
    <!-- Highcharts, for the stats page -->
    <script src="http://code.highcharts.com/stock/highstock.js"></script>
	<script src="http://code.highcharts.com/stock/modules/exporting.js"></script>
	
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/bootstrap-theme.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/styles.css" rel="stylesheet">
    
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
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
          <a class="navbar-brand" href="index.php" title="Version <?=$config['app_version'].'.'.$config['app_version_minor'];?>"><?=$config['app_name'].' '.$config['app_version'];?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
 
        <? if($_SESSION['userid']!=''){ ?>
 
          <ul class="nav navbar-nav">
            <li class="<?=($pages->activePage == 'home' ? 'active' : '');?>"><a href="index.php">Home</a></li>
            <? if($act->sts == 9){ ?>
            <li class="<?=($pages->activePage == 'addAct' ? 'active' : '');?>"><a href="index.php?page=addAct">Add Account</a></li>
            <? } ?>
            <li class="<?=($pages->activePage == 'viewReturns' ? 'active' : '');?>"><a href="index.php?page=viewReturns">View Overall Returns</a></li>


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
                <li><a href="mailto:<?=$config['app_support_email'];?>">Email</a></li>
                <li><a href="<?=$config['app_support_url'];?>">Forums</a></li>
              </ul>
            </li>
            <li><a href="index.php?doLogout=1">Logout</a></li>
          </ul>
          <? } ?>
        </div><!--/.nav-collapse -->
      </div>
    </nav>


<? 
$gen->showSiteModals();

// is config writable?  warn them//
$configFile = getcwd().'/inc/config.php';
if (is_writable($configFile) && $_SESSION['userid']!='') {$warning[] = "Your Config File Seems to be writable.  You should change this to read only for security reasons!<br> (chmod 644 ".$configFile." )";}

// does install.php still exist?  we should delete it... //
$installFile = getcwd().'/install.php';
if (file_exists($installFile) && $_SESSION['userid']!='') {$notice[] = "Your Install File Seems to still exist.  Its probably a good idea to delete this file, as you won't need it anymore and it could be a secuirty issue.<br>  (Delete the file ".$installFile." )";}


?>

<div class="container">
