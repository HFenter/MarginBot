<?php
date_default_timezone_set('America/Los_Angeles');
setlocale(LC_MONETARY, 'en_US');
session_start();
require_once('version_info.php');

// Local Database Connection Info //
$private_info = json_decode(file_get_contents("inc/config.json") , true);
$config['db']['host'] = $private_info['host'];
$config['db']['dbname'] = $private_info['dbname'];
$config['db']['dbuser'] = $private_info['dbuser'];
$config['db']['dbpass'] = $private_info['dbpass'];

// this is included in front of each database table name
$config['db']['prefix'] = 'BFXLendBot_';

//Local Admin Email //
$config['admin_email'] = 'pswerlang@gmail.com';

// Current Fees Charged by BFX for Margin Swaps (15% as of Nov. 2014)
$config['curFeesBFX'] = 15.0000;
?>