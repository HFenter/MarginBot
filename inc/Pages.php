<?php
/*------------------------------------------------------------------------------------------
 * Page Content and Layouts
 *------------------------------------------------------------------------------------------
 */
class Pages {
	var $activePage;
	var $title;

	function __construct() {
		$this->activePage = $_REQUEST['page'];
		if($this->activePage==''){
			$this->activePage='home';
		}
		switch ($this->activePage) {
			case 'doLogin':
				$this->title = 'Login';
				break;
			case 'addAct':
				$this->title = 'Add An Account';
				break;
			case 'viewReturns':
				$this->title = 'View Detailed Returns';
				break;
			case 'home':
			default:
				$this->title = 'Dashboard';
		}
		
	}


	function showPage(){
		global $accounts;
		//  should make this better
		switch ($this->activePage) {
			case 'doLogin':
				$this->showLoginPage();
				break;
			case 'addAct':
				$this->showAddAccount();
				break;
			case 'viewReturns':
				$this->showViewReturns();
				break;
			case 'home':
			default:
				$this->activePage='home';
				$this->showActiveAccounts($accounts);
		}
		
	}
	function showActiveAccounts($accounts){
		global $config;
		echo '
		<div class="panel panel-default">
		  <div class="panel-heading">Current Bitfinex Accounts</div>
		  <div class="panel-body table-responsive">
			<table class="table table-striped table-bordered">
				<thead>
				<tr>
					<th class="mid">ID</td>
					<th class="mid">Name</td>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Deposit Balance" data-content="How much $USD is in your Bitfinex Deposit Wallet in total, including Active and Pending Loans, as well as Available cash.">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Deposit<Br>Balance 
					</th>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Deposit Available" data-content="How much $USD in your Bitfinex Deposit Wallet is currently not used in either an Active or Pending Loan.">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Deposit<Br>Available
					</th>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Loans Pending" data-content="How many Pending Loans you have waiting to be accepted, as well as the average rate those loans are offered at before fees ( % / Day ) .">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Loans<Br>Pending
					</th>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Loans Active" data-content="How many Active Loans you have which have been accepted by a Margin Borrower, and are currently paying you interest, as well as the average rate those loans are paying before fees ( % / Day ) .">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Loans<Br>Active
					</th>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Estimated Daily Return" data-content="A Rough Estimate of today\'s return assuming all loans remained open for the full day at the current rate ( which they most likely won\'t) This includes Bitfinex fees.">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Estimated<Br>Return						
					</th>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Yesterdays Return" data-content="How much your Margin Swaps earned in interest yesterday, after fees ( % / Day ) .">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Yesterdays<Br>Return
					</th>
					<th class="mid">
						<div style="height: 40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Thirty Day Return" data-content="How much your Margin Swaps earned in interest over the last 30 days, after fees ( % / Day ) .">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						30 Day<Br>Returns
					</th>
					<th class="mid">
						<div style="height:40px;padding-top:12px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Lifetime Return" data-content="How much your Margin Swaps earned in interest over the life of using '.$config['app_name'].', after fees ( % / Day )  Note that your if your BFX account existed before you began using '.$config['app_name'].', those returns will not be included here .">
						  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
						</div>
						Lifetime<Br>Returns
					</th>
				</tr>
				</thead>
		';
		foreach($accounts as $a){
			$a->displayDetailsTableRow();
		}		
				
		echo '
				</table>
			</div>
		</div>';
	}


