<?
// file configs //
require_once("config.php");

$alert = array();
$warning = array();

// db connectors and functions //
require_once("database.php");
$db = new Database();
// general functions //
require_once("General.php");
$gen = new General();

// general functions //
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




// defaults //
$headers['title'] = ($headers['title'] ? $config['app_name'].' '.$config['app_version'].' - '.$headers['title'] : $config['app_name'].' '.$config['app_version']);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?=$headers['title'];?></title>

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jquery.formatCurrency-1.4.0.min.js"></script>
	
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
          <a class="navbar-brand" href="index.php"><?=$config['app_name'].' '.$config['app_version'];?></a>
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


    
<!-- Sign Up For BFX Modal -->
<div class="modal fade" id="signUpModal" tabindex="-1" role="dialog" aria-labelledby="signUpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      	<div class="pull-left" style="margin-right:20px;font-size: 22px;">
            <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title" id="signUpModalLabel">Save 10% On All Fees For 30 Days When You Sign Up Here</h4>
      </div>
      <div class="modal-body">
      	<p>If you sign up for Bitfinex.com using this <a href="https://www.bitfinex.com/?refcode=vsAnxuo5bM">referal link</a> ( Code: vsAnxuo5bM ), you'll get 10% off all fees on trade and swap activity for the first 30 days.</p>
        <p>Doing so costs you nothing, and supports the continued development of this software.</p>
        <p>If you do sign up using our Referal code, <strong>make sure to <a href="mailto:<?=$config['app_support_email'];?>">send us an email</a>, and we'll add you to our supporter list</strong>.  Supporters get first access to <?=$config['app_name'];?> updates, priority technical support ( when available ), and priority when requesting new features.</p>
      	<p style="text-align:center"><a href="https://www.bitfinex.com/?refcode=vsAnxuo5bM" class="btn btn-success btn-lg" style="width:250px;" target="bfx">Join Bitfinex Now!</a></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Donate Modal -->
<div class="modal fade" id="donateModal" tabindex="-1" role="dialog" aria-labelledby="donateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      	<div class="pull-left" style="margin-right:20px;font-size: 22px;">
            <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title" id="donateModalLabel">Donate To Support Further Development!</h4>
      </div>
      <div class="modal-body">
      	<p>Developing this software, and testing the various strategies for lending that led to its development have taken significant time and effort.  If you find this software useful, please send a small donation our way.  All donations support the continued development of this software, and help to cover my distribution and support costs.</p>
        <p>If you do send a donation,  <strong>make sure to <a href="mailto:<?=$config['app_support_email'];?>m">send us an email</a>, and we'll add you to our supporter list</strong>.  Supporters get first access to <?=$config['app_name'];?> updates, priority technical support ( when available ), and priority when requesting new features.</p>
      	<p>You can send donations to:</p>
        <div class="media" style="border-bottom:1px solid #e5e5e5;padding-bottom:20px;" >
          <a class="media-left">
            <img src="img/bitcoin_donate.png" alt="Dontate Bitcoin: 1LtVC2TE88b9zJcf6NFk4fzupM74QGUXQB">
          </a>
          <div class="media-body" style="vertical-align: middle;">
            <h4 class="media-heading">Bitcoin: 1LtVC2TE88b9zJcf6NFk4fzupM74QGUXQB</h4>
          </div>
        </div>
        <div class="media" style="padding-top:20px;">
          <a class="media-left">
            <img src="img/litecoin_donate.png" alt="Dontate Litecoin: LgKWYe7uisDkfz2LDeYi7tKEHukJdoziyp">
          </a>
          <div class="media-body" style="vertical-align: middle;">
            <h4 class="media-heading">Litecoin: LgKWYe7uisDkfz2LDeYi7tKEHukJdoziyp</h4>
          </div>
        </div>
      	</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Sign Up For BFX Modal -->
<div class="modal fade" id="disclaimerModal" tabindex="-1" role="dialog" aria-labelledby="disclaimerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      	<div class="pull-left" style="margin-right:20px;font-size: 22px;">
            <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title" id="disclaimerModalLabel"><?=$config['app_name'].' '.$config['app_version'];?> and Terms of Use</h4>
      </div>
      <div class="modal-body">
      	<p>Alright, we could put a big huge block of text here written by dozens of lawyers requiring you to sign away your first born child and all your Dogecoin (are those still a thing?), but I'd rather keep this simple and straightforward.  So, heres the deal:</p>
        <h4>The "Deal"</h4>
        <p>This software is provided as is, with no warrantee or guarantee or promise of any kind.  We're pretty sure it works, at least for the most part, and we use it ourselves.  But there's probably a few things that don't work, it is software after all, and <em><strong>ALL</strong></em> software has at least a few bugs ( <a href="mailto:<?=$config['app_support_email'];?>">report them here</a> ).  We did our best to insure that those bugs are small, and not show stopping, but we don't even promise this.</p>
        <p>More importantly, you will be providing your BFX API Key to this software in order for it to function.  This comes with a lot of security risks.  This bot basically has full access to your Bitfinex Account.  It has to in order to be useful and do anything.  The API currently limits actions that can be taken, so removing money from your account isn't possible, but we can't promise their API will be this way forever. Besides, if someone malicious did get access to your API Key, the may not be able to directly take your money, but they could force your account to make a bunch of ridiculous orders or something else that you probably wouldn't be too happy about.</p>
        <p> We've done our best to secure this Bot against hacker attacks, but at the end of the day security for this bot, and the server it lives on, is <em><strong>YOUR</strong></em> responsibility.
        	Follow all security best practices, make sure to password protect everything, don't give anyone you don't trust explicitly access to the bot, don't advertise that you've installed it on your servers and make yourself a target, etc.</p>
         <p>When you download this software, make sure to only download it from the original GitHub repository.  Don't EVER DOWNLOAD any software from an untrusted source.  They could very easily modify the software to steal everything in your Bitfinex account.</p>
         <p>Also, remember you have the full source code, right here, in your hands.  Feel free to check through the code, just to make sure you understand whats happening and that we're not doing anything Nefarious with your API access (we're not, we promise, but don't take our word for it, look through the code).</p>
         <p>Huh, turns out that was a bit longer than I intended after all....   Lets make this easier:</p>
		<h3>TL;DR</h3>
		<p><em><strong>Use At Your Own Risk.  Something goes wrong, not our problem!</strong></em></p>
         
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<div class="container">
