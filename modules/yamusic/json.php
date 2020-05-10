<?php
error_reporting(0); 


chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');



require('yamusic.class.php');

if($_GET['mode'] == 'track') {
	$songID = $_GET['songID'];
	$playlist = $_GET['playlist'];
	$count = $_GET['count'];
	$next = $_GET['next'];
	$shaffle = $_GET['shaffle'];
	
	$class = new yamusic();
	$array = $class->generateTrack($playlist, $owner, $songID, $count, $shaffle, $next, $prev);
	echo json_encode($array);
}


?>