<?php
class Bitfinex{
	
	var $userid;
	var $apiKey;
	var $apiSec;
	var $nonceInc;
	
	
	
	
	// crypto kept in arrays based on symbol
	var $cryptoBalance = array();
	var $cryptoAvailable = array();
		
	var $cryptoPendingVal = array();
	var $cryptoPendingLends = array();
	var $cryptoPendingOffers = array();
	var $cryptoPendingAvg = array();
	var $cryptoPendingIDS = array();
	var $cryptoCurrentLends = array();
	var $cryptoCurrentLendVal = array();
	var $cryptoCurrentLendAvg = array();
	
	
	var $cryptoCurPrice = array();
		
	// USD treated seperately because legacy
	/*
	var $usdBalance;
	var $usdAvailable;
		
	var $usdPendingVal;
	var $usdPendingLends = array();
	var $usdPendingOffers;
	var $usdPendingAvg;
	var $usdPendingIDS = array();
	var $usdCurrentLends = array();
	var $usdCurrentLendVal;
	var $usdCurrentLendAvg;
	*/
		
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
		if(array_key_exists('price', $req)){$req['price'] = (string)$req['price'];}
       
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
	function bitfinex_updateHistory($numDays=10,  $sinceLast=0, $curOverride = '',$showDetails = false){
		global $config;
		set_time_limit(180);
		if($curOverride!=''){
			$cryptoPairs[0]['curSym'] = $curOverride;
			
		}
		else{
			// lets try to grab everything, even crypto they don't have balance for (just in case there was some yesterday)
			$sql = "SELECT * from `".$config['db']['prefix']."CurPairs` WHERE status = '1'";
			$cryptoPairs = $this->db->query($sql);
		}
		$splitDays = 25;
		foreach($cryptoPairs as $c){
		
			// no start date set, so grab the last timestamp from the db //
			if($sinceLast==0){
				$sql = "SELECT trans_id from `".$config['db']['prefix']."Tracking` WHERE user_id = '".$this->db->escapeStr($this->userid)."' AND trans_cur = '".$this->db->escapeStr($c['curSym'])."' ORDER BY trans_id DESC LIMIT 1";
				$lastId = $this->db->query($sql);
				$sinceLast = $lastId[0]['id']+1;
				if($sinceLast <=1){
					/* No Entries, Go Back 10 Days */
					$sinceLast = strtotime("-10 day");
				}
			}
			if($numDays == 0){
				// days since start point
				$numDays = ceil( (time() - $sinceLast)/86400);
			}
			$return =0;
			// we need to loop pull this since it will only return limited records per request
			$numLoops = ceil($numDays / $splitDays);
			for($x=0;$x<$numLoops;$x++){
				if($x%15 == 1 && $x > 1){
					if($showDetails){echo '<br>Pausing for a few seconds to avoid rate limit...';}
					sleep(10);
				}
				$thisSince = ($x * 86400 * $splitDays) + $sinceLast;
				$thisEnd = $thisSince + (86400 * $splitDays); 
				if($showDetails){echo '<br>Running '.$c['curSym'].' Loop '.$x.': Start Time '.$thisSince.' ('.date('Y-m-d', $thisSince).') | End Time '.$thisEnd.' ('.date('Y-m-d', $thisEnd).')';}
				
				$ledgerDetails = array('currency' => $c['curSym'], 'since' => (string)$thisSince, 'until' => (string)($thisEnd), 'wallet' => 'deposit');
				$ledgerHistory = $this->bitfinex_query('history', $ledgerDetails);
				
				
				if(isset($ledgerHistory['error']) && $ledgerHistory['error'] =='ERR_RATE_LIMIT'){
					// asking too fast, roll back $x-1 and wait 5 more seconds
					$x--;
					if($showDetails){echo '<br>Pausing because of rate limit error...';}
					sleep(5);
					continue;
				}
				else if(count($ledgerHistory) > 0 && !isset($ledgerHistory[0]['description'])){
					//print_r($ledgerHistory);
					if($showDetails){echo '<br>An error occured and we need to stop.  The last day run was '.date('Y-m-d', $lastRunTime);}
					break;
				}
				else if(count($ledgerHistory)>0){
					foreach($ledgerHistory as $l){
						// check its a swap payment //
						if(strtolower($l['description']) == strtolower('Swap Payment on wallet deposit') || strtolower($l['description'])==strtolower('Margin Funding Payment on wallet Deposit') ){	
							if($showDetails){echo '<br>Found Record: '.$l['timestamp'].' - '.$c['curSym'].' '.date('Y-m-d', $l['timestamp']).' '.$l['amount'].' '.$l['balance'];}
							$sql = "INSERT into `".$config['db']['prefix']."Tracking` (`user_id`, `trans_cur`, `trans_id`, `date`, `dep_balance`,`swap_payment`,`average_return`) VALUES 
								('".$this->db->escapeStr($this->userid)."', '".$this->db->escapeStr($c['curSym'])."', '".$this->db->escapeStr($l['timestamp'])."', '".$this->db->escapeStr(date('Y-m-d', $l['timestamp']))."', '".$this->db->escapeStr($l['balance'])."','".$this->db->escapeStr($l['amount'])."','".$this->db->escapeStr(round((($l['amount'] / $l['balance']) * 100),6))."')";
							$lastRunTime = $l['timestamp'];
							$upd = $this->db->iquery($sql);
							if($upd['id'] >0){$return++;}
						}
					}
				}
			}
			
		}
		if($showDetails){echo '<br><strong>Updated '.$return.' Records!';}
		return $return;
	}
	//
	function bitfinex_getLendBook($numAsks=500, $currency='USD'){
		//this should be done as a bitfinex_get but it refuses to pass get variables correctly, so i'm doing it cheap
		$data = file_get_contents('https://api.bitfinex.com/v1/lendbook/'.strtolower($currency).'/?limit_asks=500&limit_bids=0');
		$curRates =  json_decode($data, true);
		$tr=0;
		$totAmt = 0;
		unset($rates);
		unset($this->lendbook);
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
		
		echo "\n<br>Running Bot For User ".$this->userid;
		//  Step 1 - Cancel Pending Loans to return cash to available pool
		//  this will loop through all currency types
		$this->bitfinex_cancelPendingLoans();
		
		
		// how much we got?
		// since we just canceled some loans we need to update this
		sleep(5);
		$this->bitfinex_getDepositBalance();
		
		
		//loop for each cryptotype they have available
		foreach($this->cryptoAvailable as $key=>$ca){
			if($this->actSettings['status'][$key]!=2){
				// are they trying to extract some currency?
				//need to load the current price for this currency so we can figure out the minimum lend (BFX currently requires a minimum loan of $50 USD equiv)
				if($key != 'USD'){
					$this->bitfinex_getCurPrice($key);
					if($this->cryptoCurPrice[$key] > 0){
						$minForLend = round((50 / $this->cryptoCurPrice[$key]),5);
					}
					else{
						$minForLend = 50;
					}
				}
				else{
					$minForLend = 50;
				}
					
				if($ca > $minForLend && $this->actSettings['extractAmt'][$key] != -1 && $ca > $this->actSettings['extractAmt'][$key]){
					echo "\n<br>Running for ".$key." - ".$ca;
					//  Step 2 - Update the BFX Lendbook for current rates
					$this->bitfinex_getLendBook(500,$key);
					
					//  Step 3 - Figure out my Loan Splits and Rates
					$doLends = $this->bitfinex_getMyLendRates($key, $minForLend);
					//print_r($doLends);
					//  Step 4 & 5 - Create the Loan offers
					$this->bitfinex_createLoanOffers($doLends, $key);
				}
			}
		}
		echo "\n<Br>Completed";
	}
	
