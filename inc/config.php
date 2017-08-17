<?php
date_default_timezone_set('America/Los_Angeles');
setlocale(LC_MONETARY, 'en_US');
session_start();
require_once('version_info.php');

// Local Database Connection Info //
$config['db']['host'] = '';
$config['db']['dbname'] = '';
$config['db']['dbuser'] = '';
$config['db']['dbpass'] = '';

// this is included in front of each database table name
$config['db']['prefix'] = '';

//Local Admin Email //
$config['admin_email'] = 'marginbot@therovegroup.com';

// Current Fees Charged by BFX for Margin Swaps (15% as of Nov. 2014)
$config['curFeesBFX'] = 15;





?>