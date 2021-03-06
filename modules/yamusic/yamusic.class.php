<?php
class yamusic extends module {
	function __construct() {
		$this->name="yamusic";
		$this->title="Яндекс.Музыка";
		$this->module_category="<#LANG_SECTION_APPLICATIONS#>";
		$this->version = '5.5';
		$this->checkInstalled();
	}

	function saveParams($data=1) {
		$p=array();
		if (IsSet($this->id)) {
			$p["id"]=$this->id;
		}
		if (IsSet($this->view_mode)) {
			$p["view_mode"]=$this->view_mode;
		}
		if (IsSet($this->edit_mode)) {
			$p["edit_mode"]=$this->edit_mode;
		}
		if (IsSet($this->tab)) {
			$p["tab"]=$this->tab;
		}
		return parent::saveParams($p);
	}

	function getParams() {
		global $id;
		global $mode;
		global $view_mode;
		global $edit_mode;
		global $page;
		global $playlistID;
		global $md;
		global $tab;
		
		//Для плеера на сценах
		global $blur;
		global $width;
		global $height;
		global $styleplayer;
		global $shaffle;
		global $onlycontrol;
		global $autoplay;
		global $showplaylist;
		global $stylebtn;
		global $playlist;
		global $fixed;
		
		if (isset($blur)) {
			$this->blur=$blur;
		}
		if (isset($width)) {
			$this->width=$width;
		}
		if (isset($height)) {
			$this->height=$height;
		}
		if (isset($styleplayer)) {
			$this->styleplayer=$styleplayer;
		}
		if (isset($shaffle)) {
			$this->shaffle=$shaffle;
		}
		if (isset($onlycontrol)) {
			$this->onlycontrol=$onlycontrol;
		}
		if (isset($autoplay)) {
			$this->autoplay=$autoplay;
		}
		if (isset($showplaylist)) {
			$this->showplaylist=$showplaylist;
		}
		if (isset($stylebtn)) {
			$this->stylebtn=$stylebtn;
		}
		if (isset($playlist)) {
			$this->playlist=$playlist;
		}
		if (isset($fixed)) {
			$this->fixed=$fixed;
		}
		if (isset($onwindow)) {
			$this->onwindow=$onwindow;
		}
		//--------------------------------------
		
		if (isset($id)) {
			$this->id=$id;
		}
		if (isset($mode)) {
			$this->mode=$mode;
		}
		if (isset($view_mode)) {
			$this->view_mode=$view_mode;
		}
		if (isset($edit_mode)) {
			$this->edit_mode=$edit_mode;
		}
		if (isset($page)) {
			$this->page=$page;
		}
		if (isset($md)) {
			$this->md=$md;
		}
		if (isset($playlistID)) {
			$this->playlistID=$playlistID;
		}
		if (isset($tab)) {
			$this->tab=$tab;
		}
	}

	function run() {
		global $session;
		$out=array();
		if ($this->action=='admin') {
			$this->admin($out);
		} else {
			$this->usual($out);
		}
		if (IsSet($this->owner->action)) {
			$out['PARENT_ACTION']=$this->owner->action;
		}
		if (IsSet($this->owner->name)) {
			$out['PARENT_NAME']=$this->owner->name;
		}
		$out['VIEW_MODE']=$this->view_mode;
		$out['EDIT_MODE']=$this->edit_mode;
		$out['MODE']=$this->mode;
		$out['ACTION']=$this->action;
		$this->data=$out;
		$p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
		$this->result=$p->result;
	}
	
	function loadUserInfo($id, $typeSearch = 0) {
		//Выгрузим из БД нужного юзера
		if($typeSearch == 1) {
			$selectUser = SQLSelectOne("SELECT * FROM `yamusic_users` WHERE `UID` = '".$id."' ORDER BY `ID` DESC LIMIT 1");
		} else {
			$selectUser = SQLSelectOne("SELECT * FROM `yamusic_users` WHERE `ID` = '".$id."' ORDER BY `ID` DESC LIMIT 1");
		}
		
		return $selectUser;
	}
	
	function loadPlaylistOnScene() {
		$select = SQLSelect("SELECT * FROM `yamusic_playlist` ORDER BY `ID` DESC");
		
		foreach($select as $key => $value) {
			//Проверим есть ли закаченые треки
			$selectSum = SQLSelectOne("SELECT COUNT(*) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$value['PLAYLISTID']."'");
			$select[$key]['USERNAME'] = $this->loadUserInfo($value['OWNER'], 1)['FULLNAME'];
			($selectSum['COUNT(*)'] != 0) ? $select[$key]['ISAVAIL'] = 1 : $select[$key]['ISAVAIL'] = 0;
		}
		
		return $select;
	}
	
	function playlistOwner($playlistID) {
		$select = SQLSelectOne("SELECT `OWNER` FROM `yamusic_playlist` WHERE `PLAYLISTID` = '".$playlistID."'");
		
		return $select;
	}
	
	function loadAllUser() {
		//Выгрузим из БД нужного юзера
		$selectUser = SQLSelect("SELECT * FROM `yamusic_users` ORDER BY `ID` DESC LIMIT 5");
		
		return $selectUser;
	}
	
	function generateTrack($playlist, $owner, $songID = '', $count = 1, $shaffle = 0, $next = '', $prev = '', $sizeCover = '200x200') {
		if(empty($playlist) || empty($owner)) die('Error!'); 
		
		if($shaffle >= 2) $shaffle = 0; 
		if($count >= 11) $count = 10;
		
		if($shaffle == 1) {
			//Знаю что медленно, но так лень нормальный запрос писать...
			$shaffleTrack = 'ORDER BY rand()';
		}
		
		if($next != '' && $prev == '' && $shaffle == 0) {
			$nextTrack = "AND `ID` < '".$next."' ORDER BY `ID` DESC";
		} else if($prev != '' && $next == '' && $shaffle == 0) {
			$prevTrack = "AND `ID` > '".$prev."' ORDER BY `ID`";
		} else if($shaffle == 0) {
			$nextTrack = "ORDER BY `ID` DESC";
		}
		
		if($songID == '') {
			$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlist."' AND `OWNER` = '".$owner."' ".$nextTrack." ".$prevTrack." ".$shaffleTrack." LIMIT ".$count);
		} else {
			$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlist."' AND `OWNER` = '".$owner."' AND `SONGID` = '".$songID."' ".$nextTrack." ".$prevTrack);
		}
		
