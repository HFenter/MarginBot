<?php
class Accounts{
	
	var $userid;
	var $email;
	var $name;
	var $sts;
	var $apiKey;
	var $apiSec;
	var $bfx;
	var $loginError;
	
	public function __construct($userid) {
    	global $config, $db;
		$this->db = $db;
		if($userid!=''){
			$this->userid = $userid;
			$this->getAccount();
		}
 	 }
	 
	 
	/* check for a logged in user, update the session for them  */
	function checkLoggedUser(){
		global $config, $hasher, $pages, $alert, $warning;
		// Check if all session variables are set 
		if (isset($_SESSION['userid'], $_SESSION['username'], $_SESSION['login_pass'])) {		 
			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];
			$userLogged = $this->db->query("SELECT password FROM `".$config['db']['prefix']."Users` WHERE id = '".$this->db->escapeStr($_SESSION['userid'])."' LIMIT 1");
			if (count($userLogged) ==  1) {
				// If the user exists get variables from result.
				// Check that the password is correct
				$check = $hasher->CheckPassword($_SESSION['login_pass'], $userLogged[0]['password']);
				if ($check) {
					// Logged In.
					// update account object with user info //
					$this->userid = $_SESSION['userid'];
					$this->getAccount();				
					return true;
				} else {
					// password check failed
					$logged = 0;
				}
			} else {
				// database search failed
				$logged = 0;
			}
		} else {
			// Session settings check failed
			$logged = 0;
		}
		if($logged == 0){
			unset($_SESSION['userid']);
			unset($_SESSION['username']);
			unset($_SESSION['login_pass']);
			$warning[] = "Your Session Has Expired, Please Log In Again";
			return false;
		}
	}
	
	/* log in user  */
	function doLoginUser(){
		global $config, $hasher, $pages, $alert, $warning;
		//  Grab a user from the database using form input //
		$userLog = $this->db->query("SELECT id, name, password FROM `".$config['db']['prefix']."Users` WHERE (email = '".$this->db->escapeStr($_REQUEST['login_email'])."'  OR name = '".$this->db->escapeStr($_REQUEST['login_email'])."' ) LIMIT 1");
		if (count($userLog) ==  1) {
			// seems to have found a row, lets hash their password and see if they match //
			// Check that the password is correct
			$check = $hasher->CheckPassword($_REQUEST['login_password'], $userLog[0]['password']);
			if ($check) {
				// passwords matched, set their session, then send them to the homepage
				$pages->activePage = 'home';
				$_SESSION['userid']=$userLog[0]['id'];
				$_SESSION['username']=$userLog[0]['name'];
				$_SESSION['login_pass']=$_REQUEST['login_password'];
				// update account object with user info //
				$this->userid = $userLog[0]['id'];
				$this->getAccount();
			} else {
			 // passwords didn't match, show an error
			$warning[] = "Your Password didn't match our records, Please Try Again.";
			return false;
			}
        } else {
            // No user exists.
			$warning[] = "Your Username or Email was not found! Please Try Again.";
            return false;
        }
	}
	
	function doLogoutUser(){
		global $hasher, $pages, $alert, $warning;
		unset($_SESSION['userid']);
		unset($_SESSION['username']);
		unset($_SESSION['login_pass']);
		$alert[] = "Your Session Has Been Logged Out, Please Log Back In To Continue";
		$pages->activePage = 'doLogin';
	}
	
	
	function doAddAccount(){
		global $config, $hasher, $pages, $alert, $warning, $gen;
		// Check Everything Submitted to see if its valid //
		if(strlen($_REQUEST['new_name']) < 3){$warning[] = 'Account Name must be at least 3 characters long';}
		if(strlen($_REQUEST['new_bfxKey']) != 43){$warning[] = 'Bitfinex API Keys are 43 Characters Long';}
		if(strlen($_REQUEST['new_bfxSec']) != 43){$warning[] = 'Bitfinex API Secrets are 43 Characters Long';}
		// Passwords should never be longer than 72 characters to prevent DoS attacks
		if (strlen($_REQUEST['new_password']) > 72){$warning[] = 'Passwords must be less than 72 Characters';}

		if(count($warning)==0){
			// Check it doesn't already exits...
			$userCheck = $this->db->query("SELECT name, bfxapikey FROM `".$config['db']['prefix']."Users` WHERE (name = '".$this->db->escapeStr($_REQUEST['new_name'])."' OR bfxapikey = '".$this->db->escapeStr($_REQUEST['new_bfxKey'])."' ) LIMIT 1");
			if (count($userCheck) ==  1) {
				if($userCheck[0]['name'] == $_REQUEST['new_name'] ){
					$warning[] = 'This user name already exists in our database';
				}
				if($userCheck[0]['bfxapikey'] == $_REQUEST['new_bfxKey'] ){
					$warning[] = 'This bitfinex key already exists in our database';
				}
			}
		}
		if(count($warning)==0){
			// test their bfx key and sec to see if we can pull data //
			$bfxTest = new Bitfinex(0, $_REQUEST['new_bfxKey'], $_REQUEST['new_bfxSec']);
			$bt = $bfxTest->bitfinex_get('account_infos');
			if($bt[0]['fees'][0]['pairs']!=''){
				// looks good //
				// Create The Account //
				
				// hash the password
				$passEnc = $hasher->HashPassword($_REQUEST['new_password']);
				// write account to db
				$sql = "INSERT into `".$config['db']['prefix']."Users` (`name`,`email`,`password`,`bfxapikey`,`bfxapisec`,`status` )
					 VALUES
					 ( '".$this->db->escapeStr($_REQUEST['new_name'])."', '".$this->db->escapeStr($_REQUEST['new_email'])."', '".$this->db->escapeStr($passEnc)."',
					 '".$this->db->escapeStr($_REQUEST['new_bfxKey'])."', '".$this->db->escapeStr($_REQUEST['new_bfxSec'])."', '".$this->db->escapeStr($_REQUEST['new_actType'])."' )";
				$newUser = $this->db->iquery($sql);
				
				if($newUser['id']!=0){
					//  Set default settings for the account //
					$sql = "INSERT into `".$config['db']['prefix']."Vars` (`id`,`minlendrate`,`spreadlend`,`USDgapBottom`,`USDgapTop`,`thirtyDayMin`,`highholdlimit`,`highholdamt` )
						 VALUES
						 ( '".$newUser['id']."', '0.0500', '3', '25000', '100000', '0.1500', '0.3500', '0' )";
					$newActSettings = $this->db->iquery($sql);
					$ret['page']=2;
					$ret['newaccount']=$newUser['id'];
					
					$alert[] = '<strong>User '.$_REQUEST['new_name'].'</strong> Account Created';
				}
			}
		}
		else{
			$ret['page']=0;
		}
		return $ret;
	}
	
	
	/* Grab account info from database */
	function getAccount(){
		global $config;
		$sql = "SELECT * from `".$config['db']['prefix']."Users` WHERE id = '".$this->db->escapeStr($this->userid)."' LIMIT 1";
		$userInfo = $this->db->query($sql);
		if($userInfo[0]['bfxapikey'] !='' && $userInfo[0]['bfxapisec'] !=''){
			/* Good user, set all the variables */
			$this->name = $userInfo[0]['name'];
			$this->email = $userInfo[0]['email'];
			$this->sts = $userInfo[0]['status'];
			$this->apiKey = $userInfo[0]['bfxapikey'];
			$this->apiSec = $userInfo[0]['bfxapisec'];
			/* Create their BFX Object */
			$this->bfx = new Bitfinex($this->userid, $this->apiKey, $this->apiSec);			
			}
	}
	
	
	function getAllAccounts(){
		// * Get All Active BFX Accounts      * //
		// * Create Account Objects for them  * //
		// Only allow ADMIN accounts to do this //
		global $config, $accounts;
		if($this->sts==9){
			$userIds = $this->db->query("SELECT id from `".$config['db']['prefix']."Users` WHERE status != 0 AND id != ".$this->userid." ORDER BY id ASC");
			foreach($userIds as $uid){
				$accounts[$uid['id']] = new Accounts($uid['id']);
			}
		}
	}
	
	
	function displayDetailsTableRow(){
		global $gen, $config;
		$yesterdayRet = $this->get1DayReturns();
		$thirtydayRet = $this->get30DayReturns();
		$fullRet = $this->getFullReturns();
		$estReturn = (($this->bfx->usdCurrentLendVal * ($this->bfx->usdCurrentLendAvg/100)) * ((100 - $config['curFeesBFX'])/100));
		echo '
		<form action="index.php" method="post">
			<input type="hidden" name="doUpdate" value="1">
			<input type="hidden" name="userid" value="'.$this->userid.'">
		<tr class="bigrow">
				<td rowspan="2" class="mid">'.$this->userid.'</td>
				<td rowspan="2" class="mid">'.$this->name.'</td>
				<td class="mid">'.$gen->moneyFormat($this->bfx->usdBalance).'</td>
				<td class="mid">'.$gen->moneyFormat($this->bfx->usdAvailable).'</td>
				<td class="mid">'.$gen->moneyFormat($this->bfx->usdPendingVal).' <span class="badge">'.number_format($this->bfx->usdPendingOffers).'</span>
					<br>( '.$gen->percentFormat($this->bfx->usdPendingAvg).' )</td>
				<td class="mid">'.$gen->moneyFormat($this->bfx->usdCurrentLendVal).' <span class="badge">'.number_format(count($this->bfx->usdCurrentLends)).'</span>
					<br>( '.$gen->percentFormat($this->bfx->usdCurrentLendAvg).' )</td>
				<td class="mid">'.$gen->moneyFormat($estReturn).'</td>
				
				<td class="mid">'.$gen->moneyFormat($yesterdayRet['intTotal']).'<br>( '.$gen->percentFormat($yesterdayRet['avgInt']).' )</td>
				<td class="mid">'.$gen->moneyFormat($thirtydayRet['intTotal']).'<br>( '.$gen->percentFormat($thirtydayRet['avgInt']).' )</td>
				<td class="mid">'.$gen->moneyFormat($fullRet['intTotal']).'<br>( '.$gen->percentFormat($fullRet['avgInt']).' )</td>
			</tr>
			<tr style="border-bottom: 2px solid #ddd;">
				<td colspan="8" style="padding: 0px;margin: 0px;">
					<div class="panel-group" role="tablist" style="margin:0px;">
					<div class="panel panel-default">
					  <div class="panel-heading" role="tab" id="collapseListGroupHeading'.$this->userid.'">
						<h4 class="panel-title">
						  <a class="collapsed" data-toggle="collapse" href="#collapseListGroup'.$this->userid.'" aria-expanded="false" aria-controls="collapseListGroup'.$this->userid.'">
							<div class="pull-left" style="margin-right:20px;">
						  		<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
							</div>
							
							Change Lending Options for '.$this->name.'
						  </a>
						</h4>
					  </div>
					  <div id="collapseListGroup'.$this->userid.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="collapseListGroupHeading'.$this->userid.'" aria-expanded="false" style="height: 0px;">
						<table class="table table-striped table-bordered" style="padding: 0px;margin: 0px;">
							
							<tr style="font-size: 10px;">
								<th class="mid" style="width: 60px;">
									<div style="height:25px;padding-top:8px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Spread Available Lends" data-content="Spread your available money across this many individual loans, in order to hit multiple interest price points and lend some of your money quicker while keeping some offered at a higher price. ( Usually keep this number between 2 - 5 unless you have a lot of available money to lend, set it to 1 if you want to lend out all of your money at your Gap Bottom setting. ) ">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Spread<br>Available Lends
								</th>
								<th class="mid" style="width: 110px;">
									<div style="height:25px;padding-top:8px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Minimum Lend Rate" data-content="Lowest Daily Interest Rate you are willing to lend at.  If the going rate on Bitfinex goes below this, you\'re loans will likely stay on offer for long periods without being accepted.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Minimum<br>Lend Rate
								</th>
								<th class="mid" style="width: 110px;">
									<div style="height:25px;padding-top:8px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Minimum for 30 Day Loans" data-content="Lowest Daily Interest Rate at which your loans will be offered for 30 days instead of 2.  Set this to insure your loans earn you a higher interest rate for longer periods durring flash lending runs.  If you want all loans to stay on a 2 day cycle, set this to 0.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Minimum<br>for 30 Day
								</th>
								<th class="mid" style="width: 100px;">
									<div style="height:25px;padding-top:8px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="High Hold Amount" data-content="Amount of money you want to keep offered at the High Hold Rate.  High Hold is used to keep a portion of your Deposit Wallet offered on Loan at a much higher rate than Marginbot would be likely to set in a given day.  This allows you to catch Flash Margin runs and lend at least some money at a higher than normal rate. Set this to 0 if you don\'t want to keep money set aside for Flash Runs.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									High Hold<br>Amount</th>
								<th class="mid" style="width: 110px;">
									<div style="height:25px;padding-top:8px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="High Hold Rate" data-content="Rate you want to offer the High Hold Amount.  High Hold is used to keep a portion of your Deposit Wallet offered on Loan at a much higher rate than Marginbot would be likely to set in a given day.  This allows you to catch Flash Margin runs and lend at least some money at a higher than normal rate.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									High Hold<br>Rate</th>
								<td rowspan="4" class="mid" style="width: 180px;">
									<button style="width:160px;white-space: normal !important;" class="btn btn-primary" type="submit">Update '.$this->name.'\'s Settings</button>
								</td>
							</tr>
							<tr>
								<td><input type="text" name="spreadlend" value="'.number_format($this->bfx->actSettings['spreadlend']).'" class="form-control"></td>
								<td>
									<div class="input-group">
									  <input type="text" name="minlendrate" value="'.number_format($this->bfx->actSettings['minlendrate'], 4).'" class="form-control autoPercent">
									  <span class="input-group-addon">%</span>
									</div>
								</td>
								<td>
									<div class="input-group">
										<input type="text" name="thirtyDayMin" value="'.number_format($this->bfx->actSettings['thirtyDayMin'], 4).'" class="form-control autoPercent">
										<span class="input-group-addon">%</span>
									</div>
								</td>
								<td>
									<div class="input-group">
										<span class="input-group-addon">$</span>
										<input type="text" name="highholdamt" value="'.number_format($this->bfx->actSettings['highholdamt'], 2).'" class="form-control autoCurrency">
									</div>
								</td>
								<td>
									<div class="input-group">
										<input type="text" name="highholdlimit" value="'.number_format($this->bfx->actSettings['highholdlimit'], 4).'" class="form-control autoPercent">
										<span class="input-group-addon">%</span>
									</div>
								</td>
							</tr>
							<tr style="font-size: 10px;">
								<th class="mid" style="width: 150px;" colspan="2">
									<div style="padding-top:1px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Gap Bottom" data-content="How far, in $USD, you want to move through the Bitfinex Lendbook before placing your first Loan Offer. If you want your offer set as the lowest rate on the book at each update, set this to $0 ( Not Recommended ).  For a detailed explanation, visit the help section.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Gap Bottom
								</th>
								<th class="mid" style="width: 150px;" colspan="2">
									<div style="padding-top:1px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Gap Top" data-content="How far, in $USD, you want to move through the Bitfinex Lendbook before placing your highest Loan Offer. Your loan offeres will be spread evenly between Gap Bottom and Gap Top depending on your \'Spread Available Lends\' setting above.  For a detailed explanation, visit the help section.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Gap Top
								</th>
								<th></th>
							</tr>
							<tr>
								<td colspan="2">
									<div class="input-group">
										<span class="input-group-addon">$</span>
										<input type="text" name="USDgapBottom" value="'.number_format($this->bfx->actSettings['USDgapBottom'], 2).'" class="form-control autoCurrency">
									</div>
								</td>
								<td colspan="2">
									<div class="input-group">
										<span class="input-group-addon">$</span>
										<input type="text" name="USDgapTop" value="'.number_format($this->bfx->actSettings['USDgapTop'], 2).'" class="form-control autoCurrency">
									</div>
								</td>
								<td></td>
							</tr>
						</table>
					  </div>
					</div>
				  </div>
					
				</td>
			</tr>
			</form>
			';
	}
	
	function updateSettings(){
		global $config, $alert, $warning;
		
		$minlendrate = preg_replace('/[^0-9.]/', '', $_REQUEST['minlendrate']);
		$spreadlend = preg_replace('/[^0-9.]/', '', $_REQUEST['spreadlend']);
		$USDgapBottom = preg_replace('/[^0-9.]/', '', $_REQUEST['USDgapBottom']);
		$USDgapTop = preg_replace('/[^0-9.]/', '', $_REQUEST['USDgapTop']);
		$thirtyDayMin = preg_replace('/[^0-9.]/', '', $_REQUEST['thirtyDayMin']);
		$highholdlimit = preg_replace('/[^0-9.]/', '', $_REQUEST['highholdlimit']);
		$highholdamt = preg_replace('/[^0-9.]/', '', $_REQUEST['highholdamt']);
		
		
		// write defaults to db
		$sql = "UPDATE `".$config['db']['prefix']."Vars` SET  minlendrate = '".$this->db->escapeStr($minlendrate)."', spreadlend = '".$this->db->escapeStr($spreadlend)."',
				USDgapBottom = '".$this->db->escapeStr($USDgapBottom)."', USDgapTop = '".$this->db->escapeStr($USDgapTop)."', 
				thirtyDayMin = '".$this->db->escapeStr($thirtyDayMin)."', 
				highholdlimit = '".$this->db->escapeStr($highholdlimit)."', 
				highholdamt = '".$this->db->escapeStr($highholdamt)."'
				 WHERE id = '".$this->db->escapeStr($this->userid)."' LIMIT 1";
				 
				 //echo $sql;
		$upd = $this->db->iquery($sql);
		
		/* Update the BFX Object */
		$this->bfx = new Bitfinex($this->userid, $this->apiKey, $this->apiSec, $this->bfx->nonceInc);
		//print_r($this->bfx);	
		
		$alert[] = '<strong>User '.$this->name.'</strong> Account Settings Updated';
		
	}
	
	
	//////////////////////////////////
	//  Statistics Stuff			//
	//////////////////////////////////
	
	/* Loan Return Details */
	function get1DayReturns(){
		global $config;
		$averageReturn = $this->db->query("SELECT swap_payment as intTotal, ((swap_payment / dep_balance)*100) as avgInt FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($this->userid)."' and date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
		return $averageReturn[0];
	}
	function get30DayReturns(){
		global $config;
		$averageReturn = $this->db->query("SELECT SUM(swap_payment) as intTotal, (SUM(swap_payment) / SUM(dep_balance))*100 as avgInt FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($this->userid)."' and date BETWEEN DATE_SUB(NOW(), INTERVAL 31 DAY) AND DATE_SUB(NOW(), INTERVAL 1 DAY)");
		return $averageReturn[0];
	}
	function getFullReturns(){
		global $config;
		$averageReturn = $this->db->query("SELECT SUM(swap_payment) as intTotal, (SUM(swap_payment) / SUM(dep_balance))*100 as avgInt FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($this->userid)."'");
		return $averageReturn[0];
	}
	
}

?>
