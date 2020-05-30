<?php
error_reporting(0); 

chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');

require('yamusic.class.php');

$class = new yamusic();
//Лезем в конфиг
$class->getConfig();

//GET
$playlistID = strip_tags($_GET['playlist']);
$owner = strip_tags($_GET['owner']);
$shaffle = strip_tags($_GET['shaffle']);


if($_GET['onstart'] == 1) {
	$class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY'] = 0;
	$class->saveConfig();
	header('Location: play.php?playlist='.$playlistID.'&owner='.$owner);
}

//Лезем в m3u файл за ссылками
$getm3uFile = file_get_contents(DIR_MODULES.$class->name.'/m3u8/pl_'.$playlistID.'_'.$owner.'.m3u');
$getm3uFile = explode('#EXTINF:0, ', $getm3uFile);

$getTrackURL = [];
$countTrack = 0;

//Циклом получаем ссылки из плейлиста
foreach($getm3uFile as $key => $value) {
 	$getTrackURLSearch = trim(stristr($value, 'http://'));
 	if(!$getTrackURLSearch) continue; 
	array_push($getTrackURL, $getTrackURLSearch);
	$countTrack++;
}

//Записываем конфиг
if($class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY'] >= $countTrack) $class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY'] = 0;

$class->config['PLAY_PAGE_'.$playlistID.'_PLAYLISTID'] = $playlistID;

//Если нет инфы по плейлисту - то начинаем с 0
if(empty($class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY'])) {
	$class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY']++;
	$currentPlay = 0;
} else {
	$currentPlay = $class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY'];
	$class->config['PLAY_PAGE_'.$playlistID.'_CURRENT_PLAY']++;
}

//Получаем владельца и ID плейлиста
$getURLParams = preg_replace('/[^0-9]/', '', explode('&', $getTrackURL[$currentPlay]));

//Получаем ссылку
$array = $class->generateTrack($playlistID, $getURLParams[1], $getURLParams[2]);
//Получаем у Яндекса хедерсы
$head = get_headers($array[0]['LINK'],1);

//Хедерсы
header('Content-Type: audio/mpeg');
header('Content-Length: '.$head["Content-Length"]);
header('X-Data-Size: '.$head["X-Data-Size"]);
header('Cache-Control: no-cache');
//В браузер
readfile($array[0]['LINK']);


$class->saveConfig();
?>