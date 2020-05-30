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

$newDOM->rotorStationGenreFeedbackRadioStarted('dance', 'mobile-radio-user-'.$loadUserInfo['UID']);
$newDOM->rotorStationGenreFeedback('dance', 'trackStarted', 'mobile-radio-user-'.$loadUserInfo['UID'], $batchId, $trackId);
$newDOM->rotorStationGenreFeedback('dance', 'trackFinished', 'mobile-radio-user-'.$loadUserInfo['UID'], $batchId, $trackId);

$newDOM->rotorStationTracks('dance', true, $trackId);

$radio = $newDOM->rotorStationGenreTracks('dance');
//$track = $newDOM->tracks($trackId);


//$link = $newDOM->tracksDownloadInfo($trackId, true);
//$link = $link[0]->directLink;

foreach($radio->sequence as $key => $value) {
	$idtra .= '<br>KEY - '.$key.'<br>';
	$idtra .= 'ID - '.$value->track->id.'<br>';
	$idtra .= 'Batch - '.$radio->batchId.'<hr>';
	$idtra .= 'Name - '.$value->track->title.'<hr>';
}


echo '<pre>';
var_dump($idtra);
die();

header('Content-Type: audio/mpeg');
header('Cache-Control: no-cache');

readfile($link);


?>