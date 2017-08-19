<?php
/*------------------------------------------------------------------------------------------
 * General Sitewide Functions and Objects
 *------------------------------------------------------------------------------------------
 */
class General {
	
	var $cryptoPairs = array();

	public function __construct() {
		global $db, $config;
		$this->db = $db;
		
		if(!stristr($_SERVER["SCRIPT_URL"],'update.php') && !stristr($_SERVER["SCRIPT_URL"],'install.php') ){
			$exists = $this->db->query("SHOW TABLES like '".$config['db']['prefix']."CurPairs'");
			if(count($exists)==0){
				$this->doRedirect('update.php');
			}
			else{
				$sql = "SELECT * from `".$config['db']['prefix']."CurPairs` WHERE status = '1'";
				$crypto = $this->db->query($sql);
				if(count($crpto)<=0){
					
				}
				
				foreach($crypto as $c){
					// make it into a pretty array
					$this->cryptoPairs[$c['curSym']] = $c;
				}
			}
		}
		
	}

	/*
	 * General Functions
	 */
	
	public function moneyFormat($val){
		// money_format() function isn't available in windows.  replaces with  numberformat
		// return money_format('%.2n', $val);
		//if($val==''){$val = 0;}
		return '$'.number_format($val, 2);
	}
	public function cryptoFormat($val, $places=6, $sym=''){
		// money_format() function isn't available in windows.  replaces with  numberformat
		// return money_format('%.2n', $val);
		//if($val==''){$val = 0;}
		if($sym == 'USD'){
			return '$'.number_format($val, 2);
		}
		else{
			return $sym.' '.number_format($val, $places);
		}
	}
	
	public function percentFormat($val, $round=5){
		//if($val==''){$val = 0;}
		return number_format($val, $round).' %';
	}
	
	
	//find urls, convert them to links
	public function changeToLinks($text, $doShowVisit=0){
		//url//
		$in=array('`((?:https?|ftp)://\S+[[:alnum:]]/?)`si','`((?<!//)(www\.\S+[[:alnum:]]/?))`si');
		$out=array('<a href="$1" rel="nofollow" target="_outLink">$1</a>','<a href="http://$1" rel="nofollow" target="_outLink">$1</a>');
		if($doShowVisit == 1){
			$out=array('<a href="$1" rel="nofollow" target="_outLink">Visit Site</a>','<a href="http://$1" rel="nofollow" target="_outLink">Visit Site</a>');
		}
		$text = preg_replace($in,$out,$text);
		//email//
		$in=array('`([^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4})`si');
		$out=array('<a href="mailto:$1" rel="nofollow">Send Email</a>');
		$text =  preg_replace($in,$out,$text);
		return $text;
	}
	
	
	//change plain text title into url (ex: "Cool Show & Stuff" -> "Cool_Show_and_Stuff")
	public function title2url($title, $maxlength = 100){
		$title = htmlspecialchars(urldecode($title));
		$bad1 = array(' ', '/', '?','!','.',',', '%','#');
		$bad2 = array('&','+');
		$title = str_replace($bad1, '_', $title);
		$title = str_replace($bad2, 'and', $title);
		
		$code_entities_match = array( '&quot;' ,'!' ,'@' ,'#' ,'$' ,'%' ,'^' ,'&' ,'*' ,'(' ,')' ,'+' ,'{' ,'}' ,'|' ,':' ,'"' ,'<' ,'>' ,'?' ,'[' ,']' ,'' ,';' ,"'" ,',' ,'.' ,'_' ,'/' ,'*' ,'+' ,'~' ,'`' ,'=' ,' ' ,'---' ,'--','--');
		$code_entities_replace = array('' ,'-' ,'-' ,'' ,'' ,'' ,'-' ,'-' ,'' ,'' ,'' ,'' ,'' ,'' ,'' ,'-' ,'' ,'' ,'' ,'' ,'' ,'' ,'' ,'' ,'' ,'-' ,'' ,'-' ,'-' ,'' ,'' ,'' ,'' ,'' ,'-' ,'-' ,'-','-');
		$title = str_replace($code_entities_match, $code_entities_replace, $title);
		if(strlen($title) > $maxlength){
			$title = substr($title, 0, $maxlength);
		}
		return $title;
	}
	
