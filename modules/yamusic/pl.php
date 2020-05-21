<?php
$playlistID = $_GET['playlistID'];
$owner = $_GET['owner'];
$shaffle = $_GET['shaffle'];
$songID = $_GET['songID'];
$secure = $_GET['secure'];

$array = json_decode(file_get_contents($secure.'://'.$_SERVER["SERVER_ADDR"].'/modules/yamusic/json.php?mode=track&playlist='.$playlistID.'&count=1&owner='.$owner.'&shaffle='.$shaffle.'&songID='.$songID), TRUE);

$link = $array[0]['LINK'];
header("Location: ".$array[0]['LINK']);

?>