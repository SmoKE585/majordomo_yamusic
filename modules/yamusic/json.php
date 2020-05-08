<?php
chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');



require('yamusic.class.php');

if($_GET['mode'] == 'generate') {
	$songID = $_GET['songID'];
	$playlist = $_GET['playlist'];
	$count = $_GET['count'];
	
	$class = new yamusic();
	$array = $class->generateTrack($playlist, $owner, $songID, $count);
	echo json_encode($array);
}


?>