	//take long text, shorten it to length, then count back 1 char until you find a space (for clean breaks).
	public function cutText($string, $length, $noHellip=0){
	    if(strlen($string) > $length){
			$lengthTrim = $length;
			while ($string{$length} != " " && $length > 0) {
		        $length--;
		    }
			if($length != 0){
			    return substr($string, 0, $length). ($noHellip==1 ? '' : '&hellip;');
				}
			else{
				return substr($string, 0, $lengthTrim);
				}
			}
		else{
			return $string;
			}
	}
	
	public function encode_email($e){
		for ($i = 0; $i < strlen($e); $i++) { $output .= '&#'.ord($e[$i]).';'; }
		return $output;
	}
	
	// EMAILRIFIC TYPE FUNCTIONALITIENESS (Of Much Happy Fun Joy) //
	public function checkValidEmail($addy){
		list($userName, $mailDomain) = split("@", $addy);
		if(!stristr($mailDomain,'.')){
			return false;
		}
		else if(checkdnsrr($mailDomain, "MX")){
			return true;
		}
		else{
			return false;
		}
	}
	
	###############################
	##		time functions       ##
	###############################
	// age stuff //
	public function getAge($date) {
		$then = strtotime($date);
		return(floor((time()-$then)/31556926));
	}
	
	// Convert $num_secs to Hours:Minutes:Seconds
	public function sec2hms($num_secs) {
		$str = '';
		$hours = intval(intval($num_secs) / 3600);
		if($hours > 0) $str .= $hours.':';
		$minutes = intval(((intval($num_secs) / 60) % 60));
		if ($minutes < 10) $str .= '0';
		$str .= $minutes.':';
		$seconds = intval(intval(($num_secs % 60)));
		if ($seconds < 10) $str .= '0';
		$str .= $seconds;
		return($str);
	}
	// convert post time to friendly text 
	public function howLongToExpire($expireTime){
		if($expireTime==''){return "Never";}
		//$expireTime = strtotime($expireTime);
		//$now = time()-(3600 * 7);
		$difference = ($expireTime -  time()) / 60;
		
		if($difference <= 1){$difText = " Any Second ";}
		elseif($difference <= 5){$difText = " A Few Minutes";}
		elseif($difference <= 1440){$difText = " About ".ceil($difference / 60)." Hours";}
		elseif($difference >= 1440 && $difference <= 2160){$difText = " 1 Day ";}
		elseif($difference >= 1440){ $difText = ceil($difference / 1440)." Days";}
		return $difText;
	}
	
	
	// convert post time to friendly text 
	public function howLongAgo($date, $showDate=0){
		if($date==''){return "Never";}
		$date = strtotime($date);
		$now = time()-(3600 * 7);
		$difference = ($now - $date) / 60;
		
		/*
		echo '<br>Date : '.$date.'<br>Now: '.$now.'<br>Diff: '.$difference;
		$seconds = date_offset_get(new DateTime);
		print '<br>timeoffset: '.$seconds / 3600;
		*/	
		if($difference <= 1){$difText = " Just a moment ago ";}
		elseif($difference <= 2){$difText = " A few minutes ago ";}
		elseif($difference <= 30){$difText = " Half an hour ago ";}
		elseif($difference <= 60){$difText = " About an hour ago ";}
		elseif($difference >= 60 && $difference <= 240){$difText = " A few hours ago ";}
		elseif($difference >= 240 && $difference <= 480){$difText = " About 6 hours ago ";}
		elseif($difference >= 480 && $difference <= 840){$difText = " About 12 hours ago ";}
		elseif($difference >= 840 && $difference <= 1440){$difText = " About 16 hours ago ";}
		elseif($difference >= 1440 && $difference <= 2880){$difText = " Yesterday ";}
		
		elseif($difference >= 1440 && $showDate==0){ $difText = ceil($difference / 1440)." Days ago ";}
		else{ 
			if(date('Y', $date) == date('Y', $now)){
				$difText = date('F jS', $date);
			}
			else{
				$difText = date('F jS, Y', $date);
			}
		}
		
		return $difText;
	}
	
