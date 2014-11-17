<?
$headers['title'] = "Dashboard";
require_once('inc/header.php');


$gen->showWarnings($warning);
$gen->showAlerts($alert);



// Show the Active Page
$pages->showPage();


// Show Footer //
require_once('inc/footer.php');
?>