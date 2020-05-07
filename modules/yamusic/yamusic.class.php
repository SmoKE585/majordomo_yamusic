<?php
class yamusic extends module {
	function __construct() {
		$this->name="yamusic";
		$this->title="Яндекс.Музыка";
		$this->module_category="<#LANG_SECTION_APPLICATIONS#>";
		$this->version = '1.0 Beta';
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

	function admin(&$out) {
		$this->getConfig();
		
		if($this->mode == 'auth') {
			global $loginYandex;
			global $passwordYandex;
			
			$this->loginYandex = strip_tags(trim($loginYandex));
			$this->passwordYandex = strip_tags(trim($passwordYandex));
			
			if(!empty($this->loginYandex) && !empty($this->passwordYandex)) {
				require(DIR_MODULES.$this->name.'/client.php');
				
				//Создаем объект
				$yamusicBase = new Client();
				
				//Запрос на получение токена
				$getToken = $yamusicBase->fromCredentials($this->loginYandex, $this->passwordYandex, false);
				
				$rec['ID'] = '';
				$rec['USERNAME'] = $this->loginYandex;
				$rec['TOKEN'] = $getToken;
				$rec['SELECTED'] = '0';
				$rec['ADDTIME'] = time();
				
				SQLInsert('yamusic_users', $rec);
				
				$this->redirect("?");
			}
		}
		
		$selectUser = SQLSelectOne("SELECT * FROM `yamusic_users` ORDER BY `ID` DESC LIMIT 1");
		
		if($selectUser['TOKEN'] != '') {
			$out['ISUSER'] = 1;
			
			//Генерим последние 10 треков из плейлиста Мне нравится
			//Подключим функции
			require(DIR_MODULES.$this->name.'/client.php');
			
			$music = new Client($selectUser['TOKEN']);
			
			//Узнаем сколько треков нужно пропустить
			$skipTrack = 0;
			//Лимит
			$limitLoadTrack = 10;
			//Получим грязный список треков
			$likesTrack = $music->getLikesTracks();

			//Заготовка для массива
			$musicList = [];
			//Циклом переберем первые 5 треков и отдадим браузеру (Ссылка действует 1 минуту)
			$count = 0;
			foreach($likesTrack->tracks as $key => $value) {
				//if($key != $skipTrack) continue;
				//Получим ID треков
				$idTrack = $value->id;
				//Получим обложку и название
				$getCover = $music->tracks($idTrack);
				
				//Получим грязную ссылку
				$link = $music->tracksDownloadInfo($idTrack, true);
				$link = $link[0]->directLink;
				//Генерируем массив песен
				$cover = mb_strlen($getCover[0]->coverUri)-2;
				$cover = substr($getCover[0]->coverUri, 0, $cover);
				
				$rec = [
					'NAMESONG' => $getCover[0]->title,
					'ARTISTS' => $getCover[0]->artists[0]->name,
					'COVER' => 'https://'.$cover.'50x50',
					'DURATION' => round($getCover[0]->durationMs/1000/60, 2).' мин.',
					'LINK' => $link,
				];
				
				array_push($musicList, $rec);

				//Счетчик
				$count++;
				//if($count == $skipTrack) break;
				if($count > $limitLoadTrack) break;
			}
			
			$out['MUSICLIST'] = $musicList;
		}
		
	}
	
	function usual(&$out) {
		$this->admin($out);
	}

	function install($data='') {
		parent::install();
	}
	
	function uninstall() {
		SQLExec('DROP TABLE IF EXISTS yamusic_users');
	}	
	
	function dbInstall($data = '') {
		$data = <<<EOD
yamusic_users: ID int(10) unsigned NOT NULL auto_increment
yamusic_users: USERNAME varchar(100) NOT NULL DEFAULT ''
yamusic_users: TOKEN varchar(255) NOT NULL DEFAULT ''
yamusic_users: SELECTED varchar(2) NOT NULL DEFAULT ''
yamusic_users: ADDTIME varchar(255) NOT NULL DEFAULT ''
	
EOD;
		parent::dbInstall($data);
	}
}