		$selectMusic = array_reverse($selectMusic);
		
		//Выгрузим музыку пользователя
		//Заготовка для массива
		foreach($selectMusic as $key => $value) {
			$selectMusic[$key]['LINK'] = $this->getDirectLink($value['SONGID'], $owner);
			$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'H:i:s');
			$selectMusic[$key]['COVER_SIZED'] = str_ireplace("200x200", $sizeCover, $value['COVER']);;
		}
		
		return $selectMusic;
	}
	
	function generateTrackDirect($playlist, $owner, $songID) {
		if(empty($playlist) || empty($owner) || empty($songID)) die('Error!'); 
		
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		$trackInfo = $newDOM->tracks($songID);
		
		$selectMusic[0]['SONGID'] = $songID;
		$selectMusic[0]['PLAYLISTID'] = $playlist;
		$selectMusic[0]['OWNER'] = $owner;
		$selectMusic[0]['NAMESONG'] = $trackInfo[0]->title;
		$selectMusic[0]['ARTISTS'] = $trackInfo[0]->artists[0]->name;
		$selectMusic[0]['LINK'] = $this->getDirectLink($songID, $owner);
		$selectMusic[0]['DURATION'] = $this->microTimeConvert($trackInfo[0]->durationMs, 'H:i:s');
		$selectMusic[0]['COVER'] = 'https://'.str_ireplace("%%", '200x200', $trackInfo[0]->coverUri);
		
		return $selectMusic;
	}
	
	function loadUserPlaylist($userToken, $userUID) {
		//Очистим таблицы
		SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."'");
		SQLExec("DELETE FROM `yamusic_playlist` WHERE `OWNER` = '".$userUID."'");
		
		//Удаляем метки на индивидуальных плейлистах
		$this->getConfig();
		$this->config['PLAYLIST_ON_DAY_MODIFY_'.$userUID] = '';
		$this->config['PLAYLIST_DEJAVU_MODIFY_'.$userUID] = '';
		$this->config['PLAYLIST_NEWTRACKS_MODIFY_'.$userUID] = '';
		$this->config['PLAYLIST_TAINIK_MODIFY_'.$userUID] = '';
		$this->saveConfig();
		
		//Загрузка плейлистов юзера
		require_once(DIR_MODULES.$this->name.'/client.php');
		$loadPlaylist = new Client($userToken);
		$loadPlaylist = $loadPlaylist->usersPlaylistsList();
		
		//Запишем в БД
		foreach($loadPlaylist as $value) {
			//Генерим обложку
			@$coverLoad = file_get_contents('https://avatars.yandex.net'.$value->cover->dir.'200x200');
			if($coverLoad) {
				$coverLoad = 'https://avatars.yandex.net'.$value->cover->dir.'200x200';
			} else {
				$coverLoad = '/img/modules/yamusic.png';
			}
			
			$selectIfDouble = SQLSelectOne("SELECT * FROM `yamusic_playlist` WHERE `PLAYLISTID` = '".dbSafe($value->kind)."' AND `OWNER` = '".dbSafe($userUID)."' ORDER BY `ID` DESC LIMIT 1");
			
			if($selectIfDouble['PLAYLISTID'] != $value->kind) {
				SQLExec("INSERT INTO `yamusic_playlist` (`OWNER`,`PLAYLISTID`,`TITLE`,`VISIBILITY`,`CREATED`,`DURATION`,`COVER`) VALUES ('".dbSafe($userUID)."','".dbSafe($value->kind)."','".dbSafe($value->title)."','".dbSafe($value->visibility)."','".dbSafe(date('d.m.Y H:i:s', strtotime($value->modified)))."','".$value->durationMs."','".dbSafe($coverLoad)."');");
			}

		}

		//Создадим плейлист МНЕ НРАВИТСЯ и специальные плейлисты и закостылим их ибо нельзя уже переделывать БД, забыл 1 колонку добавить =( Плак =(
		$selectIfDouble = SQLSelectOne("SELECT * FROM `yamusic_playlist` WHERE `PLAYLISTID` = '-1".$userUID."' ORDER BY `ID` DESC LIMIT 1");
		if($selectIfDouble['PLAYLISTID'] != '-1'.$userUID) {
			SQLExec("INSERT INTO `yamusic_playlist` (`OWNER`,`PLAYLISTID`,`TITLE`,`VISIBILITY`,`CREATED`,`DURATION`,`COVER`) VALUES ('".dbSafe($userUID)."','-1".$userUID."','Мне нравится','private','".dbSafe(date('d.m.Y H:i:s', time()))."','','https://music.yandex.ru/blocks/playlist-cover/playlist-cover_like.png');");
		}
		
		//$this->redirect("?");
	}
	
	function subscriptionUserInfo($userToken) {
		//Загрузка МУЗЫКИ юзера из плейлиста
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		return $newDOM->accountStatus();
	}
	
	function likeAction($userToken, $action, $songID, $owner) {
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		//Ставим лайк или дизлайк
		if($action == 'add') {
			$newDOM->usersLikesTracksAdd($songID);
			
			//Копируем в БД трек
			$getCover = $newDOM->tracks($songID);
			$cover = mb_strlen($getCover[0]->coverUri)-2;
			$cover = substr($getCover[0]->coverUri, 0, $cover);
			
			$selectSum = SQLSelectOne("SELECT COUNT(*) FROM `yamusic_music` WHERE `SONGID` = '".$songID."' AND `PLAYLISTID` = '-1".$owner."'");
			
			if($selectSum['COUNT(*)'] == 0) {
				SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($songID)."','-1".dbSafe($owner)."','".dbSafe($owner)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
			}
			
			return 'like';
		} else {
			$newDOM->usersLikesTracksRemove($songID);
			
			SQLExec("DELETE FROM `yamusic_music` WHERE `SONGID` = '".$songID."' AND `PLAYLISTID` = '-1".$owner."' AND `OWNER` = '".$owner."' LIMIT 1");
			
			return 'dislike';
		}
		//Формируем новый плейлист
		$this->generatePlaylistM3U('-1'.$owner, $owner);
	}
	
	function vd($data) {
		echo '<pre>';
		var_dump($data);
		die('--------- КОНЕЦ ВЫВОДА ---------');
	}
	
	function loadUserMusic($userToken, $userUID, $playlistID, $newOwner = false) {
		//Загрузка МУЗЫКИ юзера из плейлиста
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		//Загрузка треков для плейлиста МНЕ НРАВИТСЯ
		if($playlistID == '-1'.$userUID) {
			SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '-1".$userUID."'");
			
			$likesTrack = $newDOM->getLikesTracks();
			
			foreach(array_reverse($likesTrack->tracks) as $key => $value) {
				//Получим ID треков
				$idTrack = $value->id;
				//Получим обложку и название
				$getCover = $newDOM->tracks($idTrack);
				
				//Если нет инфы о треке - пропускае, он удален
				if(!$getCover[0]->durationMs) continue;
				if(!$getCover[0]->coverUri) continue;
				
				//Генерируем массив песен
				$cover = mb_strlen($getCover[0]->coverUri)-2;
				$cover = substr($getCover[0]->coverUri, 0, $cover);
				
				SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
			}
		} else if($playlistID == 'chart'.$newOwner) {
			$chart = json_decode(json_encode($this->chartLoad($userToken)), true);
			
			if($newOwner != false) {
				SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$newOwner."' AND `PLAYLISTID` = '".$playlistID."'");
			} else {
				SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
			}
			
			
			foreach(array_reverse($chart["TRACKS"]) as $key => $value) {
				//Получим ID треков
				$idTrack = $value["id"];
				//Получим обложку и название

				//Если нет инфы о треке - пропускае, он удален
				if(!$value["track"]["durationMs"]) continue;
				
				$cover = 'https://'.str_ireplace("%%", '200x200', $value["track"]["coverUri"]);
				
				if($newOwner != false) $userUID = $newOwner;
				
				SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($value["track"]["title"])."','".dbSafe($value["track"]["artists"][0]["name"])."','".dbSafe($cover)."','".dbSafe($value["track"]["durationMs"])."','".time()."');");
			}
		} else {
			if($newOwner != false) {
				SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$newOwner."' AND `PLAYLISTID` = '".$playlistID."'");
			} else {
				SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
			}
			
			$loadUserMusic = $newDOM->usersPlaylists($playlistID, $userUID);
			$loadUserMusic = $loadUserMusic->result[0]->tracks;
			
			foreach(array_reverse($loadUserMusic) as $key => $value) {
				//Получим ID треков
				$idTrack = $value->id;
				//Получим обложку и название
				$getCover = $newDOM->tracks($idTrack);
				
				//Если нет инфы о треке - пропускае, он удален
				if(!$getCover[0]->durationMs) continue;
				
				//Генерируем массив песен
				$cover = mb_strlen($getCover[0]->coverUri)-2;
				$cover = substr($getCover[0]->coverUri, 0, $cover);
				
				if($newOwner != false) $userUID = $newOwner;
				
				SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
			}
		}
		//Генерим плейлисты
		$this->generatePlaylistM3U($playlistID, $userUID);
		
		return ;
		//$this->redirect("?mode=loadPlayList&playlistID=".$playlistID);
	}
	
	function getDirectLink($songID, $owner) {
		//Получим токен владельца трека
		$userToken = SQLSelectOne("SELECT `TOKEN` FROM `yamusic_users` WHERE `UID` = '".$owner."'");
		
		//Функция выдача ссылок для json.php
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken['TOKEN']);
		
		$link = $newDOM->tracksDownloadInfo($songID, true);
		$link = $link[0]->directLink;
		
		return $link;
	}
	
	//выгружаем чарт
	function chartLoad($userToken, $type = 'russia') {
		//Запрашиваем чарт
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		$chart = $newDOM->chart()->chart;

		$chartTracks['TRACKS'] = $chart->tracks;
		$chartTracks['YAOWNER'] = $chart->uid;
		$chartTracks['PLAYLISTID'] = $chart->kind;
		
		return $chartTracks;
		
	}
	
	function generatePlaylistM3U($playlistID, $owner) {
		if(!$playlistID || !$owner) return;
		
		if(!is_dir(DIR_MODULES.$this->name.'/m3u8/')) {
			mkdir(DIR_MODULES.$this->name.'/m3u8/', 0777, true);
		}
		
		//Для тех, кто ставит пароли
		if(defined('EXT_ACCESS_USERNAME') && defined('EXT_ACCESS_PASSWORD')) {
			$login = EXT_ACCESS_USERNAME;
			$pass = EXT_ACCESS_PASSWORD;
			$loginString = $login.':'.$pass.'@';
		} else {
			$loginString = '';
		}
		
		$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistID."' AND `OWNER` = '".$owner."' ORDER BY `ID` DESC");
		
