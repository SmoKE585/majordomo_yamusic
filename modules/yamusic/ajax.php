<?php
error_reporting(0); 


chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');



require('yamusic.class.php');

//?action=playOnTVPlaylist&obj='+object+'&prop='+prop+'&playlist='+playlist+'&owner='+owner,

if($_GET['action'] == 'playOnTV') {
	$action = strip_tags($_GET['action']);
	$obj = strip_tags($_GET['obj']);
	$prop = strip_tags($_GET['prop']);
	$playlist = strip_tags($_GET['playlist']);
	$owner = strip_tags($_GET['owner']);
	$songID = strip_tags($_GET['songID']);
	
	$generateLink = 'browser|http://'.$_SERVER['SERVER_ADDR'].'/modules/yamusic/sendOnTV.php?playlist='.$playlist.'&owner='.$owner.'&shaffle=0&songID='.$songID;
	
	sg($obj.'.'.$prop, $generateLink);
}

if($_GET['action'] == 'changeUser') {
	$newUserID = strip_tags($_GET['newuser']);
	
	$class = new yamusic();
	$class->changeActiveUser($newUserID);
}

if($_GET['action'] == 'saveVolume') {
	$chanel = strip_tags($_GET['chanel']);
	$value = strip_tags($_GET['value']);
	
	$class = new yamusic();
	$class->setAudioVolume($chanel, $value);
}

if($_GET['action'] == 'saveTerminal') {
	$terminal = strip_tags($_GET['terminal']);
	
	$class = new yamusic();
	
	$class->getConfig();
	$class->config['MAIN_TERMINAL'] = $terminal;
	$class->saveConfig();
}

if($_GET['action'] == 'sendOnTerminal') {
	$playlist = urldecode(strip_tags($_GET['pl']));
	$terminal = urldecode(strip_tags($_GET['terminal']));
	
	//$class = new yamusic();
	
	include_once (DIR_MODULES . 'app_player/app_player.class.php');
    $player = new app_player();
    $player->play_terminal = $terminal;
    // Имя терминала
    $player->command = ($safe_play ? 'safe_play' : 'play');
    // Команда
    $player->param = $playlist;
    // Параметр
    $player->ajax = TRUE;
    $player->intCall = TRUE;
    $player->usual($out);
    $status = $player->json['message'];
}

if($_GET['action'] == 'sendCmd') {
	$cmd = urldecode(strip_tags($_GET['cmd']));
	$value = urldecode(strip_tags($_GET['value']));
	$terminal = urldecode(strip_tags($_GET['terminal']));
	
	$sqlQuery = "SELECT * FROM `terminals` WHERE `NAME` = '" . DBSafe($terminal) . "' OR `TITLE` = '" . DBSafe($terminal) . "' ORDER BY `ID` ASC";
	$terminals = SQLSelect($sqlQuery);
	$type = $terminals[0]['PLAYER_TYPE'];

	include_once (DIR_MODULES . 'app_player/app_player.class.php');
	$player = new app_player();
	$player->play_terminal = $terminal;
	// Имя терминала
	$player->command = $cmd;
	// Команда
	$player->param = $value;
	// Параметр
	$player->ajax = TRUE;
	$player->intCall = TRUE;
	$player->usual($out);
	$status = $player->json['message'];
}

if($_GET['action'] == 'genPLS') {
	$playlist = urldecode(strip_tags($_GET['playlist']));
	$owner = urldecode(strip_tags($_GET['owner']));
	
	$class = new yamusic();
	$class->generatePlaylistM3U($playlist, $owner);
}

echo json_encode(array('status' => 'OK', 'responce' => $status));
?>