	// convert date range to friendly text
	public function getDateRangeDisplay($start, $end, $detail = 1){
		if($detail == 1){
			$fS = "F jS, Y";
			$sS = "F j";
			$sE = "jS, Y";
			$dE = "F jS, Y";
		}
		elseif($detail == 2){
			$fS = "M j";
			$sS = "M j";
			$sE = "j";
			$dE = "M j";
		}
	
		if($start == $end || $end == '0000-00-00'){
			$showDate = date($fS, strtotime($start));
			}
		else{
			if( date("F", strtotime($start)) == date("F", strtotime($end)) ){
				$showDate = date($sS, strtotime($start)).' - ' .date($sE, strtotime($end));
				}
			else{
				$showDate = date($sS, strtotime($start)).' - ' .date($dE, strtotime($end));
				}
			}
		return $showDate;
	}
	
	public function isBot(){
		$bots = array ("googlebot","webcrawler","grub.org","slurp","openfind","antibot","netresearchserver","nutch","ia_archiver","scooter","fluffy");
		foreach($bots as $b){
			if(strstr(strtolower($_SERVER["HTTP_USER_AGENT"]),$b)){
				return True;
			}
		}
		return False;
	}
	public function isMobileBrowser(){
		$isMobile = false;
	
	$op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
	$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ac = strtolower($_SERVER['HTTP_ACCEPT']);
	$ip = $_SERVER['REMOTE_ADDR'];

	$isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
        || $op != ''
        || strpos($ua, 'sony') !== false 
        || strpos($ua, 'symbian') !== false 
        || strpos($ua, 'nokia') !== false 
        || strpos($ua, 'samsung') !== false 
        || strpos($ua, 'mobile') !== false
        || strpos($ua, 'windows ce') !== false
        || strpos($ua, 'epoc') !== false
        || strpos($ua, 'opera mini') !== false
        || strpos($ua, 'nitro') !== false
        || strpos($ua, 'j2me') !== false
        || strpos($ua, 'midp-') !== false
        || strpos($ua, 'cldc-') !== false
        || strpos($ua, 'netfront') !== false
        || strpos($ua, 'mot') !== false
        || strpos($ua, 'up.browser') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'audiovox') !== false
        || strpos($ua, 'blackberry') !== false
        || strpos($ua, 'ericsson,') !== false
        || strpos($ua, 'panasonic') !== false
        || strpos($ua, 'philips') !== false
        || strpos($ua, 'sanyo') !== false
        || strpos($ua, 'sharp') !== false
        || strpos($ua, 'sie-') !== false
        || strpos($ua, 'portalmmm') !== false
        || strpos($ua, 'blazer') !== false
        || strpos($ua, 'avantgo') !== false
        || strpos($ua, 'danger') !== false
        || strpos($ua, 'palm') !== false
        || strpos($ua, 'series60') !== false
        || strpos($ua, 'palmsource') !== false
        || strpos($ua, 'pocketpc') !== false
        || strpos($ua, 'smartphone') !== false
        || strpos($ua, 'rover') !== false
        || strpos($ua, 'ipaq') !== false
        || strpos($ua, 'au-mic,') !== false
        || strpos($ua, 'alcatel') !== false
        || strpos($ua, 'ericy') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'vodafone/') !== false
        || strpos($ua, 'wap1.') !== false
        || strpos($ua, 'wap2.') !== false;

        return $isMobile ;
	}
	// Try Header Redirect
	public function doRedirect($location){
		if (!headers_sent()){
		header('Location: '.$location);
		exit;
		}
		// Header already sent.  Script redir them.
		else{
			echo '<script>window.location = "'.$location.'";</script>';
			exit;
		}
	}
	

	
	public function doSendEmailFromUser($fromAddy, $toAddy, $subject, $message, $allowHTML=0){
		if($this->checkValidEmail($fromAddy) && $this->checkValidEmail($toAddy)){
			$subject = $this->cutText(strip_tags($subject), 100);
			if($allowHTML==0){
				$message = strip_tags($message);
			}
			$headers = 'From: '.$fromAddy. "\r\n" .
		    'Reply-To: '.$fromAddy. "\r\n" .
		    'X-Mailer: FuckedGox.com Mailer : 1.0';
			if(mail($toAddy, $subject, $message, $headers)){
				$return['sts'] = 1;
			}
			else{
				$return['sts'] = 0; 
				$return['error'] = 'Mail send failed.';
			}
		}
		else{
			$return['sts'] = 0; 
			$return['error'] = 'Email address appears invalid.';
		}
	return $return;
	}
	public function doSendEmailFromSite($toAddy, $subject, $message){
		if($this->checkValidEmail($toAddy)){
			$subject = $this->cutText(strip_tags($subject), 100);
			$headers = "From: Webmaster@FuckedGox.com\r\n" .
		    "Reply-To: Webmaster@FuckedGox.com\r\n" .
		    'X-Mailer: FuckedGox.com Mailer : 1.0';
			if(mail($toAddy, $subject, $message, $headers)){
				$return['sts'] = 1;
			}
			else{
				$return['sts'] = 0; 
				$return['error'] = 'Mail send failed.';
			}
		}
		else{
			$return['sts'] = 0; 
			$return['error'] = 'Email address appears invalid.';
		}
	return $return;
	}
	
	
	function checkCronStatus(){
		global $config, $alert, $warning;
		$cronURL = 'http://'.$_SERVER["HTTP_HOST"].dirname($_SERVER['PHP_SELF']).'/crons/';
		$cronSql1 = $this->db->query("select lastrun from `".$config['db']['prefix']."CronRuns` where cron_id = 1 ORDER BY lastrun desc LIMIT 1");
		if (count($cronSql1) ==  1) {
			$age1 = floor((time()-strtotime($cronSql1[0]['lastrun'])));
			if($age1 > 7200){
				// more than 120 minutes has passed, the cron must not be running //
				$warning[] = '<strong>It appears the hourly cron hasn\'t been running</strong>.  The last time it ran was <em>'.date('g:i:s a, F jS, Y', strtotime($cronSql1[0]['lastrun'])).'</em>.  Check to make sure your cronjob contains a job similar to:
						<br>5 * * * * wget -qO- '.$cronURL.'HourlyCron.php >/dev/null 2>&1';
			}
		}
		else{
			// no record of  the cron running //
			$warning[] = '<strong>It appears you have not set up the hourly cron, or it hasn\'t been running</strong>.  Check to make sure your cronjob contains a job similar to:
					<br>5 * * * * wget -qO- '.$cronURL.'HourlyCron.php >/dev/null 2>&1';
		}
		$cronSql2 = $this->db->query("select lastrun from `".$config['db']['prefix']."CronRuns` where cron_id = 2 ORDER BY lastrun desc LIMIT 1");
		if (count($cronSql2) ==  1) {
			$age2 = floor((time()-strtotime($cronSql2[0]['lastrun'])));
			if($age2 > 1200){
				// more than 20 minutes has passed, the cron must not be running //
				$warning[] = '<strong>It appears the 10 minute cron hasn\'t been running</strong>. The last time it ran was <em>'.date('g:i:s a, F jS, Y', strtotime($cronSql2[0]['lastrun'])).'</em>.Check to make sure your cronjob contains a job similar to:<br>01,11,21,31,41,51 * * * * wget -qO- '.$cronURL.'TenMinuteCron.php >/dev/null 2>&1';
			}
		}
		else{
			// no record of  the cron running //
			$warning[] = '<strong>It appears you have not set up the 10 minute cron, or it hasn\'t been running</strong>. Check to make sure your cronjob contains a job similar to:<br>01,11,21,31,41,51 * * * * wget -qO- '.$cronURL.'TenMinuteCron.php >/dev/null 2>&1';
		}
		
		
	}
		
	
	
	
	function showWarnings($warning){
		foreach($warning as $w){
			echo '
				<div class="alert alert-danger alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					'.$w.'
				</div>';
		}
	}
	function showNotice($notice){
		foreach($notice as $n){
			echo '
				<div class="alert alert-info alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					'.$n.'
				</div>';
		}
	}
	function showAlerts($alert){
		foreach($alert as $a){
			echo '
				<div class="alert alert-success alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					'.$a.'
				</div>';
		}
	}


	function showSiteModals(){
		$this->showAffiliate();
		$this->showDonate();
		$this->showDisclaimer();
	}

	function showAffiliate(){
		global $config;
		echo '   
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
					<p>If you sign up for Bitfinex.com using this <a href="https://www.bitfinex.com/?refcode=vsAnxuo5bM">referal link</a> ( Code: vsAnxuo5bM ), you\'ll get 10% off all fees on trade and swap activity for the first 30 days.</p>
					<p>Doing so costs you nothing, and supports the continued development of this software.</p>
					<p>If you do sign up using our Referal code, <strong>make sure to <a href="mailto:'.$config['app_support_email'].'">send us an email</a>, and we\'ll add you to our supporter list</strong>.  Supporters get first access to '.$config['app_name'].' updates, priority technical support ( when available ), and priority when requesting new features.</p>
					<p style="text-align:center"><a href="https://www.bitfinex.com/?refcode=vsAnxuo5bM" class="btn btn-success btn-lg" style="width:250px;" target="bfx">Join Bitfinex Now!</a></p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				  </div>
				</div>
			  </div>
			</div>
		';
	}
	function showDonate(){
		global $config;
		
		$donateArray = array("bitcoin"=>"17x2SsY8bpZVC4QH8Va3Qz7HggaDshhjjr","litecoin"=>"Lf9Tt7HVJJsNVtbnNwa4a3C6deUmGCvsLx","ethereum"=>"0xf159d969e065b20083b50c930b71d29364ae5247","ethereumc"=>"0xf159d969e065b20083b50c930b71d29364ae5247","zcash"=>"t1UMRSWQDSyYncKkVvwFdcpu6LaZNehakTj","iota"=>"JOTXTNQRGEWY9DNTRDNKZFJNOQZMYUTYBUQJFJFKKUYMUUTY9QYMCDUHDBZRHKXBWEGCUZ9ZVIFKAVJBYP9QEEKTMD","bcash"=>"1FLmuLbjLZ1Ym53GrTqd9mhMtpCWrx9WSU");
		
		echo '
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
					<p>If you do send a donation,  <strong>make sure to <a href="mailto:'.$config['app_support_email'].'">send us an email</a>, and we\'ll add you to our supporter list</strong>.  Supporters get first access to '.$config['app_name'].' updates, priority technical support ( when available ), and priority when requesting new features.</p>
					<p>You can send donations to:</p>
					<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					';
					
					foreach($donateArray as $key=>$da){
						 echo '
							  <div class="panel panel-default">
								<div class="panel-heading" role="tab" id="heading'.$key.'">
								  <h4 class="panel-title">
									<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse'.$key.'" aria-expanded="false" aria-controls="collapse'.$key.'">
									  Donate '.strtoupper($key).'
									</a>
								  </h4>
								</div>
								<div id="collapse'.$key.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$key.'">
								  <div class="panel-body">
										<div class="media" style="border-bottom:1px solid #e5e5e5;padding-bottom:20px;" >
										  <a class="media-left">
											<img src="https://chart.googleapis.com/chart?chs=175x175&chld=M%7C0&cht=qr&chl='.$da.'" alt="Dontate '.strtoupper($key).': '.$da.'">
										  </a>
										  <div class="media-body" style="vertical-align: middle;">
											<h4 class="media-heading" style="max-width: 360px;word-wrap: break-word;">'.strtoupper($key).': '.$da.'</h4>
										  </div>
										</div>
								  </div>
								</div>
							  </div>';
					}
					
					echo '
					</div>
					
			
					</div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				  </div>
				</div>
			  </div>
			</div>
			';
		
		
	}
	
	function showDisclaimer(){
		echo '
			<!-- Disclaimer Modal -->
			<div class="modal fade" id="disclaimerModal" tabindex="-1" role="dialog" aria-labelledby="disclaimerModalLabel" aria-hidden="true">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<div class="pull-left" style="margin-right:20px;font-size: 22px;">
						<span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
					</div>
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					<h4 class="modal-title" id="disclaimerModalLabel">'.$config['app_name'].' '.$config['app_version'].'.'.$config['app_version_minor'].' and Terms of Use</h4>
				  </div>
				  <div class="modal-body">
					<p>Alright, we could put a big huge block of text here written by dozens of lawyers requiring you to sign away your first born child and all your Dogecoin (are those still a thing?), but I\'d rather keep this simple and straightforward.  So, heres the deal:</p>
					<h4>The "Deal"</h4>
					<p>This software is provided as is, with no warrantee or guarantee or promise of any kind.  We\'re pretty sure it works, at least for the most part, and we use it ourselves.  But there\'s probably a few things that don\'t work, it is software after all, and <em><strong>ALL</strong></em> software has at least a few bugs ( <a href="mailto:'.$config['app_support_email'].'">report them here</a> ).  We did our best to insure that those bugs are small, and not show stopping, but we don\'t even promise this.</p>
					<p>More importantly, you will be providing your BFX API Key to this software in order for it to function.  This comes with a lot of security risks.  This bot basically has full access to your Bitfinex Account.  It has to in order to be useful and do anything.  The API currently limits actions that can be taken, so removing money from your account isn\'t possible, but we can\'t promise their API will be this way forever. Besides, if someone malicious did get access to your API Key, the may not be able to directly take your money, but they could force your account to make a bunch of ridiculous orders or something else that you probably wouldn\'t be too happy about.</p>
					<p> We\'ve done our best to secure this Bot against hacker attacks, but at the end of the day security for this bot, and the server it lives on, is <em><strong>YOUR</strong></em> responsibility.
						Follow all security best practices, make sure to password protect everything, don\'t give anyone you don\'t trust explicitly access to the bot, don\'t advertise that you\'ve installed it on your servers and make yourself a target, etc.</p>
					 <p>When you download this software, make sure to only download it from the original GitHub repository.  Don\'t EVER DOWNLOAD any software from an untrusted source.  They could very easily modify the software to steal everything in your Bitfinex account.</p>
					 <p>Also, remember you have the full source code, right here, in your hands.  Feel free to check through the code, just to make sure you understand whats happening and that we\'re not doing anything Nefarious with your API access (we\'re not, we promise, but don\'t take our word for it, look through the code).</p>
					 <p>Huh, turns out that was a bit longer than I intended after all....   Lets make this easier:</p>
					<h3>TL;DR</h3>
					<p><em><strong>Use At Your Own Risk.  Something goes wrong, not our problem!</strong></em></p>
					 
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				  </div>
				</div>
			  </div>
			</div>';
		
	}
	
	
	function showCurrencyNav(){
		// bitfinex API is very inconsistant with upper and lower case
		foreach($this->cryptoPairs as $c){
			$return .= '<li><a href="index.php?funding='.$c['curSym'].'">'.$c['curName'].'</a></li>
			';
		}
		return $return;
	}
	
	function symbol2name($sym){
		// bitfinex API is very inconsistant with upper and lower case
		$sym = strtoupper($sym);
		
		/* Switched to DB Controlled for easier updates (bitfinex changes these a lot)
		$symArray['USD'] = 'US Dollars';
		$symArray['BTC'] = 'Bitcoin';
		$symArray['IOT'] = 'Iota';
		$symArray['ETH'] = 'Ethereum';
		$symArray['OMG'] = 'OmiseGO';
		$symArray['BCH'] = 'Bcash';
		$symArray['EOS'] = 'EOS';
		$symArray['ETC'] = 'Ethereum Classic';
		$symArray['DSH'] = 'Dash';
		$symArray['XMR'] = 'Monero';
		$symArray['ZEC'] = 'Zcash';
		$symArray['XRP'] = 'Ripple';
		*/
		
		
		
		
		
		return $this->cryptoPairs[$sym]['curName'];
		
	}
	
}
?>