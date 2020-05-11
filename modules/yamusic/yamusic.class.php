<?php
class yamusic extends module {
	function __construct() {
		$this->name="yamusic";
		$this->title="Яндекс.Музыка";
		$this->module_category="<#LANG_SECTION_APPLICATIONS#>";
		$this->version = '3.2';
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
	
	function loadUserInfo($id) {
		//Выгрузим из БД нужного юзера
		$selectUser = SQLSelectOne("SELECT * FROM `yamusic_users` WHERE `SELECTED` = 1 AND `ID` = '".$id."' ORDER BY `ID` DESC LIMIT 1");
		
		return $selectUser;
	}
	
	function loadAllUser() {
		//Выгрузим из БД нужного юзера
		$selectUser = SQLSelect("SELECT * FROM `yamusic_users` ORDER BY `ID` DESC LIMIT 5");
		
		return $selectUser;
	}
	
	function generateTrack($playlist, $owner, $songID = '', $count = 1, $shaffle = 0, $next = '', $prev = '', $sizeCover = '200x200') {
		if(empty($playlist) || empty($owner)) die(); 
		
		if($shaffle >= 2) $shaffle = 0; 
		if($count >= 11) $count = 10;
		
		if($shaffle == 1) {
			//Знаю что медленно, но так лень нормальный запрос писать...
			$shaffleTrack = 'ORDER BY rand()';
		}
		
		if($next != '' && $prev == '' && $shaffle == 0) {
			$nextTrack = "AND `ID` > '".$next."'";
		}
		
		if($prev != '' && $next == '' && $shaffle == 0) {
			$prevTrack = "AND `ID` < '".$prev."' ORDER BY `ID` DESC";
		}
		
		if($songID == '') {
			$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlist."' AND `OWNER` = '".$owner."' ".$nextTrack." ".$prevTrack." ".$shaffleTrack." LIMIT ".$count);
		} else {
			$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlist."' AND `OWNER` = '".$owner."' AND `SONGID` = '".$songID."' ".$nextTrack." ".$prevTrack);
		}
		
