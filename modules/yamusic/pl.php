<?php
$songID = $_GET['songID'];
$playlistID = $_GET['playlistID'];
$owner = $_GET['owner'];

if(!$songID) {
	http_response_code(404);
	die();
}


error_reporting(0); 


chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');

require('yamusic.class.php');

$class = new yamusic();
$array = $class->generateTrack($playlistID, $owner, $songID);

//var_dump($array[0]['LINK']);
//die();

//$array = json_decode(file_get_contents('http://'.$_SERVER["SERVER_ADDR"].'/modules/yamusic/json.php?mode=track&playlist='.$playlistID.'&owner='.$owner.'&songID='.$songID), TRUE);

header('Content-Type: audio/mpeg');
header('Cache-Control: no-cache');

readfile($array[0]['LINK']);

//header("Location: ".$array[0]['LINK']);
?>
