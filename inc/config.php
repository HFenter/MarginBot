<?php
date_default_timezone_set('America/Los_Angeles');
setlocale(LC_MONETARY, 'en_US');
session_start();
// site Name
$config['app_name'] = 'MarginBot';
$config['app_version'] = '0.1';
$config['app_support_url'] = 'http://fuckedgox.com/MBot/';
$config['app_support_email'] = 'marginbot@fuckedgox.com';

// Local Database Connection Info //
$config['db']['host'] = 'localhost';
$config['db']['dbname'] = 'localdb';
$config['db']['dbuser'] = 'localuser';
$config['db']['dbpass'] = 'localpass';

//Local Admin Email //
$config['admin_email'] = 'support@fuckedgox.com';

// Current Fees Charged by BFX for Margin Swaps (15% as of Nov. 2014)
$config['curFeesBFX'] = 15;





?>