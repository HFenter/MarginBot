<?php
date_default_timezone_set('America/Los_Angeles');
setlocale(LC_MONETARY, 'en_US');
session_start();
require_once('version_info.php');

// Local Database Connection Info //
$fileContent = file_exists('inc/config.json') ? file_get_contents('inc/config.json') : file_get_contents('../inc/config.json');
$privateInfo = json_decode($fileContent , true);
$config['db']['host'] = $privateInfo['database']['host'];
$config['db']['dbname'] = $privateInfo['database']['dbname'];
$config['db']['dbuser'] = $privateInfo['database']['dbuser'];
$config['db']['dbpass'] = $privateInfo['database']['dbpass'];

// this is included in front of each database table name
$config['db']['prefix'] = $privateInfo['database']['prefix'];

//Local Admin Email //
$config['admin_email'] = $privateInfo['email'];

// Current Fees Charged by BFX for Margin Swaps (15% as of Nov. 2014)
$config['curFeesBFX'] = $privateInfo['fee'];
?>