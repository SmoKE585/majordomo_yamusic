<?php
error_reporting(0); 


chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');

require_once('yamusic.class.php');
require_once('client.php');

$class = new yamusic();

//Выгрузим всех юзеров
$loadAllUsers = $class->loadAllUser();
//Найдем основного юзера
foreach($loadAllUsers as $value) {
	if($value['SELECTED'] == 1) {
		$mainUser = $value['ID'];
		break;
	}
}

//Выгрузим инфо о основном юзере
$loadUserInfo = $class->loadUserInfo($mainUser);
//Создадим класс подключения к Яше
$newDOM = new Client($loadUserInfo['TOKEN']);
//Выгрузим массив с радио POP
$radio = $newDOM->rotorStationGenreTracks('dance');

//ID подборки треков
$batchId = $radio->batchId;
(empty($_GET['id'])) ? $trackPos = 0 : $trackPos = $_GET['id'];
$trackId = $radio->sequence[$trackPos]->track->id;

$newDOM->rotorStationGenreFeedbackRadioStarted('dance');
$newDOM->rotorStationGenreFeedback('dance', 'trackStarted', null, $batchId, $trackId);
$newDOM->rotorStationGenreFeedback('dance', 'skip', null, $batchId, $trackId);

$newDOM->rotorStationTracks('dance', true, $trackId);

$track = $newDOM->tracks($trackId);


$link = $newDOM->tracksDownloadInfo($trackId, true);
$link = $link[0]->directLink;

//echo '<pre>';
//var_dump($link);


header('Content-Type: audio/mpeg');
header('Cache-Control: no-cache');

readfile($link);


?>