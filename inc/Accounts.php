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
	
	public function __construct($userid='') {
    	global $config, $db;
		$this->db = $db;
		if($userid!=''){
			$this->userid = $userid;
			$this->getAccount();
		}
 	 }
	 
	 // sts and user_lvl (in user db as status) are as follows //
	 // 0 - account inactive / disabled
	 // 1 - base account
	 // 2 - base account, lending paused
	 // 8 - admin account, lending paused
	 // 9 - admin account
	 // ( it was done this way to maintain database backwards compatibilty with older installs when we added pausing )
	 
	 
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
			unset($_SESSION['user_lvl']);
			$warning[] = "Your Session Has Expired, Please Log In Again";
			return false;
		}
	}
	
	/* log in user  */
	function doLoginUser(){
		global $config, $hasher, $pages, $alert, $warning;
		//  Grab a user from the database using form input //
		$userLog = $this->db->query("SELECT id, name, password, status FROM `".$config['db']['prefix']."Users` WHERE (email = '".$this->db->escapeStr($_REQUEST['login_email'])."'  OR name = '".$this->db->escapeStr($_REQUEST['login_email'])."' ) LIMIT 1");
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
				$_SESSION['user_lvl']=$userLog[0]['status'];
				// update account object with user info //
				$this->userid = $userLog[0]['id'];
				$this->getAccount();
				return true;
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
		unset($_SESSION['user_lvl']);
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
						 ( '".$newUser['id']."', '0.0650', '3', '25000', '100000', '0.1500', '0.3500', '0' )";
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
		if($this->sts==9 || $this->sts==8){
			$userIds = $this->db->query("SELECT id from `".$config['db']['prefix']."Users` WHERE status != 0 AND id != ".$this->userid." ORDER BY id ASC");
			foreach($userIds as $uid){
				$accounts[$uid['id']] = new Accounts($uid['id']);
			}
		}
	}
	
	
	function displayDetailsTableRow(){
		global $gen, $config;
		$thisCurrency = $_REQUEST['funding'];
		
		$yesterdayRet = $this->get1DayReturns($thisCurrency);
		$thirtydayRet = $this->get30DayReturns($thisCurrency);
		$fullRet = $this->getFullReturns($thisCurrency);
		$estReturn = (($this->bfx->cryptoCurrentLendVal[$thisCurrency] * ($this->bfx->cryptoCurrentLendAvg[$thisCurrency]/100)) * ((100 - $config['curFeesBFX'])/100));
		
		
		echo '
		<tr class="bigrow '.( ($this->bfx->actSettings['status'][$thisCurrency]==2 ) ? 'danger':'').'" id="userRow_'.$this->userid.'">
				<td rowspan="2" class="mid">'.$this->userid.'</td>
				<td rowspan="2" class="mid">'.$this->name.'<br> 
					( <a href="#" data-uid="'.$this->userid.'" class="doPauseCur" data-cur="'.$thisCurrency.'" id="doPauseCur_'.$this->userid.'">'.( ($this->sts == 2 || $this->sts == 8 ) ? 'Unpause':'Pause').' Lending</a> )<br>
					( <a class="collapsed" data-toggle="collapse" href="#collapseListExtract'.$this->userid.'" aria-expanded="false" aria-controls="collapseListExtract'.$this->userid.'">Extract</a> )
				</td>
				<td class="mid">'.$gen->cryptoFormat($this->bfx->cryptoBalance[$thisCurrency], 8, $thisCurrency).'</td>
				<td class="mid">'.$gen->cryptoFormat($this->bfx->cryptoAvailable[$thisCurrency], 8, $thisCurrency).'</td>
				<td class="mid">'.$gen->cryptoFormat($this->bfx->cryptoPendingVal[$thisCurrency], 8, $thisCurrency).' <span class="badge">'.number_format($this->bfx->cryptoPendingOffers[$thisCurrency]).'</span>
					<br>('.$gen->percentFormat($this->bfx->cryptoPendingAvg[$thisCurrency]).')
					<br>(<a class="collapsed" data-toggle="collapse" href="#collapseListPending'.$this->userid.'" aria-expanded="false" aria-controls="collapseListPending'.$this->userid.'">Show</a>)
				</td>
				<td class="mid">'.$gen->cryptoFormat($this->bfx->cryptoCurrentLendVal[$thisCurrency], 8, $thisCurrency).' <span class="badge">'.number_format(count($this->bfx->cryptoCurrentLends[$thisCurrency])).'</span>
					<br>('.$gen->percentFormat($this->bfx->cryptoCurrentLendAvg[$thisCurrency]).')
					<br>(<a class="collapsed" data-toggle="collapse" href="#collapseListOutstanding'.$this->userid.'" aria-expanded="false" aria-controls="collapseListOutstanding'.$this->userid.'">Show</a>)
				</td>
				<td class="mid">'.$gen->cryptoFormat($estReturn, 8, $thisCurrency).'</td>
				
				<td class="mid">'.$gen->cryptoFormat($yesterdayRet['intTotal'], 8, $thisCurrency).'<br>('.$gen->percentFormat($yesterdayRet['avgInt']).')</td>
				<td class="mid">'.$gen->cryptoFormat($thirtydayRet['intTotal'], 8, $thisCurrency).'<br>('.$gen->percentFormat($thirtydayRet['avgInt']).')</td>
				<td class="mid">'.$gen->cryptoFormat($fullRet['intTotal'], 8, $thisCurrency).'<br>('.$gen->percentFormat($fullRet['avgInt']).')</td>
			</tr>
			<tr style="border-bottom: 2px solid #ddd;">
				<td colspan="8" style="padding: 0px;margin: 0px;">
					<!-- Change Settings Div -->
					<form action="index.php" method="post">
					<input type="hidden" name="doUpdate" value="1">
					<input type="hidden" name="userid" value="'.$this->userid.'">
					<input type="hidden" name="curType" value="'.$thisCurrency.'">
					<div class="panel-group" role="tablist" style="margin:0px;">
					<div class="panel panel-default">
					  <div class="panel-heading" role="tab" id="collapseListGroupHeading'.$this->userid.'">
						<h4 class="panel-title">
						  <a class="collapsed" data-toggle="collapse" href="#collapseListGroup'.$this->userid.'" aria-expanded="false" aria-controls="collapseListGroup'.$this->userid.'">
							<div class="pull-left" style="margin-right:20px;">
						  		<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
							</div>
							
							Change Lending Options for '.$this->name.' - '.$gen->symbol2name($_REQUEST['funding']).'
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
								<td><input type="text" name="spreadlend" value="'.number_format($this->bfx->actSettings['spreadlend'][$thisCurrency]).'" class="form-control"></td>
								<td>
									<div class="input-group">
									  <input type="text" name="minlendrate" value="'.number_format($this->bfx->actSettings['minlendrate'][$thisCurrency], 5).'" class="form-control autoPercent">
									  <span class="input-group-addon">%</span>
									</div>
								</td>
								<td>
									<div class="input-group">
										<input type="text" name="thirtyDayMin" value="'.number_format($this->bfx->actSettings['thirtyDayMin'][$thisCurrency], 5).'" class="form-control autoPercent">
										<span class="input-group-addon">%</span>
									</div>
								</td>
								<td>
									<div class="input-group">
										<span class="input-group-addon">'.$thisCurrency.'</span>
										<input type="text" name="highholdamt" value="'.number_format($this->bfx->actSettings['highholdamt'][$thisCurrency], 2).'" class="form-control autoCurrency">
									</div>
								</td>
								<td>
									<div class="input-group">
										<input type="text" name="highholdlimit" value="'.number_format($this->bfx->actSettings['highholdlimit'][$thisCurrency], 5).'" class="form-control autoPercent">
										<span class="input-group-addon">%</span>
									</div>
								</td>
							</tr>
							<tr style="font-size: 10px;">
								<th class="mid" style="width: 150px;" colspan="2">
									<div style="padding-top:1px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Gap Bottom" data-content="How far, in '.$thisCurrency.', you want to move through the Bitfinex Lendbook before placing your first Loan Offer. If you want your offer set as the lowest rate on the book at each update, set this to $0 ( Not Recommended ).  For a detailed explanation, visit the help section.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Gap Bottom
								</th>
								<th class="mid" style="width: 150px;" colspan="2">
									<div style="padding-top:1px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Gap Top" data-content="How far, in '.$thisCurrency.', you want to move through the Bitfinex Lendbook before placing your highest Loan Offer. Your loan offeres will be spread evenly between Gap Bottom and Gap Top depending on your \'Spread Available Lends\' setting above.  For a detailed explanation, visit the help section.">
									  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
									</div>
									Gap Top
								</th>
								<th></th>
							</tr>
							<tr>
								<td colspan="2">
									<div class="input-group">
										<span class="input-group-addon">'.$thisCurrency.'</span>
										<input type="text" name="USDgapBottom" value="'.number_format($this->bfx->actSettings['USDgapBottom'][$thisCurrency], 2).'" class="form-control autoCurrency">
									</div>
								</td>
								<td colspan="2">
									<div class="input-group">
										<span class="input-group-addon">'.$thisCurrency.'</span>
										<input type="text" name="USDgapTop" value="'.number_format($this->bfx->actSettings['USDgapTop'][$thisCurrency], 2).'" class="form-control autoCurrency">
									</div>
								</td>
								<td></td>
							</tr>
						</table>
					  </div>
					</div>
				  </div>
				  </form>
				  
				  
				  
				<!-- Extract Money Settings -->
				<form action="index.php" method="post">
					<input type="hidden" name="doUpdateExtract" value="1">
					<input type="hidden" name="userid" value="'.$this->userid.'">
					<input type="hidden" name="curType" value="'.$thisCurrency.'">
					<div class="panel-group" role="tablist" style="margin:0px;">
					<div class="panel panel-default">
					  <div id="collapseListExtract'.$this->userid.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="collapseListExtractHeading'.$this->userid.'" aria-expanded="false" style="height: 0px;">
						<table class="table table-striped table-bordered" style="padding: 0px;margin: 0px;">
							<thead>
							<tr style="font-size: 10px;">
								<th class="mid" style="width: 250px;">Fullfilled</th>
								<th class="mid">ETA</th>
								<th class="mid" style="width: 500px;">Amount</th>
							</tr>
							</thead>
							<tbody>
							
							<tr>';
							
							if($this->bfx->actSettings['extractAmt'] > 0 ){
								echo '
									<td class="mid">'.($this->bfx->cryptoAvailable[$thisCurrency] >= $this->bfx->actSettings['extractAmt'][$thisCurrency] ? '<span class="text-success"><strong>'.$gen->moneyFormat($this->bfx->actSettings['extractAmt'][$thisCurrency]).'</strong>' : '<span class="text-danger">'.$gen->moneyFormat($this->bfx->cryptoAvailable) ).' of '.$gen->moneyFormat($this->bfx->actSettings['extractAmt'][$thisCurrency]).' USD</span></td>
									<td class="mid">'.($this->bfx->cryptoAvailable[$thisCurrency] >= $this->bfx->actSettings['extractAmt'][$thisCurrency] ? '<span class="text-success"><strong>AVAILABLE NOW</strong>' : '<span class="text-danger">'.$gen->howLongToExpire($this->getTimeToExtract())).'</span></td>
								';
							}
							else if($this->bfx->actSettings['extractAmt'][$thisCurrency] == -1 ){
								echo '
									<td class="mid">'.($this->bfx->cryptoAvailable[$thisCurrency] == $this->bfx->cryptoBalance[$thisCurrency] ? '<span class="text-success"><strong>'.$gen->moneyFormat($this->bfx->cryptoBalance[$thisCurrency]).'</strong>' : '<span class="text-danger">'.$gen->moneyFormat($this->bfx->cryptoAvailable[$thisCurrency]) ).' of MAX '.$thisCurrency.'</span></td>
									<td class="mid">'.($this->bfx->cryptoAvailable[$thisCurrency] == $this->bfx->cryptoBalance[$thisCurrency] ? '<span class="text-success"><strong>AVAILABLE NOW</strong>' : '<span class="text-danger">'.$gen->howLongToExpire($this->getTimeToExtract())).'</span></td>
								';
							}
							else{
								echo '
									<td class="mid">N/A</td>
									<td class="mid">N/A</td>
								';
							}
							echo '
								<td>
									<div class="input-group" style="float:left;">
										<span class="input-group-addon">'.$thisCurrency.'</span>
										<input type="text" name="extractAmt" id="extractAmt_'.$this->userid.'" value="'.($this->bfx->actSettings['extractAmt'][$thisCurrency] == -1 ? 'MAX' : number_format($this->bfx->actSettings['extractAmt'][$thisCurrency], 2)).'" class="form-control autoCurrency">
									</div>
									<div style="float:right;">
										<button style="width:100px;white-space: normal !important;" class="btn btn-warning maxExtract" value="'.$this->userid.'">MAX</button>
										<button style="width:100px;white-space: normal !important;" class="btn btn-primary" type="submit">Save</button>
									</div>
								</td>
							</tr>
							</tbody>
						</table></div></div></div>
					</form>
					'.$this->showPendingLends($thisCurrency).'
					'.$this->showOpenLends($thisCurrency).'
				</td>
			</tr>
			
			';
			
			
			
	}
	
	
	function showPendingLends($thisCurrency='USD'){
		global $gen, $config;
		$return = '
				<!-- Pending Loans -->
					<div class="panel-group" role="tablist" style="margin:0px;">
					<div class="panel panel-default">
					  <div id="collapseListPending'.$this->userid.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="collapseListPendingHeading'.$this->userid.'" aria-expanded="false" style="height: 0px;">
						<table class="table table-striped table-bordered sortableTable" style="padding: 0px;margin: 0px;">
							<thead>
							<tr style="font-size: 10px;">
								<th class="mid" style="width: 60px;">Currency</th>
								<th class="mid" style="width: 110px;">N. of Swap Contracts</th>
								<th class="mid" style="width: 110px;">Rate (% per day)</th>
								<th class="mid" style="width: 110px;">Swap Time</th>
								<th class="mid" style="width: 100px;">Placed at</th>
							</tr>
							</thead>
							<tbody>
							
							';
							//print_r($this->bfx->usdPendingLends);
						if(count($this->bfx->cryptoPendingLends[$thisCurrency]) == 0){
							$return .= '
							<tr id="pending_0">
								<td colspan="5" class="mid info lead">No Pending Loans</td>
							</tr>';
						}
						else{
								
							foreach($this->bfx->cryptoPendingLends[$thisCurrency] as $p){
								//$expireTime = $l['timestamp'] + ($l['period'] * 86400);
								
								$return .= '
								<tr id="pending_'.$p['id'].'">
									<td>'.$p['currency'].'</td>
									<td>'.$p['remaining_amount'].'</td>
									<td>'.$gen->percentFormat( $p['rate'] / 365).'</td>
									<td>'.$p['period'].' Days</td>
									<td>'.date('d M h:i',  $p['timestamp']).'</td>
									
								</tr>';
								
							}
						}
					$return .= '</tbody>
						</table></div></div></div>';
						
			return $return;
		
	}
	
	function showOpenLends($thisCurrency='USD'){
		global $gen, $config;
		$return = '
			<!-- Outstanding Loans -->
					<div class="panel-group" role="tablist" style="margin:0px;">
					<div class="panel panel-default">
					  <div id="collapseListOutstanding'.$this->userid.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="collapseListOutstandingHeading'.$this->userid.'" aria-expanded="false" style="height: 0px;">
						<table class="table table-striped table-bordered sortableTable" style="padding: 0px;margin: 0px;">
							<thead>
							<tr style="font-size: 10px;">
								<th class="mid" style="width: 60px;">Currency</th>
								<th class="mid" style="width: 110px;">N. of Swap Contracts</th>
								<th class="mid" style="width: 110px;">Rate (% per day)</th>
								<th class="mid" style="width: 100px;">Opened at</th>
								<th class="mid" style="width: 110px;">Expire in</th>
							</tr>
							</thead>
							<tbody>
							';
			if(count($this->bfx->cryptoCurrentLends[$thisCurrency]) == 0){
							$return .= '
							<tr id="outstanding_0">
								<td colspan="5" class="mid info lead">No Outstanding Loans</td>
							</tr>';
			}
			else{
				foreach($this->bfx->cryptoCurrentLends[$thisCurrency] as $l){
					$expireTime = $l['timestamp'] + ($l['period'] * 86400);
					
					$return .= '
								<tr id="outstanding_'.$l['id'].'">
									<td>'.$l['currency'].'</td>
									<td>'.$l['amount'].'</td>
									<td>'.$gen->percentFormat( $l['rate'] / 365).'</td>
									<td>'.date('d M h:i',  $l['timestamp']).'</td>
									<td>'.$gen->howLongToExpire($expireTime).'</td>
									
								</tr>';
								
					}
				}
		$return .= '</tbody></table></div></div></div>';
		return $return;
		
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
		/*
		$sql = "UPDATE `".$config['db']['prefix']."Vars` SET  minlendrate = '".$this->db->escapeStr($minlendrate)."', spreadlend = '".$this->db->escapeStr($spreadlend)."',
				USDgapBottom = '".$this->db->escapeStr($USDgapBottom)."', USDgapTop = '".$this->db->escapeStr($USDgapTop)."', 
				thirtyDayMin = '".$this->db->escapeStr($thirtyDayMin)."', 
				highholdlimit = '".$this->db->escapeStr($highholdlimit)."', 
				highholdamt = '".$this->db->escapeStr($highholdamt)."'
				 WHERE userid = '".$this->db->escapeStr($this->userid)."' AND curType = '".$this->db->escapeStr($_REQUEST['curType'])."' LIMIT 1";
		*/		 
		$sql = "REPLACE INTO `".$config['db']['prefix']."Vars` 
					(
						`minlendrate`,
						`spreadlend`,
						`USDgapBottom`,
						`USDgapTop`,
						`thirtyDayMin`,
						`highholdlimit`,
						`highholdamt`,
						`userid`,
						`curType`
					)
				VALUES 
					(
						'".$this->db->escapeStr($minlendrate)."',
						'".$this->db->escapeStr($spreadlend)."',
						'".$this->db->escapeStr($USDgapBottom)."',
						'".$this->db->escapeStr($USDgapTop)."', 
						'".$this->db->escapeStr($thirtyDayMin)."', 
						'".$this->db->escapeStr($highholdlimit)."',
						'".$this->db->escapeStr($highholdamt)."',
						'".$this->db->escapeStr($this->userid)."',
						'".$this->db->escapeStr($_REQUEST['curType'])."'
					)";
				 
				 //echo $sql;
		$upd = $this->db->iquery($sql);
		
		/* Update the BFX Object */
		$this->bfx = new Bitfinex($this->userid, $this->apiKey, $this->apiSec, $this->bfx->nonceInc);
		//print_r($this->bfx);	
		
		$alert[] = '<strong>User '.$this->name.'</strong> Account Settings Updated';
		
	}
	
	// Update Extraction
	function updateExtractSettings(){
		global $config, $alert, $warning;
		
		if(strtolower($_REQUEST['extractAmt']) == 'max'){
			$extractAmt = -1;
		}
		else{
			$extractAmt = preg_replace('/[^0-9.]/', '', $_REQUEST['extractAmt']);
		}
		// write defaults to db
		$sql = "UPDATE `".$config['db']['prefix']."Vars` SET  extractAmt = '".$this->db->escapeStr($extractAmt)."'
				WHERE userid = '".$this->db->escapeStr($this->userid)."' AND curType = '".$this->db->escapeStr($_REQUEST['curType'])."' LIMIT 1";
				 
		$upd = $this->db->iquery($sql);
		
		/* Update the BFX Object */
		$this->bfx = new Bitfinex($this->userid, $this->apiKey, $this->apiSec, $this->bfx->nonceInc);
				
		$alert[] = '<strong>User '.$this->name.'</strong> Extraction Settings Updated';
		
	}
	
	
	//////////////////////////////////
	//  Statistics Stuff			//
	//////////////////////////////////
	
	/* Loan Return Details */
	function get1DayReturns($cur='USD'){
		global $config;
		$averageReturn = $this->db->query("SELECT swap_payment as intTotal, ((swap_payment / dep_balance)*100) as avgInt FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($this->userid)."' AND trans_cur = '".$this->db->escapeStr($cur)."'  and date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
		return $averageReturn[0];
	}
	function get30DayReturns($cur='USD'){
		global $config;
		$averageReturn = $this->db->query("SELECT SUM(swap_payment) as intTotal, (SUM(swap_payment) / SUM(dep_balance))*100 as avgInt FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($this->userid)."' AND trans_cur = '".$this->db->escapeStr($cur)."' and date BETWEEN DATE_SUB(NOW(), INTERVAL 31 DAY) AND DATE_SUB(NOW(), INTERVAL 1 DAY)");
		return $averageReturn[0];
	}
	function getFullReturns($cur='USD'){
		global $config;
		$averageReturn = $this->db->query("SELECT SUM(swap_payment) as intTotal, (SUM(swap_payment) / SUM(dep_balance))*100 as avgInt FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($this->userid)."' AND trans_cur = '".$this->db->escapeStr($cur)."'");
		return $averageReturn[0];
	}
	
	function getStatsArray( $uid = 0, $cur='USD'){
		global $config;
		if($uid == 0){
			$uid = $this->userid;
		}
		$averageReturn = $this->db->query("SELECT date, swap_payment, average_return, dep_balance FROM `".$config['db']['prefix']."Tracking` where user_id = '".$this->db->escapeStr($uid)."' AND trans_cur = '".$this->db->escapeStr($cur)."' ");
		return $averageReturn;		
	}
	
	
	function getTimeToExtract(){
		$returnTimeStamp = '';
		if($this->bfx->actSettings['extractAmt'][$thisCurrency] == -1){
			$exAmt = $this->bfx->cryptoBalance[$thisCurrency]; 
		}
		else{
			$exAmt = $this->bfx->actSettings['extractAmt'][$thisCurrency];
		}
		
		
		if(count($this->bfx->cryptoCurrentLends[$thisCurrency]) > 0){
			foreach($this->bfx->cryptoCurrentLends[$thisCurrency] as $key=>$cl){
				$expireTime = $cl['timestamp']+($cl['period'] * 86400);
				$expireMoney[$expireTime] = $cl['amount'];
			}
			ksort($expireMoney);
			$totBack = $this->bfx->cryptoAvailable[$thisCurrency];
			foreach($expireMoney as $key=>$em){
				if($totBack < $exAmt){
					$totBack += $em;
					$returnTimeStamp = $key;
				}
			}
		}
		return $returnTimeStamp;
		
	}
	
}

?>