	function showAddAccount(){
		global $accStep;
		// Step 1, User Info
		if($accStep['page']==0){
			echo '
			<form action="index.php" method="post" autocomplete="off" >
			<input type="hidden" name="doAddAccount" value="1">
			<input type="hidden" name="page" value="addAct">
			<div class="panel panel-default">
				<div class="panel-heading">Add Managed Account</div>
				<div class="panel-body table-responsive">
					<table class="table table-striped table-bordered">
						<thead>
						<tr>
							<th class="mid">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Account Name" data-content="User name this account will display.  If the account is set to allow login, this will also be their login user name.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Account Name
							</th>
							<th class="mid">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Account Email" data-content="Email associated with the account, for sending reports and login information.   If the account is set to allow login, they can also use this address to log in.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Account Email
							</th>
							<th class="mid">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Password" data-content="Account Password.  Only used if the account is set to allow login.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Password
							</th>
							<th class="mid">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Account Level" data-content="Accounts can be one of 3 types, Admin - which has full access to all accounts and functions, Mananged with login has access to ONLY their account, and Managed No Login, which can only be accessed by Admin accounts.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Account Level
							</th>
						</tr>
						</thead>
						<tr>
							<td class="mid">
								<input type="text" id="inputName" class="form-control" placeholder="Account Name" required="" autofocus="" name="new_name"  autocomplete="off" value="'.$_REQUEST['new_name'].'">
							</td>
							<td class="mid">
								<input type="email" id="inputEmail" class="form-control" placeholder="Account Email" autofocus="" name="new_email"  autocomplete="off" value="'.$_REQUEST['new_email'].'">
							</td>
							<td class="mid">
								<input type="password" id="inputPassword" class="form-control" placeholder="Account Password" autofocus="" name="new_password"  autocomplete="off" value="'.$_REQUEST['new_password'].'">
							</td>
							<td class="mid">
								<select  id="inputType" class="form-control" autofocus="" name="new_actType">
									<option value="2">Managed Account (No Login)</option>
									<option value="1">Managed Account (Can Login)</option>
									<option value="9">Admin Account</option>
								</select>
							</td>
						</tr>
						<thead>
						<tr>
							<th class="mid" colspan="2">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Bitfinex API Key" data-content="When setting up a new account, you will need to get this API Key from bitfinex.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Bitfinex API Key
							</th>
							<th class="mid" colspan="2">
								<div style="height: 20px;padding-top:2px;" aria-label="Help" class="pull-right"  data-toggle="popover" data-placement="right" title="Bitfinex API Secret" data-content="When setting up a new account, you will need to get this API Secret from bitfinex.">
								  <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>
								</div>
								Bitfinex API Secret						
							</th>
						</tr>
						</thead>
						<tr>					
							<td class="mid" colspan="2">
								<input type="text" id="inputBFXKey" class="form-control" placeholder="Bitfinex API Key" required="" autofocus="" name="new_bfxKey"  autocomplete="off" value="'.$_REQUEST['new_bfxKey'].'">
							</td>
							<td class="mid" colspan="2">
								<input type="text" id="inputBFXSec" class="form-control" placeholder="Bitfinex API Secret" required="" autofocus="" name="new_bfxSec"  autocomplete="off" value="'.$_REQUEST['new_bfxSec'].'">
							</td>
						</tr>
						<tr>					
							<td class="mid" colspan="4">
								<button class="btn btn-lg btn-primary btn-block" type="submit">Create Account</button>
							</td>
						</tr>
					</table>
				</div>
			</div>
			</form>';
		}
		else{
			// Step 2, update default settings //
			// lets just reuse what we already have above
			$a[] = new Accounts($accStep['newaccount']);
			$this->showActiveAccounts($a);
		}
		
	}
	
	function showViewReturns(){
		global $accounts;
		
		if($_SESSION['user_lvl']==9){
			// global stats for all accounts //
			echo "
				<script type='text/javascript' src='js/global_chart.js'></script>
				<div class='bigChart'>					
					<div id='chart_GlobalDailyReturns' class='chartArea'><img src='img/ajax-loader.gif' class='loader'></div>
				</div>
				";
			foreach($accounts as $a){
				$userIds[] = $a->userid;
				$userNames[] = $a->name;
				echo "
				<div class='bigChart'>
					<div id='chart_UserDailyReturns_".$a->userid."' class='chartArea'><img src='img/ajax-loader.gif' class='loader'></div>
				</div>
					";
                }
				echo "
				<script>
					var userIds = [".implode(",", $userIds)."];
					var userNames = ['".implode("','", $userNames)."'];
				</script>
				<script type='text/javascript' src='js/user_chart.js'></script>
				
				";

		
		}
		// Individual Stats //
		else{
			echo "
				<div class='bigChart'>
					<div id='chart_UserDailyReturns_".$_SESSION['userid']."' class='chartArea'><img src='img/ajax-loader.gif' class='loader'></div>
				</div>
                <script>
					var userIds = [".$_SESSION['userid']."];
					var userNames = ['".$_SESSION['username']."'];
				</script>
				<script type='text/javascript' src='js/user_chart.js'></script>
				
				";
		}
	}


	function showLoginPage(){
	
		echo '
		
		
		<form class="form-signin" role="form" method="post" action="index.php">
			<h2 class="form-signin-heading">Please sign in</h2>
			<label for="inputEmail" class="sr-only">User Name or Email Address</label>
			<input type="username" id="inputEmail" class="form-control" placeholder="User Name or Email" required="" autofocus="" name="login_email">
			<label for="inputPassword" class="sr-only">Password</label>
			<input type="password" id="inputPassword" class="form-control" placeholder="Password" required="" name="login_password">
			<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
		  </form>
		  ';
	  
	}
}
?>