<?php
error_reporting(0); 


chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');



require('yamusic.class.php');

//?action=playOnTVPlaylist&obj='+object+'&prop='+prop+'&playlist='+playlist+'&owner='+owner,

if($_GET['action'] == 'playOnTVPlaylist') {
	$action = strip_tags($_GET['action']);
	$obj = strip_tags($_GET['obj']);
	$prop = strip_tags($_GET['prop']);
	$playlist = strip_tags($_GET['playlist']);
	$owner = strip_tags($_GET['owner']);
	
	$generateLink = 'browser|http://'.$_SERVER['SERVER_ADDR'].'/modules/yamusic/sendOnTV.php?playlist='.$playlist.'&owner='.$owner.'&shaffle=0';
	
	sg($obj.'.'.$prop, $generateLink);
}

if($_GET['action'] == 'changeUser') {
	$newUserID = strip_tags($_GET['newuser']);
	
	$class = new yamusic();
	$class->changeActiveUser($newUserID);
}

echo json_encode(array('status' => 'OK'));
?>