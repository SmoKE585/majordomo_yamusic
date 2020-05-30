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

$head = get_headers($array[0]['LINK'],1);

header('Content-Type: audio/mpeg');
header('Content-Length: '.$head["Content-Length"]);
header('X-Data-Size: '.$head["X-Data-Size"]);
header('Cache-Control: no-cache');

readfile($array[0]['LINK']);
?>
