<?php
$songID = $_GET['songID'];
$playlistID = $_GET['playlistID'];
$owner = $_GET['owner'];

if(!$songID) {
	http_response_code(404);
	die();
}

$array = json_decode(file_get_contents('http://'.$_SERVER["SERVER_ADDR"].'/modules/yamusic/json.php?mode=track&playlist='.$playlistID.'&owner='.$owner.'&songID='.$songID), TRUE);

header("Location: ".$array[0]['LINK']);
?>