	function bitfinex_getCurPrice($cur='BTC'){
		if(!isset($this->cryptoCurPrice[$cur])){
			$curPrice = $this->bitfinex_get('pubticker',strtolower($cur).'usd');
			$this->cryptoCurPrice[$cur] = $curPrice['last_price'];
		}
		return $this->cryptoCurPrice[$cur];
	}
	
	function bitfinex_createLoanOffers($lendArray, $cur='USD'){
		if(count($lendArray)>0){
			foreach($lendArray[$cur] as $la){
				if($la['amt']>0){
					$offerNew = array('currency' => $cur, 'amount' => (string)$la['amt'],'rate' => (string)$la['rate'] ,'period' => (int)$la['time'],'direction' => 'lend');
					$newUSD = $this->bitfinex_query('offer/new', $offerNew);
					if($newUSD['message']!=''){
						$newUSD = $this->bitfinex_query('offer/new', $offerNew);
					}
				}
			}
		}
	}
	
	
	
	function bitfinex_cancelPendingLoans(){
		if(count($this->cryptoPendingIDS) > 0){
			foreach($this->cryptoPendingIDS as $key=>$p){
				foreach($p as $i){
					$offerCancel = array('offer_id' => $i);
					$cancel = $this->bitfinex_query('offer/cancel', $offerCancel);
				}
			}
		}
	}
	
	
	function bitfinex_getMyLendRates($cur='USD', $minForLend = 50){
		// How much do we lend at each rate point? 
		//  basically, we figure out how much we have to lend total,
		//  then divide it by the $spreadlend account setting to get
		//  a lend per amount.  If theres a highhold, we subtract
		//  that from the total and set it aside as a special lend
		
				
		$ca = $this->cryptoAvailable[$cur];
		// if its less than $50, we have nothing to do, since thats the minimum loan //
		if($ca >= $minForLend ){
			$a = 1;
			$splitAvailable = $ca;
			// Lets subtract the High Hold so we can make sure we're working with the right ammount
			if( $this->actSettings['highholdamt'][$cur] >= $minForLend ){
				$splitAvailable = $splitAvailable - $this->actSettings['highholdamt'][$cur];
				//checking to make sure the highhold isn't more than total available too...
				$loans[$cur][0]['amt'] = ($this->actSettings['highholdamt'][$cur] > $ca ? $ca : $this->actSettings['highholdamt'][$cur]);
				$loans[$cur][0]['rate'] = ($this->actSettings['highholdlimit'][$cur]*365);
				// always loan out highholds for 30 days... bascially we're pretty sure this is a high rate loan
				$loans[$cur][0]['time'] = 30;				
			}
			// is there anything left after the highhold?  if so, lets split it up //
			if( $splitAvailable >= $minForLend ){
				// How many splits do we want?
				// gotta make sure each split is bigger than the minimum loan of $50 USD equiv
				$numSplits = $this->actSettings['spreadlend'][$cur];
				$amtEach = floor(($splitAvailable / $numSplits)*100)/100;
				if($numSplits == 1){
					$amtEach = $splitAvailable;
				}
				else{
					while( $amtEach < $minForLend && $numSplits >= 0 ){
						$amtEach = floor(($splitAvailable / --$numSplits)*100)/100;
					}
				}
				// figure out the interest rate for each split, based on the current lend book and the gap settings
				//  this part is complicated, i'm documenting the best i can here....
				//  (This can and should be optimized, but it works and i wanted to get it out)
				if($numSplits >= 1){
					if($numSplits > 1){
						$gapClimb = ( ($this->actSettings['USDgapTop'][$cur] - $this->actSettings['USDgapBottom'][$cur])/($numSplits-1));
					}
					$nextlend = $this->actSettings['USDgapBottom'][$cur];
					//set annual minimum for calculations //
					$minLendRateAnnual = ($this->actSettings['minlendrate'][$cur]*365);
					foreach($this->lendbook as $l){
						while( ($l['totamt']>=$nextlend) && ($a <= $numSplits) ){
							$loans[$cur][$a]['amt'] = $amtEach;
							// Make sure the gap setting rate is higher than the minimum lend rate...
							// Old Version
							//$loans[$a]['rate'] = ( ($l['rate'] - .0001) > $this->actSettings['minlendrate'] ? ($l['rate'] - .0001) : $this->actSettings['minlendrate'] ) ;
							// NEW VERSION: ( 0.00365 / annual = 0.00001 / day )
							$loans[$cur][$a]['rate'] = ( ($l['rate'] - 0.00365) > $minLendRateAnnual ? ($l['rate'] - 0.00365) : $minLendRateAnnual ) ;
							//echo $loans[$a]['rate'].'<br>';
							
							//how long should we lend this out... as a rule, 2 days so we can cycle and get a high turnover
							// unless its above the threshold $this->actSettings['thirtyDayMin'], in which case we should lend it for the max 30 days
							// (basically, the rate is higher than normal, lets keep this loan out as long as possible)
							//  if $this->actSettings['thirtyDayMin'] = 0, always loan for 2 days, no matter what
							$loans[$cur][$a]['time'] = (($this->actSettings['thirtyDayMin'][$cur]>0)&&($l['rate'] > ($this->actSettings['thirtyDayMin'][$cur] * 365)) ? 30 : 2);
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
			// currency here is lower case for some reason, lets strtolower everything just in case it changes in the future
			if($cb['type']=='deposit'){
				$this->cryptoBalance[strtoupper($cb['currency'])] = $cb['amount'];
				$this->cryptoAvailable[strtoupper($cb['currency'])] = $cb['available'];
			}
		}
	}
	
	/* Grab current active loans */
	function bitfinex_getCurLoans(){
		unset($intReturn);// = 0;
		$curLends = $this->bitfinex_get('credits');
		foreach($curLends as $c){
			$this->cryptoCurrentLends[strtoupper($c['currency'])][] = $c;
			$this->cryptoCurrentLendVal[strtoupper($c['currency'])] += $c['amount'];
			$intReturn[strtoupper($c['currency'])] += ($c['amount']*( ($c['rate']/365)/100) );
		}
		// fixed for divide by 0
		if(count($this->cryptoCurrentLendVal)>0){
			foreach($this->cryptoCurrentLendVal as $key=>$lv){
				if($lv >0){
					$this->cryptoCurrentLendAvg[$key] = round( (($intReturn[$key] / $lv )*100),6);
				}
				else{
					$this->cryptoCurrentLendAvg[$key] = 0;
				}
			}
		}
		
	}
	
	/* Grab current pending loans */
	function bitfinex_getPendLoans(){
		// empty existing data
		unset($this->cryptoPendingVal);
		unset($this->cryptoPendingOffers);
		unset($this->cryptoPendingIDS);
		unset($this->cryptoPendingLends);
		unset($intReturn);
		
		//$intReturn = 0;
		$curOffers = $this->bitfinex_get('offers');
		foreach($curOffers as $o){
			$this->cryptoPendingVal[strtoupper($o['currency'])] += $o['remaining_amount'];
			$this->cryptoPendingOffers[strtoupper($o['currency'])]++;
			$this->cryptoPendingIDS[strtoupper($o['currency'])][] = $o['id'];
			$this->cryptoPendingLends[strtoupper($o['currency'])][] = $o;
			$intReturn[strtoupper($o['currency'])] += ($o['remaining_amount']*( ($o['rate']/365)/100) );
		}
		// fixed for divide by 0
		if(count($this->cryptoPendingVal)>0){
			foreach($this->cryptoPendingVal as $key=>$lv){
				if($lv >0){
					$this->cryptoPendingAvg[$key] = round( (($intReturn[$key] / $lv )*100),6);
				}
				else{
					$this->cryptoPendingAvg[$key] = 0;
				}
			}
		}
	}
	


	////////////////////////////////
	// Local BFX Account Settings //
	////////////////////////////////
	
	/* Grab current deposit balance */
	function bitfinex_getAccountSettings(){
		global $config;
		$sql = "SELECT * from `".$config['db']['prefix']."Vars` WHERE userid = '".$this->db->escapeStr($this->userid)."'";
		$userSettings = $this->db->query($sql);
		foreach($userSettings as $u){
			/* Good user, set all the variables */
			$this->actSettings['minlendrate'][strtoupper($u['curType'])] 	= $u['minlendrate'];
			$this->actSettings['spreadlend'][strtoupper($u['curType'])] 	= $u['spreadlend'];
			$this->actSettings['USDgapBottom'][strtoupper($u['curType'])] 	= $u['USDgapBottom'];
			$this->actSettings['USDgapTop'][strtoupper($u['curType'])] 		= $u['USDgapTop'];
			$this->actSettings['thirtyDayMin'][strtoupper($u['curType'])] 	= $u['thirtyDayMin'];
			$this->actSettings['highholdlimit'][strtoupper($u['curType'])] 	= $u['highholdlimit'];
			$this->actSettings['highholdamt'][strtoupper($u['curType'])] 	= $u['highholdamt'];
			$this->actSettings['extractAmt'][strtoupper($u['curType'])] 	= $u['extractAmt'];
			$this->actSettings['status'][strtoupper($u['curType'])] 		= $u['status'];
			}
	}
	
	
}

?>