$string = '#EXTM3U
';
		
		foreach($selectMusic as $key => $value) {
$string .= '#EXTINF:0, '.$value['ARTISTS'].' - '.$value['NAMESONG'].'
';
$string .= 'http://'.$loginString.$_SERVER["SERVER_ADDR"].'/modules/yamusic/pl.php?playlistID='.$playlistID.'&owner='.$owner.'&songID='.$value['SONGID'].'
';
		}
		
		$openPL = fopen(DIR_MODULES.$this->name."/m3u8/pl_".$playlistID."_".$owner.".m3u", 'w') or $error = 'Нет прав на запись файла!';
		fwrite($openPL, $string);
		fclose($openPL);
		
		return $error;
	}
	
	function microTimeConvert($ms, $format) {
		((int) $ms != '') ? $ms = date($format, mktime(0, 0, $ms/1000)) : $ms = 0;
		return $ms;
	}
	
	function changeActiveUser($setUserID) {
		SQLExec("UPDATE `yamusic_users` SET `SELECTED` = '0'");
		SQLExec("UPDATE `yamusic_users` SET `SELECTED` = '1' WHERE `ID` = '".dbSafe($setUserID)."'");
		
		return;
	}
	
	function setAudioVolume($chanel, $value) {
		$this->getConfig();
		
		$this->config['VOLUME_'.$chanel] = $value;
		$this->saveConfig();
		
		return;
	}
		
	function loadUserSpecialPlaylist($userToken, $userUID, $playListName, $reload = false) {
		//Запрашиваем плейлист дня
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
		$playlistOnDayArray = $newDOM->landing('personalplaylists');
		
		//Яндекс меняет массив сместами, поэтому придется циклом найти нужное
		foreach($playlistOnDayArray->blocks[0]->entities as $key => $value) {
			if($value->data->data->title == $playListName) {
				$playlistID = $value->data->data->kind;
				$playlistTitle = $value->data->data->title;
				$playlistOwner = $value->data->data->owner->uid;
				$playlistModify = $value->data->data->modified;
				$keyArray = $key;
				break;
			}
		}
		
		//Формируем массив на выдачу
		$arrayReq = [
			'playlistName' => $playlistTitle,
			'playlistID' => $playlistID,
			'playlistModify' => $playlistModify,
			'playlistOwnerNew' => $userUID,
			'playlistOwnerOld' => $playlistOwner,
			'playlistKeyInArray' => $keyArray,
		];
		
		$this->getConfig();
		$playlistModify_old = $this->config['PLAYLIST_'.$playListName.'_OWNER_'.$userUID];
		//Проверим нужно ли обновлять плейлист
		if($reload == true || $playlistModify_old != $playlistModify) {
			//Удалим старые треки из БД
			SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
			//Запустим функцию выкачивания плейлиста
			$this->loadUserMusic($userToken, $playlistOwner, $playlistID, $userUID);
			//Запишем дату обновления плейлиста
			$this->config['PLAYLIST_'.$playListName.'_OWNER_'.$userUID] = $playlistModify;
			$this->saveConfig();
			$arrayReq['noneNeedReload'] = 0;
		} else {
			$arrayReq['noneNeedReload'] = 1;
		}
		
		//Вернем массив
		return $arrayReq;
	}
	
	function selectMusicInDB ($playlistID, $owner, $playListName) {
		$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistID."' AND `OWNER` = '".$owner."' ORDER BY `ID` DESC");
		
		//Выгрузим музыку пользователя
		$countMusicList = 0;	
		$countShowMusicList = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistID."' AND `OWNER` = '".$owner."'");
		
		foreach($selectMusic as $key => $value) {
			$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'i:s');
			if($playlistID == '-1'.$owner) {
				$selectMusic[$key]['ISLIKE'] = 1;
			} else {
				$isLike = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `SONGID` = '".$value['SONGID']."' AND `PLAYLISTID` = '-1".$owner."' AND `OWNER` = '".$owner."'");
				if($isLike['COUNT(`ID`)'] != 0) $selectMusic[$key]['ISLIKE'] = 1;
			}
			$countMusicList++;
		}
		
		//В выдачу
		$arrayReq['PLAYLIST_MUSICLIST'] = $selectMusic;
		$arrayReq['PLAYLIST_CURRENT'] = $playlistID;
		$arrayReq['PLAYLIST_CURRENT_NAME'] = $playListName;
		$arrayReq['TOTAL_PLAYLIST_TRACKS'] = $countShowMusicList['COUNT(`ID`)'];
		$arrayReq['TOTAL_PLAYLIST_SHOWTRACKS'] = $countMusicList;
		
		return $arrayReq;
	}
	
	function admin(&$out) {
		$this->getConfig();
		
		if($this->mode == 'auth') {
			global $loginYandex;
			global $passwordYandex;
			
			$this->loginYandex = strip_tags(trim($loginYandex));
			$this->passwordYandex = strip_tags(trim($passwordYandex));
			
			if(!empty($this->loginYandex) && !empty($this->passwordYandex)) {
				require_once(DIR_MODULES.$this->name.'/client.php');
				
				//Создаем объект
				$yamusicBase = new Client();
				
				//Запрос на получение токена
				$getToken = $yamusicBase->fromCredentials($this->loginYandex, $this->passwordYandex, false);
				
				if($getToken != '') {
					//Выгрузим инфо о аккаунте
					$accountInfo = new Client($getToken);
					$accountInfo = $accountInfo->getAccount();
					$loadUserInfoSub = $this->subscriptionUserInfo($getToken);
			
					$hasPlus = $loadUserInfoSub->plus->hasPlus;
					
					if($hasPlus == true) {
						SQLExec("INSERT INTO `yamusic_users` (`USERNAME`,`TOKEN`,`SELECTED`,`FULLNAME`,`REGDATE`,`STATUS`,`UID`,`ADDTIME`) VALUES ('".$this->loginYandex."','".$getToken."','1','".$accountInfo->fullName."','".date('d.m.Y H:i:s', strtotime($accountInfo->registeredAt))."','".$accountInfo->serviceAvailable."','".$accountInfo->uid."','".time()."');");
					
						$this->redirect("?");
					} else {
						$out['ERRORSUBSCRIBE'] = 1;
					}
				} else {
					$out['ERRORAUTH'] = 1;
				}
			}
		}
		
		//Выгрузим всех юзеров
		$loadAllUsers = $this->loadAllUser();
		if($loadAllUsers) {
			//Найдем основного юзера
			foreach($loadAllUsers as $value) {
				if($value['SELECTED'] == 1) {
					$mainUser = $value['ID'];
					break;
				}
			}
			$out['ALLUSERLIST'] = $loadAllUsers;
			//Выгрузим инфо о основном юзере
			$loadUserInfo = $this->loadUserInfo($mainUser);
			$loadUserInfoSub = $this->subscriptionUserInfo($loadUserInfo['TOKEN']);
			
			//echo '<pre>';
			//var_dump($this->subscriptionUserInfo($loadUserInfo['TOKEN'])->subscription->autoRenewable[0]->expires);
			//var_dump($this->subscriptionUserInfo($loadUserInfo['TOKEN']));
			//die();
			
			//Отдаем в выдачу
			$out['ACCOUNT_UID'] = $loadUserInfo['UID'];
			$out['ACCOUNT_ID'] = $loadUserInfo['ID'];
			$out['ACCOUNT_TOKEN'] = $loadUserInfo['TOKEN'];
			$out['ACCOUNT_LOGIN'] = $loadUserInfo['USERNAME'];
			$out['ACCOUNT_NAME'] = $loadUserInfo['FULLNAME'];
			
			if($loadUserInfoSub->plus->hasPlus == true) { 
				$out['ACCOUNT_AVAIL'] = '<span style="color: green">Подписка активна</span>';
			} else {
				SQLExec("DELETE FROM `yamusic_music` WHERE `ID` = '".$loadUserInfo['ID']."'");
				SQLExec("UPDATE `yamusic_users` SET `SELECTED` = '1' LIMIT 1");
				$out['ACCOUNT_AVAIL'] = '<span style="color: red">Подписка НЕ активна</span>';
			}
			
			
			$out['ACCOUNT_PLUS_AVAIL'] = $loadUserInfoSub->plus->hasPlus;
			(!empty($loadUserInfo['REGDATE'])) ? $out['ACCOUNT_REGDATE'] = $loadUserInfo['REGDATE'] : $out['ACCOUNT_REGDATE'] = $loadUserInfoSub->permissions->until;
			(!empty($loadUserInfoSub->subscription->autoRenewable[0]->expires)) ? $out['ACCOUNT_SUB_END_DATE'] = date('d.m.Y H:i:s', strtotime($loadUserInfoSub->subscription->autoRenewable[0]->expires)) : $out['ACCOUNT_SUB_END_DATE'] = date('d.m.Y H:i:s', strtotime($loadUserInfoSub->subscription->nonAutoRenewable->end));
			(!empty($loadUserInfoSub->subscription->autoRenewable[0]->product->duration)) ? $out['ACCOUNT_SUB_HOWDATE_PAY'] = $loadUserInfoSub->subscription->autoRenewable[0]->product->duration : $out['ACCOUNT_SUB_HOWDATE_PAY'] = '';
			(!empty($loadUserInfoSub->subscription->autoRenewable[0]->product->price->amount)) ? $out['ACCOUNT_SUB_HOWPAY'] = $loadUserInfoSub->subscription->autoRenewable[0]->product->price->amount : $out['ACCOUNT_SUB_HOWPAY'] = '';
			(!empty($loadUserInfoSub->subscription->autoRenewable[0]->product->price->currency)) ? $out['ACCOUNT_SUB_HOWPAY_CURR'] = $loadUserInfoSub->subscription->autoRenewable[0]->product->price->currency : $out['ACCOUNT_SUB_HOWPAY_CURR'] = '';
			$out['ACCOUNT_SUB_TOEND'] = $loadUserInfoSub->subscription->autoRenewable[0]->finished;

			//Метка, что юзер есть в БД
			$out['ISUSER'] = 1;
			
			//Выгрузим из БД плейлисты
			$selectPlaylist = SQLSelect("SELECT * FROM `yamusic_playlist` WHERE `OWNER` = '".$loadUserInfo['UID']."' ORDER BY `PLAYLISTID` LIMIT 50");
			
			if($selectPlaylist[0]['PLAYLISTID']) {
				//В выдачу
				$out['PLAYLIST'] = $selectPlaylist;
				//Выгрузим плейлисты пользователя
				$countPlaylist = 0;		
				foreach($selectPlaylist as $key => $value) {
					$out['PLAYLIST'][$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'H:i:s');
					//Счетчик
					$countPlaylist++;
				}
				
				$out['TOTAL_PLAYLIST'] = $countPlaylist;				
			} else {
				//Плейлистов в БД нет, загружаем
				$this->loadUserPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID']);
				$out['LOADPLAYLISTDONE'] = 1;
			}
			
			//ЗАпрос на обновление плейлистов
			if($this->mode == 'reloadplaylist') {
				$this->loadUserPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID']);
				$out['LOADPLAYLISTDONE'] = 1;
			}
			
			if(empty($this->mode)) $this->playlistID = '-1'.$loadUserInfo['UID'];
				
			if($this->mode == 'playlistOnDay') {
				($this->view_mode == 'reload') ? $needReload = true : $needReload = false;
				$loadDataInPlaylist = $this->loadUserSpecialPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID'], 'Плейлист дня', $needReload);
				$loadMusic = $this->selectMusicInDB($loadDataInPlaylist['playlistID'], $loadDataInPlaylist['playlistOwnerNew'], $loadDataInPlaylist['playlistName']);
				
				$out['PLAYLIST_MUSICLIST'] = $loadMusic['PLAYLIST_MUSICLIST'];
				$out['PLAYLIST_CURRENT'] = $loadMusic['PLAYLIST_CURRENT'];
				$out['PLAYLIST_CURRENT_NAME'] = $loadMusic['PLAYLIST_CURRENT_NAME'];
				$out['PLAYLIST_CURRENT_SYSTEMNAME'] = $this->mode;
				$out['TOTAL_PLAYLIST_TRACKS'] = $loadMusic['TOTAL_PLAYLIST_TRACKS'];
				$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'];	
				(!$loadMusic['TOTAL_PLAYLIST_TRACKS']) ? $out['TOTAL_PLAYLIST_NEEDLOAD'] = 1 : $out['TOTAL_PLAYLIST_NEEDLOAD'] = 0;
			} else if($this->mode == 'playlistDejavu') {
				($this->view_mode == 'reload') ? $needReload = true : $needReload = false;
				$loadDataInPlaylist = $this->loadUserSpecialPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID'], 'Дежавю', $needReload);
				$loadMusic = $this->selectMusicInDB($loadDataInPlaylist['playlistID'], $loadDataInPlaylist['playlistOwnerNew'], $loadDataInPlaylist['playlistName']);
				
				$out['PLAYLIST_MUSICLIST'] = $loadMusic['PLAYLIST_MUSICLIST'];
				$out['PLAYLIST_CURRENT'] = $loadMusic['PLAYLIST_CURRENT'];
				$out['PLAYLIST_CURRENT_NAME'] = $loadMusic['PLAYLIST_CURRENT_NAME'];
				$out['PLAYLIST_CURRENT_SYSTEMNAME'] = $this->mode;
				$out['TOTAL_PLAYLIST_TRACKS'] = $loadMusic['TOTAL_PLAYLIST_TRACKS'];
				$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'];	
				(!$loadMusic['TOTAL_PLAYLIST_TRACKS']) ? $out['TOTAL_PLAYLIST_NEEDLOAD'] = 1 : $out['TOTAL_PLAYLIST_NEEDLOAD'] = 0;
			} else if($this->mode == 'playlistNewTracks') {
				($this->view_mode == 'reload') ? $needReload = true : $needReload = false;
				$loadDataInPlaylist = $this->loadUserSpecialPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID'], 'Премьера', $needReload);
				$loadMusic = $this->selectMusicInDB($loadDataInPlaylist['playlistID'], $loadDataInPlaylist['playlistOwnerNew'], $loadDataInPlaylist['playlistName']);
				
				$out['PLAYLIST_MUSICLIST'] = $loadMusic['PLAYLIST_MUSICLIST'];
				$out['PLAYLIST_CURRENT'] = $loadMusic['PLAYLIST_CURRENT'];
				$out['PLAYLIST_CURRENT_NAME'] = $loadMusic['PLAYLIST_CURRENT_NAME'];
				$out['PLAYLIST_CURRENT_SYSTEMNAME'] = $this->mode;
				$out['TOTAL_PLAYLIST_TRACKS'] = $loadMusic['TOTAL_PLAYLIST_TRACKS'];
				$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'];	
				(!$loadMusic['TOTAL_PLAYLIST_TRACKS']) ? $out['TOTAL_PLAYLIST_NEEDLOAD'] = 1 : $out['TOTAL_PLAYLIST_NEEDLOAD'] = 0;
			} else if($this->mode == 'playlistTainik') {
				($this->view_mode == 'reload') ? $needReload = true : $needReload = false;
				$loadDataInPlaylist = $this->loadUserSpecialPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID'], 'Тайник', $needReload);
				$loadMusic = $this->selectMusicInDB($loadDataInPlaylist['playlistID'], $loadDataInPlaylist['playlistOwnerNew'], $loadDataInPlaylist['playlistName']);
				
				$out['PLAYLIST_MUSICLIST'] = $loadMusic['PLAYLIST_MUSICLIST'];
				$out['PLAYLIST_CURRENT'] = $loadMusic['PLAYLIST_CURRENT'];
				$out['PLAYLIST_CURRENT_NAME'] = $loadMusic['PLAYLIST_CURRENT_NAME'];
				$out['PLAYLIST_CURRENT_SYSTEMNAME'] = $this->mode;
				$out['TOTAL_PLAYLIST_TRACKS'] = $loadMusic['TOTAL_PLAYLIST_TRACKS'];
				$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'];	
				(!$loadMusic['TOTAL_PLAYLIST_TRACKS']) ? $out['TOTAL_PLAYLIST_NEEDLOAD'] = 1 : $out['TOTAL_PLAYLIST_NEEDLOAD'] = 0;
			} else if($this->mode == 'chart') {
				$loadMusic = $this->selectMusicInDB('chart'.$loadUserInfo['UID'], $loadUserInfo['UID'], 'Чарт Яндекс.Музыка');
				
				//Запросим данные по чарту
				if($this->view_mode == 'reload' || $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'] < 1) {
					$this->loadUserMusic($loadUserInfo['TOKEN'], $loadUserInfo['UID'], 'chart'.$loadUserInfo['UID'], $loadUserInfo['UID']);
					$this->redirect("?mode=chart");
				}
				
				$out['PLAYLIST_MUSICLIST'] = $loadMusic['PLAYLIST_MUSICLIST'];
				$out['PLAYLIST_CURRENT'] = $loadMusic['PLAYLIST_CURRENT'];
				$out['PLAYLIST_CURRENT_NAME'] = $loadMusic['PLAYLIST_CURRENT_NAME'];
				$out['PLAYLIST_CURRENT_SYSTEMNAME'] = $this->mode;
				$out['TOTAL_PLAYLIST_TRACKS'] = $loadMusic['TOTAL_PLAYLIST_TRACKS'];
				$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'];	
				(!$loadMusic['TOTAL_PLAYLIST_TRACKS']) ? $out['TOTAL_PLAYLIST_NEEDLOAD'] = 1 : $out['TOTAL_PLAYLIST_NEEDLOAD'] = 0;
			} else {
				$selectMusic = SQLSelectOne("SELECT `SONGID` FROM `yamusic_music` WHERE `PLAYLISTID` = '".$this->playlistID."' AND `OWNER` = '".$loadUserInfo['UID']."' ORDER BY `ID` DESC");
				$selectPlaylist = SQLSelectOne("SELECT `TITLE` FROM `yamusic_playlist` WHERE `PLAYLISTID` = '".$this->playlistID."' AND `OWNER` = '".$loadUserInfo['UID']."'");
				
				if($selectMusic['SONGID']) {
					$loadMusic = $this->selectMusicInDB($this->playlistID, $loadUserInfo['UID'], $selectPlaylist['TITLE']);
				
					$out['PLAYLIST_MUSICLIST'] = $loadMusic['PLAYLIST_MUSICLIST'];
					$out['PLAYLIST_CURRENT'] = $loadMusic['PLAYLIST_CURRENT'];
					$out['PLAYLIST_CURRENT_NAME'] = $loadMusic['PLAYLIST_CURRENT_NAME'];
					$out['TOTAL_PLAYLIST_TRACKS'] = $loadMusic['TOTAL_PLAYLIST_TRACKS'];
					$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $loadMusic['TOTAL_PLAYLIST_SHOWTRACKS'];
				} else {
					//Музыки загруженой нет, загружаем
					$this->loadUserMusic($loadUserInfo['TOKEN'], $loadUserInfo['UID'], $this->playlistID);
				}
			}
			
			if(!empty($this->config['TERMINAL_POTOK_TYPE'])) {
				$out['TERMINAL_POTOK_TYPE'] = $this->config['TERMINAL_POTOK_TYPE'];
			} else {
				$this->config['TERMINAL_POTOK_TYPE'] = 'playlist';
				$out['TERMINAL_POTOK_TYPE'] = $this->config['TERMINAL_POTOK_TYPE'];
				$this->saveConfig();
			}
			
			if(is_file(DIR_MODULES.$this->name.'/m3u8/pl_'.$out['PLAYLIST_CURRENT'].'_'.$loadUserInfo['UID'].'.m3u')) {
				if($this->config['TERMINAL_POTOK_TYPE'] == 'playlist') {
					$out['FULL_PATH_FOR_PLAYLIST_M3U'] = 'http://'.$_SERVER["SERVER_ADDR"].'/modules/'.$this->name.'/m3u8/pl_'.$out['PLAYLIST_CURRENT'].'_'.$loadUserInfo['UID'].'.m3u';
				} else {
					$class->config['PLAY_PAGE_'.$out['PLAYLIST_CURRENT'].'_CURRENT_PLAY'] = 0;
					$out['FULL_PATH_FOR_PLAYLIST_M3U'] = 'http://'.$_SERVER["SERVER_ADDR"].'/modules/'.$this->name.'/play.php?playlist='.$out['PLAYLIST_CURRENT'].'&owner='.$loadUserInfo['UID'].'';
					$this->saveConfig();
				}
			} else {
				$out['FULL_PATH_FOR_PLAYLIST_M3U'] = '';
			}
			
			
			//Посмотрим в БД есть ли ТВ LG
			$isHaveLGTV_isUse = SQLExec("SHOW TABLES FROM `".DB_NAME."` LIKE 'lgwebostv_commands'");
			
			if($isHaveLGTV_isUse->num_rows == 1) {
				$isHaveLGTV = SQLSelect("SELECT * FROM `lgwebostv_commands` WHERE `LINKED_OBJECT` != '' AND `LINKED_PROPERTY` != '' ORDER BY `ID`");
			
				if($isHaveLGTV[0]['LINKED_OBJECT'] && $isHaveLGTV[0]['LINKED_PROPERTY']) {
					$countArrayTV = 0;
					foreach($isHaveLGTV as $key=>$value) {
						if($value['TITLE'] == 'command') {
							$out['ISHAVELGTV_ARRAY'][$countArrayTV]['DEVICE_ID'] = $value['DEVICE_ID'];
							$out['ISHAVELGTV_ARRAY'][$countArrayTV]['OBJ'] = $value['LINKED_OBJECT'];
							$out['ISHAVELGTV_ARRAY'][$countArrayTV]['PROP'] = $value['LINKED_PROPERTY'];
							$countArrayTV++;
							continue;
						}
					}
					
					$out['ISHAVELGTV_FLAG'] = 1;
					//$out['ISHAVELGTV_ARRAY'] = $isHaveLGTV;
				}
			}
			
			//Лечим косяк MJDM
			//if($this->md != 'yamusic') $this->redirect("?mode=loadPlayList&playlistID=".$this->playlistID);
			
			//Выгружаем громкость
			$this->getConfig();
			$out['VOLUME_PUANDSCENE'] = $this->config['VOLUME_PUANDSCENE'];
			if(empty($out['VOLUME_PUANDSCENE'])) $out['VOLUME_PUANDSCENE'] = 1;
			
			$out['VOLUME_TVLG'] = $this->config['VOLUME_TVLG'];
			if(empty($out['VOLUME_TVLG'])) $out['VOLUME_TVLG'] = 1;
			
			$out['VOLUME_TERMINALVOL'] = $this->config['VOLUME_TERMINALVOL'];
			$out['VOLUME_TERMINALVOL_JS'] = $this->config['VOLUME_TERMINALVOL']*100;
			if(empty($out['VOLUME_TERMINALVOL'])) $out['VOLUME_TERMINALVOL'] = 100;
			
			
			$out['MAIN_TERMINAL'] = $this->config['MAIN_TERMINAL'];
			
		} else {
			//Метка, что юзера НЕТ в БД
			$out['ISUSER'] = 0;
		}
		
		$out['VERSION'] = $this->version;
		$out['SERVER_IP'] = $_SERVER['SERVER_ADDR'];
	}
	
	function usual(&$out) {
		$this->admin($out);
		$this->getConfig();
		
		($this->fixed != '') ? $out['SCENE_PLAYER_FIXED'] = 'background-color: transparent;border: none;position: fixed;z-index: 99;border-radius: 20px 20px 0px 0px;bottom: 0;'.$this->fixed.': 0;margin-'.$this->fixed.': 15px;' : $out['SCENE_PLAYER_FIXED'] = 'background-color: transparent;border: none;';
		($this->onwindow == 1) ? $out['SCENE_PLAYER_ONWINDOW'] = 1 : $out['SCENE_PLAYER_ONWINDOW'] = 0;
		($this->blur == 1) ? $out['SCENE_PLAYER_BLUR'] = 1 : $out['SCENE_PLAYER_BLUR'] = 0;
		($this->width) ? $out['SCENE_PLAYER_WIDTH'] = $this->width : $out['SCENE_PLAYER_WIDTH'] = 300;
		($this->height) ? $out['SCENE_PLAYER_HEIGHT'] = $this->height : $out['SCENE_PLAYER_HEIGHT'] = 0;
		($this->shaffle) ? $out['SCENE_PLAYER_SHAFFLE'] = $this->shaffle : $out['SCENE_PLAYER_SHAFFLE'] = 0;
		($this->autoplay) ? $out['SCENE_PLAYER_AUTOPLAY'] = $this->autoplay : $out['SCENE_PLAYER_AUTOPLAY'] = 0;
		($this->stylebtn) ? $out['SCENE_PLAYER_STYLEBTN'] = str_replace("#", "_COL_", $this->stylebtn) : $out['SCENE_PLAYER_STYLEBTN'] = 0;
		($this->showplaylist) ? $out['SCENE_PLAYER_SHOWPLAYLIST'] = $this->showplaylist : $out['SCENE_PLAYER_SHOWPLAYLIST'] = 0;
		($this->onlycontrol) ? $out['SCENE_PLAYER_ONLYCONTROL'] = $this->onlycontrol : $out['SCENE_PLAYER_ONLYCONTROL'] = 0;
		if($this->styleplayer) $out['SCENE_PLAYER_STYLEPLAYER'] = str_replace("#", "_COL_", $this->styleplayer);
		
		if(empty($this->playlist)) {
			//Получим UID активное пользователя и пока только мне нравится
			$loadAllUsers = $this->loadAllUser();
			//Найдем основного юзера
			foreach($loadAllUsers as $value) {
				if($value['SELECTED'] == 1) {
					$mainUser = $value['UID'];
					break;
				}
			}
			
			$out['SCENE_PLAYER_UID'] = $mainUser;
			$out['SCENE_PLAYER_PLAYLIST'] = '-1'.$mainUser;
		} else {
			$out['SCENE_PLAYER_PLAYLIST'] = $this->playlist;
			$out['SCENE_PLAYER_UID'] = $this->playlistOwner($this->playlist)['OWNER'];
		}
		
		$out['FRAME_USUAL_SETVOLUME'] = $this->config['VOLUME_PUANDSCENE'];
		if(empty($out['FRAME_USUAL_SETVOLUME'])) $out['FRAME_USUAL_SETVOLUME'] = 1;
		
		$out['VERSION'] = $this->version;
	}

	function install($data='') {
		parent::install();
	}
	
	function uninstall() {
		SQLExec('DROP TABLE IF EXISTS yamusic_users');
		SQLExec('DROP TABLE IF EXISTS yamusic_playlist');
		SQLExec('DROP TABLE IF EXISTS yamusic_music');
	}	
	
	function dbInstall($data = '') {
		$data = <<<EOD
yamusic_users: ID int(10) unsigned NOT NULL auto_increment
yamusic_users: USERNAME varchar(100) NOT NULL DEFAULT ''
yamusic_users: TOKEN varchar(255) NOT NULL DEFAULT ''
yamusic_users: SELECTED varchar(2) NOT NULL DEFAULT ''
yamusic_users: FULLNAME varchar(255) NOT NULL DEFAULT ''
yamusic_users: REGDATE varchar(255) NOT NULL DEFAULT ''
yamusic_users: STATUS varchar(255) NOT NULL DEFAULT ''
yamusic_users: UID varchar(255) NOT NULL DEFAULT ''
yamusic_users: ADDTIME varchar(255) NOT NULL DEFAULT ''

yamusic_playlist: ID int(10) unsigned NOT NULL auto_increment
yamusic_playlist: OWNER varchar(255) NOT NULL DEFAULT ''
yamusic_playlist: PLAYLISTID varchar(255) NOT NULL DEFAULT ''
yamusic_playlist: TITLE varchar(255) NOT NULL DEFAULT ''
yamusic_playlist: VISIBILITY varchar(255) NOT NULL DEFAULT ''
yamusic_playlist: CREATED varchar(255) NOT NULL DEFAULT ''
yamusic_playlist: DURATION varchar(255) NOT NULL DEFAULT ''
yamusic_playlist: COVER varchar(255) NOT NULL DEFAULT ''

yamusic_music: ID int(10) unsigned NOT NULL auto_increment
yamusic_music: SONGID varchar(255) NOT NULL DEFAULT ''
yamusic_music: PLAYLISTID varchar(255) NOT NULL DEFAULT ''
yamusic_music: OWNER varchar(255) NOT NULL DEFAULT ''
yamusic_music: NAMESONG varchar(255) NOT NULL DEFAULT ''
yamusic_music: ARTISTS varchar(255) NOT NULL DEFAULT ''
yamusic_music: COVER varchar(255) NOT NULL DEFAULT ''
yamusic_music: DURATION varchar(255) NOT NULL DEFAULT ''
yamusic_music: ADDTIME varchar(255) NOT NULL DEFAULT ''
	
EOD;
		parent::dbInstall($data);
	}
}