		//Выгрузим музыку пользователя
		//Заготовка для массива
		foreach($selectMusic as $key => $value) {
			$selectMusic[$key]['LINK'] = $this->getDirectLink($value['SONGID'], $loadUserInfo['TOKEN']);
			$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'H:i:s');
			$selectMusic[$key]['COVER_SIZED'] = str_ireplace("200x200", $sizeCover, $value['COVER']);;
		}
		
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

		//Создадим плейлист МНЕ НРАВИТСЯ
		$selectIfDouble = SQLSelectOne("SELECT * FROM `yamusic_playlist` WHERE `PLAYLISTID` = '-1".$userUID."' ORDER BY `ID` DESC LIMIT 1");
		if($selectIfDouble['PLAYLISTID'] != '-1'.$userUID) {
			SQLExec("INSERT INTO `yamusic_playlist` (`OWNER`,`PLAYLISTID`,`TITLE`,`VISIBILITY`,`CREATED`,`DURATION`,`COVER`) VALUES ('".dbSafe($userUID)."','-1".$userUID."','Мне нравится','private','".dbSafe(date('d.m.Y H:i:s', time()))."','','https://music.yandex.ru/blocks/playlist-cover/playlist-cover_like.png');");
		}
		
		$this->redirect("?");
	}
	
	function loadUserMusic($userToken, $userUID, $playlistID) {
		//Загрузка МУЗЫКИ юзера из плейлиста
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		//Загрузка треков для плейлиста МНЕ НРАВИТСЯ
		if($playlistID == '-1'.$userUID) {
			$likesTrack = $newDOM->getLikesTracks();
			
			foreach($likesTrack->tracks as $key => $value) {
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
				
				$selectIfDouble = SQLSelectOne("SELECT * FROM `yamusic_music` WHERE `SONGID` = '".dbSafe($idTrack)."' AND `OWNER` = '".dbSafe($userUID)."' ORDER BY `ID` DESC LIMIT 1");
				
				if($selectIfDouble['SONGID'] != $idTrack) {
					SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
				}
			}
		} else {
			$loadUserMusic = $newDOM->usersPlaylists($playlistID, $userUID);
			$loadUserMusic = $loadUserMusic->result[0]->tracks;
			
			foreach($loadUserMusic as $key => $value) {
				//Получим ID треков
				$idTrack = $value->id;
				//Получим обложку и название
				$getCover = $newDOM->tracks($idTrack);
				
				//Если нет инфы о треке - пропускае, он удален
				if(!$getCover[0]->durationMs) continue;
				
				//Генерируем массив песен
				$cover = mb_strlen($getCover[0]->coverUri)-2;
				$cover = substr($getCover[0]->coverUri, 0, $cover);
				
				//$selectIfDouble = SQLSelectOne("SELECT * FROM `yamusic_music` WHERE `SONGID` = '".dbSafe($idTrack)."' AND `OWNER` = '".dbSafe($userUID)."' ORDER BY `ID` DESC LIMIT 1");
				
				//if($selectIfDouble['SONGID'] != $idTrack) {
					SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
				//}
			}
		}
		
		$this->redirect("?mode=loadPlayList&playlistID=".$playlistID);
	}
	
	function getDirectLink($songID, $userToken) {
		//Функция выдача ссылок для json.php
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		
		$link = $newDOM->tracksDownloadInfo($songID, true);
		$link = $link[0]->directLink;
		
		return $link;
	}
	
	function microTimeConvert($ms, $format) {
		$ms = date($format, mktime(0, 0, $ms/1000));
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
	
	function loadUserMusicPlaylistOnDay($userToken, $userUID, $reload = 0) {
		//Запрашиваем плейлист дня
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
		$playlistOnDayArray = $newDOM->landing('personalplaylists');
		$playlistID = $playlistOnDayArray->blocks[0]->entities[0]->data->data->kind;
		$playlistModify = $playlistOnDayArray->blocks[0]->entities[0]->data->data->modified;
		$playlistOwner = $playlistOnDayArray->blocks[0]->entities[0]->data->data->owner->uid;
		
		//Удалим старые треки из БД
		SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
		//Разные даты, а значит надо заного обновлять
		$loadUserMusic = $newDOM->usersPlaylists($playlistID, $playlistOwner);
		$loadUserMusic = $loadUserMusic->result[0]->tracks;
		
		foreach($loadUserMusic as $key => $value) {
			//Получим ID треков
			$idTrack = $value->id;
			//Получим обложку и название
			$getCover = $newDOM->tracks($idTrack);
			
			//Если нет инфы о треке - пропускае, он удален
			if(!$getCover[0]->durationMs) continue;
			
			//Генерируем массив песен
			$cover = mb_strlen($getCover[0]->coverUri)-2;
			$cover = substr($getCover[0]->coverUri, 0, $cover);
			
			//Записываем НО меняем владельца плейлиста
			SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
		}
		
		return;
	}
	
	function loadUserMusicDejavuPlaylist($userToken, $userUID, $reload = 0) {
		//Запрашиваем плейлист дня
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
		$playlistOnDayArray = $newDOM->landing('personalplaylists');
		$playlistID = $playlistOnDayArray->blocks[0]->entities[2]->data->data->kind;
		$playlistModify = $playlistOnDayArray->blocks[0]->entities[2]->data->data->modified;
		$playlistOwner = $playlistOnDayArray->blocks[0]->entities[2]->data->data->owner->uid;
		
		//Удалим старые треки из БД
		SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
		//Разные даты, а значит надо заного обновлять
		$loadUserMusic = $newDOM->usersPlaylists($playlistID, $playlistOwner);
		$loadUserMusic = $loadUserMusic->result[0]->tracks;
		
		foreach($loadUserMusic as $key => $value) {
			//Получим ID треков
			$idTrack = $value->id;
			//Получим обложку и название
			$getCover = $newDOM->tracks($idTrack);
			
			//Если нет инфы о треке - пропускае, он удален
			if(!$getCover[0]->durationMs) continue;
			
			//Генерируем массив песен
			$cover = mb_strlen($getCover[0]->coverUri)-2;
			$cover = substr($getCover[0]->coverUri, 0, $cover);
			
			//Записываем НО меняем владельца плейлиста
			SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
		}
		
		return;
	}
	
	function loadUserMusicNewTracksPlaylist($userToken, $userUID, $reload = 0) {
		//Запрашиваем плейлист дня
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
		$playlistOnDayArray = $newDOM->landing('personalplaylists');
		$playlistID = $playlistOnDayArray->blocks[0]->entities[1]->data->data->kind;
		$playlistModify = $playlistOnDayArray->blocks[0]->entities[1]->data->data->modified;
		$playlistOwner = $playlistOnDayArray->blocks[0]->entities[1]->data->data->owner->uid;
		
		//Удалим старые треки из БД
		SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
		//Разные даты, а значит надо заного обновлять
		$loadUserMusic = $newDOM->usersPlaylists($playlistID, $playlistOwner);
		$loadUserMusic = $loadUserMusic->result[0]->tracks;
		
		foreach($loadUserMusic as $key => $value) {
			//Получим ID треков
			$idTrack = $value->id;
			//Получим обложку и название
			$getCover = $newDOM->tracks($idTrack);
			
			//Если нет инфы о треке - пропускае, он удален
			if(!$getCover[0]->durationMs) continue;
			
			//Генерируем массив песен
			$cover = mb_strlen($getCover[0]->coverUri)-2;
			$cover = substr($getCover[0]->coverUri, 0, $cover);
			
			//Записываем НО меняем владельца плейлиста
			SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
		}
		
		return;
	}
	
	function loadUserMusicTainikPlaylist($userToken, $userUID, $reload = 0) {
		//Запрашиваем плейлист дня
		require_once(DIR_MODULES.$this->name.'/client.php');
		$newDOM = new Client($userToken);
		//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
		$playlistOnDayArray = $newDOM->landing('personalplaylists');
		$playlistID = $playlistOnDayArray->blocks[0]->entities[4]->data->data->kind;
		$playlistModify = $playlistOnDayArray->blocks[0]->entities[4]->data->data->modified;
		$playlistOwner = $playlistOnDayArray->blocks[0]->entities[4]->data->data->owner->uid;
		
		//Удалим старые треки из БД
		SQLExec("DELETE FROM `yamusic_music` WHERE `OWNER` = '".$userUID."' AND `PLAYLISTID` = '".$playlistID."'");
		//Разные даты, а значит надо заного обновлять
		$loadUserMusic = $newDOM->usersPlaylists($playlistID, $playlistOwner);
		$loadUserMusic = $loadUserMusic->result[0]->tracks;
		
		foreach($loadUserMusic as $key => $value) {
			//Получим ID треков
			$idTrack = $value->id;
			//Получим обложку и название
			$getCover = $newDOM->tracks($idTrack);
			
			//Если нет инфы о треке - пропускае, он удален
			if(!$getCover[0]->durationMs) continue;
			
			//Генерируем массив песен
			$cover = mb_strlen($getCover[0]->coverUri)-2;
			$cover = substr($getCover[0]->coverUri, 0, $cover);
			
			//Записываем НО меняем владельца плейлиста
			SQLExec("INSERT INTO `yamusic_music` (`SONGID`,`PLAYLISTID`,`OWNER`,`NAMESONG`,`ARTISTS`,`COVER`,`DURATION`,`ADDTIME`) VALUES ('".dbSafe($idTrack)."','".dbSafe($playlistID)."','".dbSafe($userUID)."','".dbSafe($getCover[0]->title)."','".dbSafe($getCover[0]->artists[0]->name)."','https://".dbSafe($cover)."200x200','".dbSafe($getCover[0]->durationMs)."','".time()."');");
		}
		
		return;
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
					
					if($accountInfo->serviceAvailable == true) {
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
			//Отдаем в выдачу
			$out['ACCOUNT_UID'] = $loadUserInfo['UID'];
			$out['ACCOUNT_ID'] = $loadUserInfo['ID'];
			$out['ACCOUNT_LOGIN'] = $loadUserInfo['USERNAME'];
			$out['ACCOUNT_NAME'] = $loadUserInfo['FULLNAME'];
			$out['ACCOUNT_REGDATE'] = $loadUserInfo['REGDATE'];
			($loadUserInfo['STATUS'] == 1) ? $out['ACCOUNT_AVAIL'] = '<span style="color: green">Подписка активна</span>' : $out['ACCOUNT_AVAIL'] = '<span style="color: red">Подписка НЕ активна</span>';
			
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
			
			if($this->mode == 'test') {
				require_once(DIR_MODULES.$this->name.'/client.php');
				$accountInfo = new Client($loadUserInfo['TOKEN']);
				
				$test = $accountInfo->search('знаешь ли ты', false,'track',0,false);
				echo '<pre>';
				var_dump($test);
				die();
			}
			
			if(empty($this->mode)) $this->playlistID = '-1'.$loadUserInfo['UID'];
				
			if($this->mode == 'playlistOnDay') {
				//Запрашиваем плейлист дня
				require_once(DIR_MODULES.$this->name.'/client.php');
				$newDOM = new Client($loadUserInfo['TOKEN']);
				//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
				$playlistOnDay = $newDOM->landing('personalplaylists');
				$playlistOnDay_ID = $playlistOnDay->blocks[0]->entities[0]->data->data->kind;
				$playlistOnDay_Modify = $playlistOnDay->blocks[0]->entities[0]->data->data->modified;
				$playlistOnDay_Owner = $playlistOnDay->blocks[0]->entities[0]->data->data->owner->uid;
				
				//Сравним тот же плейлист пытаются выкачать или нет
				$this->getConfig();
				$oldPlaylist_Modify = $this->config['PLAYLIST_ON_DAY_MODIFY_'.$loadUserInfo['UID']];
				
				if($oldPlaylist_Modify != $playlistOnDay_Modify || $this->view_mode == 'reload') {
					//Разные даты, а значит надо заного обновлять
					$this->loadUserMusicPlaylistOnDay($loadUserInfo['TOKEN'], $loadUserInfo['UID']);
					
					$this->config['PLAYLIST_ON_DAY_MODIFY_'.$loadUserInfo['UID']] = $playlistOnDay_Modify;
					$this->saveConfig();
					
					$this->redirect("?&md=yamusic&inst=adm&mode=playlistOnDay");
				} else {
					$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					//Выгрузим музыку пользователя
					$countMusicList = 0;	
					$countShowMusicList = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					foreach($selectMusic as $key => $value) {
						$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'i:s');
						$countMusicList++;
					}
					
					//В выдачу
					$out['PLAYLIST_MUSICLIST'] = $selectMusic;
					$out['PLAYLIST_CURRENT'] = $playlistOnDay_ID;
					$out['PLAYLIST_CURRENT_NAME'] = 'Плейлист дня';
					$out['TOTAL_PLAYLIST_TRACKS'] = $countShowMusicList['COUNT(`ID`)'];
					$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $countMusicList;
				}
			} else if($this->mode == 'playlistDejavu') {
				//Запрашиваем плейлист дежавю
				require_once(DIR_MODULES.$this->name.'/client.php');
				$newDOM = new Client($loadUserInfo['TOKEN']);
				//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
				$playlistOnDay = $newDOM->landing('personalplaylists');
				$playlistOnDay_ID = $playlistOnDay->blocks[0]->entities[2]->data->data->kind;
				$playlistOnDay_Modify = $playlistOnDay->blocks[0]->entities[2]->data->data->modified;
				$playlistOnDay_Owner = $playlistOnDay->blocks[0]->entities[2]->data->data->owner->uid;
				
				//Сравним тот же плейлист пытаются выкачать или нет
				$this->getConfig();
				$oldPlaylist_Modify = $this->config['PLAYLIST_DEJAVU_MODIFY_'.$loadUserInfo['UID']];
			
				if($oldPlaylist_Modify != $playlistOnDay_Modify || $this->view_mode == 'reload') {
					//Разные даты, а значит надо заного обновлять
					$this->loadUserMusicDejavuPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID']);
					
					$this->config['PLAYLIST_DEJAVU_MODIFY_'.$loadUserInfo['UID']] = $playlistOnDay_Modify;
					$this->saveConfig();
					
					$this->redirect("?&md=yamusic&inst=adm&mode=playlistDejavu");
				} else {
					$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					//Выгрузим музыку пользователя
					$countMusicList = 0;	
					$countShowMusicList = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					foreach($selectMusic as $key => $value) {
						$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'i:s');
						$countMusicList++;
					}
					
					//В выдачу
					$out['PLAYLIST_MUSICLIST'] = $selectMusic;
					$out['PLAYLIST_CURRENT'] = $playlistOnDay_ID;
					$out['PLAYLIST_CURRENT_NAME'] = 'Дежавю';
					$out['TOTAL_PLAYLIST_TRACKS'] = $countShowMusicList['COUNT(`ID`)'];
					$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $countMusicList;
				}
			} else if($this->mode == 'playlistNewTracks') {
				//Запрашиваем плейлист дежавю
				require_once(DIR_MODULES.$this->name.'/client.php');
				$newDOM = new Client($loadUserInfo['TOKEN']);
				//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
				$playlistOnDay = $newDOM->landing('personalplaylists');
				$playlistOnDay_ID = $playlistOnDay->blocks[0]->entities[1]->data->data->kind;
				$playlistOnDay_Modify = $playlistOnDay->blocks[0]->entities[1]->data->data->modified;
				$playlistOnDay_Owner = $playlistOnDay->blocks[0]->entities[1]->data->data->owner->uid;
				
				//Сравним тот же плейлист пытаются выкачать или нет
				$this->getConfig();
				$oldPlaylist_Modify = $this->config['PLAYLIST_NEWTRACKS_MODIFY_'.$loadUserInfo['UID']];
			
				if($oldPlaylist_Modify != $playlistOnDay_Modify || $this->view_mode == 'reload') {
					//Разные даты, а значит надо заного обновлять
					$this->loadUserMusicNewTracksPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID']);
					
					$this->config['PLAYLIST_NEWTRACKS_MODIFY_'.$loadUserInfo['UID']] = $playlistOnDay_Modify;
					$this->saveConfig();
					
					$this->redirect("?&md=yamusic&inst=adm&mode=playlistNewTracks");
				} else {
					$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					//Выгрузим музыку пользователя
					$countMusicList = 0;	
					$countShowMusicList = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					foreach($selectMusic as $key => $value) {
						$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'i:s');
						$countMusicList++;
					}
					
					//В выдачу
					$out['PLAYLIST_MUSICLIST'] = $selectMusic;
					$out['PLAYLIST_CURRENT'] = $playlistOnDay_ID;
					$out['PLAYLIST_CURRENT_NAME'] = 'Дежавю';
					$out['TOTAL_PLAYLIST_TRACKS'] = $countShowMusicList['COUNT(`ID`)'];
					$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $countMusicList;
				}
			} else if($this->mode == 'playlistTainik') {
				//Запрашиваем плейлист дежавю
				require_once(DIR_MODULES.$this->name.'/client.php');
				$newDOM = new Client($loadUserInfo['TOKEN']);
				//ИД плейлиста, сохраним его в конфиг, чтобы потом удалить
				$playlistOnDay = $newDOM->landing('personalplaylists');
				$playlistOnDay_ID = $playlistOnDay->blocks[0]->entities[4]->data->data->kind;
				$playlistOnDay_Modify = $playlistOnDay->blocks[0]->entities[4]->data->data->modified;
				$playlistOnDay_Owner = $playlistOnDay->blocks[0]->entities[4]->data->data->owner->uid;
				
				//Сравним тот же плейлист пытаются выкачать или нет
				$this->getConfig();
				$oldPlaylist_Modify = $this->config['PLAYLIST_TAINIK_MODIFY_'.$loadUserInfo['UID']];
			
				if($oldPlaylist_Modify != $playlistOnDay_Modify || $this->view_mode == 'reload') {
					//Разные даты, а значит надо заного обновлять
					$this->loadUserMusicTainikPlaylist($loadUserInfo['TOKEN'], $loadUserInfo['UID']);
					
					$this->config['PLAYLIST_TAINIK_MODIFY_'.$loadUserInfo['UID']] = $playlistOnDay_Modify;
					$this->saveConfig();
					
					$this->redirect("?&md=yamusic&inst=adm&mode=playlistTainik");
				} else {
					$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					//Выгрузим музыку пользователя
					$countMusicList = 0;	
					$countShowMusicList = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$playlistOnDay_ID."'");
					
					foreach($selectMusic as $key => $value) {
						$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'i:s');
						$countMusicList++;
					}
					
					//В выдачу
					$out['PLAYLIST_MUSICLIST'] = $selectMusic;
					$out['PLAYLIST_CURRENT'] = $playlistOnDay_ID;
					$out['PLAYLIST_CURRENT_NAME'] = 'Дежавю';
					$out['TOTAL_PLAYLIST_TRACKS'] = $countShowMusicList['COUNT(`ID`)'];
					$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $countMusicList;
				}
			} else {
				$selectMusic = SQLSelect("SELECT * FROM `yamusic_music` WHERE `PLAYLISTID` = '".$this->playlistID."' AND `OWNER` = '".$loadUserInfo['UID']."'");
				$selectPlaylist = SQLSelectOne("SELECT * FROM `yamusic_playlist` WHERE `PLAYLISTID` = '".$this->playlistID."' AND `OWNER` = '".$loadUserInfo['UID']."'");
				
				if($selectMusic[0]['SONGID']) {
					//Выгрузим музыку пользователя
					$countMusicList = 0;	
					$countShowMusicList = SQLSelectOne("SELECT COUNT(`ID`) FROM `yamusic_music` WHERE `PLAYLISTID` = '".$this->playlistID."' AND `OWNER` = '".$loadUserInfo['UID']."'");
					
					foreach($selectMusic as $key => $value) {
						$selectMusic[$key]['DURATION'] = $this->microTimeConvert($value['DURATION'], 'i:s');
						$countMusicList++;
					}
					
					//В выдачу
					$out['PLAYLIST_MUSICLIST'] = $selectMusic;
					$out['PLAYLIST_CURRENT'] = $this->playlistID;
					$out['PLAYLIST_CURRENT_NAME'] = $selectPlaylist['TITLE'];
					$out['TOTAL_PLAYLIST_TRACKS'] = $countShowMusicList['COUNT(`ID`)'];
					$out['TOTAL_PLAYLIST_SHOWTRACKS'] = $countMusicList;
					
				} else {
					//Музыки загруженой нет, загружаем
					$this->loadUserMusic($loadUserInfo['TOKEN'], $loadUserInfo['UID'], $this->playlistID);
				}
			}
			
			//Посмотрим в БД есть ли ТВ LG
			$isHaveLGTV_isUse = SQLExec("SHOW TABLES FROM `db_terminal` LIKE 'lgwebostv_commands'");
			
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
