<?php
error_reporting(0); 

chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');

$volume = $_GET['setVolue'];
$playlist = $_GET['playlist'];
$owner = $_GET['owner'];
$autoplay = $_GET['autoplay'];
$shaffle = $_GET['shaffle'];
$version = $_GET['version'];

?>
<html>
	<head>
		<script type="text/javascript"  src="/3rdparty/jquery/jquery-3.3.1.min.js"></script>
		<link rel="stylesheet" href="/3rdparty/bootstrap/css/bootstrap.min.css" type="text/css">
		<script type="text/javascript" src="/3rdparty/bootstrap/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="/templates/yamusic/css/line-awesome.min.css">
		
		<script>
		function saveVolume(chanel, value) {
			$.getJSON({
				url: '/modules/yamusic/ajax.php?action=saveVolume&chanel='+chanel+'&value='+value,
				success: function(responce) {
					
				},
				error: function(responce) {
					alert('Ошибка AJAX - saveVolume');
				}
			});			
		}
		
		function startPlayMusic(command) {
			//Покажем лоадинг
			$('#startPlayMusic').hide();
			$('#shaffleBotton').hide();
			$('#volumeBotton').hide();
			$('#pausePlayMusic').hide();
			$('#nextTrack').hide();
			$('#prevTrack').hide();
			$('#startPlayMusicLoad').show();
			
			//Узнаем, чью музыку запускать
			onwer = $('#ownerMusic').text();
			isShaffle = $('#shaffleMusic').text();
			playlist = $('#playlistMusic').text();
			next = $('#currentMusicTrack').text();
			prev = $('#currentMusicTrack').text();
			
			songID = '&songID='+$('#playSongCommand').text();
			
			if(command == 'prev') {
				urlGenerate = '/modules/yamusic/json.php?mode=track&playlist='+playlist+'&count=1&owner='+onwer+'&shaffle='+isShaffle+'&prev='+prev+songID;
			} else if(command == 'next') {
				urlGenerate = '/modules/yamusic/json.php?mode=track&playlist='+playlist+'&count=1&owner='+onwer+'&shaffle='+isShaffle+'&next='+next+songID;
			} else {
				urlGenerate = '/modules/yamusic/json.php?mode=track&playlist='+playlist+'&count=1&owner='+onwer+'&shaffle='+isShaffle+songID;
			}
			
			//Запрос нужного трека
			$.getJSON({
				url: urlGenerate,
				success: function(responce) {
					//Вставляем ссылку
					$('#musicPlayer').attr('src', responce[0].LINK);
					
					var audio = document.getElementById("musicPlayer");
					
					audio.addEventListener('ended', nextPlayMusic);
					
					audio.addEventListener('canplay', function () {
						$('#currentMusicTrack').text(responce[0].ID);
					})
					
					audio.addEventListener('loadedmetadata', function () {
						//Меняем кнопки
						$('#startPlayMusicLoad').hide();
						//Инфо о треке
						if(responce[0].NAMESONG.length > 20) {
							$('#songName').html('<marquee behavior="alternate" direction="left" style="margin-bottom: -6px;" scrollamount="3">'+responce[0].NAMESONG+'</marquee>');
						} else {
							$('#songName').html(responce[0].NAMESONG);
						}
						
						$('#artistsName').html(responce[0].ARTISTS);
						$('#coverSong').attr('src', responce[0].COVER);
						
						$('#shaffleBotton').show();
						$('#volumeBotton').show();
						
						//Воспроизводим после того как метаданные загрузятся
						$('#musicPlayer').get(0).play();
						$('#pausePlayMusic').show();
						$('#nextTrack').show();
						if(isShaffle != 1) $('#prevTrack').show();
						$('#playSongCommand').text('');
					})

				},
				error: function(responce) {
					alert('Ошибка AJAX - startPlayMusic');
				}
			});			
		}
		
		function nextPlayMusic() {
			var audio = document.getElementById("musicPlayer");
			audio.removeEventListener('ended', nextPlayMusic);
			
			$('#pausePlayMusic').hide();
			startPlayMusic('next');
		}
		
		function prevPlayMusic() {
			var audio = document.getElementById("musicPlayer");
			audio.removeEventListener('ended', nextPlayMusic);
			
			$('#pausePlayMusic').hide();
			startPlayMusic('prev');
		}
		
		function pausePlayMusic() {	
			var audio = document.getElementById("musicPlayer");
			audio.removeEventListener('ended', nextPlayMusic);
			
			//Меняем кнопки
			$('#startPlayMusic').show();
			$('#pausePlayMusic').hide();
			$('#nextTrack').hide();
			$('#prevTrack').hide();
			//Пауза
			$('#musicPlayer').get(0).pause();
			$('#musicPlayer').removeAttr('src');
						
			//Инфо о треке
			$('#songName').html('Яндекс.Музыка');
			$('#artistsName').html('v.<?php echo $version; ?>');
			$('#coverSong').attr('src', '/img/modules/yamusic.png');
		}
		
		function shaffleMusicTrack() {
			shaffleStatus = $('#shaffleMusic').text();
			
			if(shaffleStatus == 1) {
				$('#shaffleMusic').text('0');
				$('#shaffleBotton').attr('style', 'font-size: 2.3rem;margin-left: 15px;vertical-align: super;');
				$('#prevTrack').show();
			} else {
				$('#shaffleMusic').text('1');
				$('#shaffleBotton').attr('style', 'color: orange;font-size: 2.3rem;margin-left: 15px;vertical-align: super;');
				$('#prevTrack').hide();
			}
		}
		
		function rangeVolumeSet(chanel) {
			let audio = document.querySelector('audio');
			val = $('#volumeSetControl').val();
			audio.volume = val/100;
			
			saveVolume(chanel, val/100);
			
			$('#setShowVolume').text(val);
			$('#volumeSetControl').css({'background':'-webkit-linear-gradient(left ,#8870FF 0%,#8870FF '+val+'%,#fff '+val+'%, #fff 100%)'});
		}
		
		function saveVolume(chanel, value) {
			$.getJSON({
				url: '/modules/yamusic/ajax.php?action=saveVolume&chanel='+chanel+'&value='+value,
				success: function(responce) {
					
				},
				error: function(responce) {
					alert('Ошибка AJAX - saveVolume');
				}
			});			
		}
		
		$(function() {
			audio = document.querySelector('audio');
			audio.volume = <?php echo $volume;?>;
			
			$('#setShowVolume').text('<?php echo $volume;?>'*100);
			$('#volumeSetControl').val('<?php echo $volume;?>'*100);
			$('#volumeSetControl').css({'background':'-webkit-linear-gradient(left ,#8870FF 0%,#8870FF '+'<?php echo $volume;?>'*100+'%,#fff '+'<?php echo $volume;?>'*100+'%, #fff 100%)'});
			
			<?php if($autoplay == 1) echo 'startPlayMusic();'; ?>
		});
		</script>
	</head>
	<body>
		<div style="background: #ffd18e; z-index: 99; height: 150px; width: 300px; padding: 15px; border-radius: 20px 20px 0px 0px; bottom: 0px; right: 0px; margin-right: 10px;">
			<div id="informationCoverSongDIV">
				<div style="float: left;">
					<img id="coverSong" src="/img/modules/yamusic.png" style="width: 50px;border: 1px solid white;">
				</div>
				<div style="margin-left: 60px;padding-top: 4px;font-weight: 100;">
					<span id="songName" style="font-weight: bold;background: white;border-radius: 5px 5px 5px 0px;padding: 2px 5px 2px 5px;">Яндекс.Музыка</span><br>
					<span id="artistsName" style="background: white;padding: 2px 5px 2px 5px;border-radius: 0px 0px 5px 5px;">v.<?php echo $version; ?></span>
				</div>
			</div>
			<div id="volumeControlDIV" style="display:none;">
				<div class="text-center" style="font-size: 1.9rem;margin-bottom: 5px;">
					<i class="las la-volume-up"></i> Громкость <span id="setShowVolume"></span>%
				</div>
				<input type="range" id="volumeSetControl" onChange="rangeVolumeSet('PUANDSCENE')" min="0" max="100" style="width: 100%;-webkit-appearance: none;border-radius: 2px;height: 10px;outline: none;border: 1px solid #D4D4D4;" value="0">
			</div>
			
			<div id="buttonControl" class="text-center" style="background: white;border-radius: 50px;padding: 10px;margin-top: 20px;">
				<i class="las la-volume-up" onclick="$('#informationCoverSongDIV').toggle();$('#volumeControlDIV').toggle();" id="volumeBotton" style="display: none;font-size: 2.3rem;margin-right: 15px;vertical-align: super;"></i>
				
				<i class="las la-backward" style="display:none;font-size: 4rem; color: rgb(93, 83, 134); margin-right: 15px;" id="prevTrack" onclick="prevPlayMusic();"></i>
				
				<img src="/templates/yamusic/img/load.gif" id="startPlayMusicLoad" style="width: 40px; vertical-align: top; display: none;">
				
				<i class="las la-play" style="font-size: 4rem; color: rgb(92, 184, 92);" id="startPlayMusic" onclick="startPlayMusic();"></i>
				<i class="las la-pause-circle" style="display:none;font-size: 4rem; color: rgb(179, 91, 129);" id="pausePlayMusic" onclick="pausePlayMusic();"></i>
				<i class="las la-forward" style="display:none;font-size: 4rem; color: rgb(93, 83, 134); margin-left: 15px;" id="nextTrack" onclick="nextPlayMusic();"></i>
				
				<i class="las la-random" id="shaffleBotton" onclick="shaffleMusicTrack();" style="<?php if($shaffle == 1) echo 'color: orange;'; ?>font-size: 2.3rem; margin-left: 15px; vertical-align: super;"></i>
				
				<audio id="musicPlayer" src="" preload="auto" --autoplay-policy=no-user-gesture-required></audio>

				<div id="currentMusicTrack" style="display: none;"></div>
				<div id="playSongCommand" style="display: none;"></div>
				<div id="shaffleMusic" style="display: none;"><?php echo $shaffle;?></div>
				<div id="ownerMusic" style="display: none;"><?php echo $owner;?></div>
				<div id="playlistMusic" style="display: none;"><?php echo $playlist;?></div>
			</div>
		</div>
	</body>
</html>