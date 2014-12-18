<?php
class Bitfinex{
	
	var $userid;
	var $apiKey;
	var $apiSec;
	var $nonceInc;
	
	
	var $usdBalance;
	var $usdAvailable;
		
	var $usdPendingVal;
	var $usdPendingOffers;
	var $usdPendingAvg;
	var $usdPendingIDS = array();
	var $usdCurrentLends = array();
	var $usdCurrentLendVal;
	var $usdCurrentLendAvg;
	
	var $actSettings = array();
	
	
	var $lendbook = array();
	
	
    public function __construct($userid, $apiKey, $apiSec, $nonce=0) {
    	global $config, $db;
		$this->userid = $userid;
        $this->apiKey = $apiKey;
        $this->apiSec = $apiSec;
		$this->db = $db;
		if($nonce==0){
			$this->nonceInc = (microtime(true)*100);
		}
		else{
			$this->nonceInc = $nonce +1;
		}
		if($userid != 0){
			$this->bitfinex_getDepositBalance();
			$this->bitfinex_getPendLoans();
			$this->bitfinex_getCurLoans();
			$this->bitfinex_getAccountSettings();
		}
			
 	 }
	 
	 
	 /* Data posting query, for detailed API calls */
	 function bitfinex_query($method, array $req = array()) {
        // API settings
		
        $req['request'] = '/v1/'.$method;
        $this->nonceInc += 1;
		$req['nonce'] = (string)$this->nonceInc;
		if($req['price']){$req['price'] = (string)$req['price'];}
       
        // generate the POST data string
        $reqData = base64_encode(json_encode($req, true));
		$post_data = http_build_query($req, '', '&');
		//print_r($post_data);
        $sign = hash_hmac('sha384', $reqData, $this->apiSec);
 
        // generate the extra headers
        $headers = array(
			'X-BFX-APIKEY: '.$this->apiKey,
			'X-BFX-PAYLOAD: '.$reqData,
			'X-BFX-SIGNATURE: '.$sign
        );
 
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MarginBot PHP client;)');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://api.bitfinex.com/v1/'.$method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
        // run the query
        $res = curl_exec($ch);
        if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
		$dec = json_decode($res, true);
		return $dec;
	}
	
	/* No data posting, simple get method */
	function bitfinex_get($method, $symbol='') {
        // API settings
        $req['request'] = '/v1/'.$method.($symbol != '' ? '/'.$symbol : '');
        $this->nonceInc += 1;
		$req['nonce'] = (string)$this->nonceInc;
        
		// generate the POST data string
        $reqData = base64_encode(json_encode($req, true));
        $sign = hash_hmac('sha384', $reqData, $this->apiSec);
 
        // generate the extra headers
        $headers = array(
			'X-BFX-APIKEY: '.$this->apiKey,
			'X-BFX-PAYLOAD: '.$reqData,
			'X-BFX-SIGNATURE: '.$sign
        );
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MarginBot PHP client;)');
        }
        
		curl_setopt($ch, CURLOPT_URL, 'https://api.bitfinex.com'.$req['request'] );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
 		// run the query
        $res = curl_exec($ch);
		if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
		$dec = json_decode($res, true);
		return $dec;
	}
	
	
	/* Grab performace history and store it in a local database */
	function bitfinex_updateHistory($numDays=10,  $sinceLast=0){
		global $config;
		// no start date set, so grab the last timestamp from the db //
		if($sinceLast==0){
			$sql = "SELECT trans_id from `".$config['db']['prefix']."Tracking` WHERE user_id = '".$this->db->escapeStr($this->userid)."' ORDER BY trans_id DESC LIMIT 1";
			$lastId = $this->db->query($sql);
			$sinceLast = $lastId[0]['id']+1;
			if($sinceLast <=1){
				/* No Entries, Go Back 10 Days */
				$sinceLast = strtotime("-10 day");
			}
		}
		$ledgerDetails = array('currency' => 'USD', 'since' => (string)$sinceLast, 'until' => (string)($sinceLast + (86400 * $numDays)), 'wallet' => 'deposit');
		$ledgerHistory = $this->bitfinex_query('history', $ledgerDetails);
		$intTot =0;
		$balTot =0;
		$paymentsTot = 0;
		$return =0;
		foreach($ledgerHistory as $l){
			// check its a swap payment //
			if($l['description'] == 'Swap Payment on wallet deposit'){
				$sql = "INSERT into `".$config['db']['prefix']."Tracking` (`user_id`, `trans_id`, `date`, `dep_balance`,`swap_payment`,`average_return`) VALUES 
					('".$this->db->escapeStr($this->userid)."', '".$this->db->escapeStr($l['timestamp'])."', '".$this->db->escapeStr(date('Y-m-d', $l['timestamp']))."', '".$this->db->escapeStr($l['balance'])."','".$this->db->escapeStr($l['amount'])."','".$this->db->escapeStr(round((($l['amount'] / $l['balance']) * 100),6))."')";
				$upd = $this->db->iquery($sql);
				//print_r($upd);
				if($upd['num'] >0){$return++;}
			}
		}
		return $return;
	}
	//
	function bitfinex_getLendBook($numAsks=500){
		//this should be done as a bitfinex_get but it refuses to pass get variables correctly, so i'm doing it cheap
		$data = file_get_contents('https://api.bitfinex.com/v1/lendbook/usd/?limit_asks=500&limit_bids=0');
		$curRates =  json_decode($data, true);
		$tr=0;
		$totAmt = 0;
		unset($rates);
		foreach($curRates['asks'] as $b){
			$totAmt += $b['amount'];
			$rt = $b['rate'];//round(($b['rate']/365), 6);
			if($rt == $rates[$tr]['rate']){
				$rates[$tr]['rate'] = $rt;
				$rates[$tr]['totamt'] = $totAmt;
				$rates[$tr]['amt'] = ($b['amount'] + $rates[$tr]['amt']);
				$rates[$tr]['totOffers'] += 1;
			}
			else{
				$rates[++$tr]['rate'] = $rt;
				$rates[$tr]['totamt'] = $totAmt;
				$rates[$tr]['amt'] = $b['amount'];
				$rates[$tr]['totOffers'] += 1;
			}
		}
		$this->lendbook = $rates;
	}
	function bitfinex_updateMyLends(){
		// The magic function itself..  this is basically the bot right here. 
		//  This guy basically:
		//		1. Goes on bitfinex, cancels all pending loans to put the money back to available
		//		2. Grabs the current lendbook to see what the going rate is
		//		3. Based on your account settings splits your available money into TotalMoney / ( $spreadlend ) shares
		//		4. Creates ( $spreadlend ) Loan Offers spread evenly between the rates at which ( $USDgapBottom ) and ( $USDgapTop ) are reached
		//			(though never below your ( $minlendrate ) setting.
		//		5. If you have a ( $highholdamt ) and ( $highholdlimit ) set, it will always try to keep ( $highholdamt ) offered at ( $highholdlimit ) rate,
		//			in case there is a Flash Run on lending, so you can at least have some of your investment taking advantage of the much higher rates that occur durring panic buy/sales.
		//			Unless your settings are strange, these HighHold rates will rarely get hit, so expect this money to be not lent far more often than it is lent out.
		
		echo "\nRunning Bot For User ".$this->userid;
		//  Step 1 - Cancel Pending Loans to return cash to available pool
		$this->bitfinex_cancelPendingLoans();
		//  Step 2 - Update the BFX Lendbook for current rates
		$this->bitfinex_getLendBook();
		//  Step 3 - Figure out my Loan Splits and Rates
		$doLends = $this->bitfinex_getMyLendRates();
		
		//  Step 4 & 5 - Create the Loan offers
		$this->bitfinex_createLoanOffers($doLends);
		echo "\nCompleted";
	}
	
	function bitfinex_createLoanOffers($lendArray){
		if(count($lendArray)>0){
			foreach($lendArray as $la){
				$offerNew = array('currency' => 'USD', 'amount' => (string)$la['amt'],'rate' => (string)$la['rate'] ,'period' => (int)$la['time'],'direction' => 'lend');
				$newUSD = $this->bitfinex_query('offer/new', $offerNew);
				if($newUSD['message']!=''){
					$newUSD = $this->bitfinex_query('offer/new', $offerNew);
				}			
			}
		}
	}
	
	
	
	function bitfinex_cancelPendingLoans(){
		foreach($this->usdPendingIDS as $i){
			$offerCancel = array('offer_id' => $i);
			$cancelUSD = $this->bitfinex_query('offer/cancel', $offerCancel);
			}	
	}
	
	
	function bitfinex_getMyLendRates(){
		// How much do we lend at each rate point? 
		//  basically, we figure out how much we have to lend total,
		//  then divide it by the $spreadlend account setting to get
		//  a lend per amount.  If theres a highhold, we subtract
		//  that from the total and set it aside as a special lend
		
		// how much we got?
		// since we just canceled some loans we need to update this
		$this->bitfinex_getDepositBalance();
		
		// if its less than $50, we have nothing to do, since thats the minimum loan //
		if($this->usdAvailable >= 50){
			$a = 1;
			$splitAvailable = $this->usdAvailable;
			// Lets subtract the High Hold so we can make sure we're working with the right ammount
			if($this->actSettings['highholdamt'] >= 50){
				$splitAvailable = $splitAvailable - $this->actSettings['highholdamt'];
				//checking to make sure the highhold isn't more than total available too...
				$loans[0]['amt'] = ($this->actSettings['highholdamt'] > $this->usdAvailable ? $this->usdAvailable : $this->actSettings['highholdamt']);
				$loans[0]['rate'] = ($this->actSettings['highholdlimit']*365);
				// always loan out highholds for 30 days... bascially we're pretty sure this is a high rate loan
				$loans[0]['time'] = 30;				
			}
			// is there anything left after the highhold?  if so, lets split it up //
			if($splitAvailable > 50){
				// How many splits do we want?
				// gotta make sure each split is bigger than the minimum loan of $50
				$numSplits = $this->actSettings['spreadlend'];
				$amtEach = floor(($splitAvailable / $numSplits)*100)/100;
				while($amtEach<50){
					$amtEach = floor(($splitAvailable / --$numSplits)*100)/100;
				}
				
				// figure out the interest rate for each split, based on the current lend book and the gap settings
				//  this part is complicated, i'm documenting the best i can here....
				//  (This can and should be optimized, but it works and i wanted to get it out)
				if($numSplits >= 1){
					$gapClimb = ( ($this->actSettings['USDgapTop'] - $this->actSettings['USDgapBottom'])/($numSplits-1));
					$nextlend = $this->actSettings['USDgapBottom'];
					//set annual minimum for calculations //
					$minLendRateAnnual = ($this->actSettings['minlendrate']*365);
					foreach($this->lendbook as $l){
						while( ($l['totamt']>=$nextlend) && ($a <= $numSplits) ){
							$loans[$a]['amt'] = $amtEach;
							// Make sure the gap setting rate is higher than the minimum lend rate...
							// ( 0.00365 / annual = 0.00001 / day )
							$loans[$a]['rate'] = ( ($l['rate'] - 0.00365) > $minLendRateAnnual ? ($l['rate'] - 0.00365) : $minLendRateAnnual ) ;
							echo $loans[$a]['rate'].'<br>';
							//how long should we lend this out... as a rule, 2 days so we can cycle and get a high turnover
							// unless its above the threshold $this->actSettings['thirtyDayMin'], in which case we should lend it for the max 30 days
							// (basically, the rate is higher than normal, lets keep this loan out as long as possible)
							//  if $this->actSettings['thirtyDayMin'] = 0, always loan for 2 days, no matter what
							$loans[$a]['time'] = (($this->actSettings['thirtyDayMin']>0)&&($l['rate'] > ($this->actSettings['thirtyDayMin'] * 365)) ? 30 : 2);
							$nextlend += $gapClimb;
							$a++;
						}
					}
				}
			}
		}
		return $loans;
	}
	
	
	
	

	/////////////////////////////////////////
	// Bitfinex Grab Account Data Function //
	/////////////////////////////////////////
		
	/* Grab current deposit balance */
	function bitfinex_getDepositBalance(){
		$curBalanceRaw = $this->bitfinex_get('balances');
		foreach($curBalanceRaw as $key=>$cb){
			if($cb['type']=='deposit'){
				$this->usdBalance = $cb['amount'];
				$this->usdAvailable = $cb['available'];			
			}
		}
	}
	
	/* Grab current active loans */
	function bitfinex_getCurLoans(){
		$intReturn = 0;
		$curLends = $this->bitfinex_get('credits');
		foreach($curLends as $c){
			if($c['currency']=='USD'){
				$this->usdCurrentLends[] = $c;
				$this->usdCurrentLendVal += $c['amount'];
				$intReturn += ($c['amount']*( ($c['rate']/365)/100) );
			}
		}
		// fixed for divide by 0
		if($this->usdCurrentLendVal >0){
			$this->usdCurrentLendAvg = round( (($intReturn / $this->usdCurrentLendVal )*100),6);
		}
		else{
			$this->usdCurrentLendAvg = 0;
		}
		
	}
	
	/* Grab current pending loans */
	function bitfinex_getPendLoans(){
		$intReturn = 0;
		$curOffers = $this->bitfinex_get('offers');
		foreach($curOffers as $o){
			if($o['currency']=='USD'){
				$this->usdPendingVal += $o['remaining_amount'];
				$this->usdPendingOffers++;
				$this->usdPendingIDS[] = $o['id'];
				$intReturn += ($o['remaining_amount']*( ($o['rate']/365)/100) );
			}
		}
		// fixed for divide by 0
		if($this->usdPendingVal >0){
			$this->usdPendingAvg = round( (($intReturn /$this->usdPendingVal )*100),6);
		}
		else{
			$this->usdPendingAvg = 0;
		}
	}
	


	////////////////////////////////
	// Local BFX Account Settings //
	////////////////////////////////
	
	/* Grab current deposit balance */
	function bitfinex_getAccountSettings(){
		global $config;
		$sql = "SELECT * from `".$config['db']['prefix']."Vars` WHERE id = '".$this->db->escapeStr($this->userid)."' LIMIT 1";
		$userSettings = $this->db->query($sql);
		if($userSettings[0]['minlendrate'] !=''){
			/* Good user, set all the variables */
			$this->actSettings['minlendrate'] = $userSettings[0]['minlendrate'];
			$this->actSettings['spreadlend'] = $userSettings[0]['spreadlend'];
			$this->actSettings['USDgapBottom'] = $userSettings[0]['USDgapBottom'];
			$this->actSettings['USDgapTop'] = $userSettings[0]['USDgapTop'];
			$this->actSettings['thirtyDayMin'] = $userSettings[0]['thirtyDayMin'];
			$this->actSettings['highholdlimit'] = $userSettings[0]['highholdlimit'];
			$this->actSettings['highholdamt'] = $userSettings[0]['highholdamt'];
			}
	}
	
	
}

?>
