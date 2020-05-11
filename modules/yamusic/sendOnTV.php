<?php
$owner = strip_tags($_GET['owner']);
$playlist = strip_tags($_GET['playlist']);
$shaffle = strip_tags($_GET['shaffle']);
$songID = strip_tags($_GET['songID']);

if($shaffle) $shaffle = 0;

if(empty($owner) || empty($playlist)) {
	http_response_code(404);
	die();
}

error_reporting(0); 

chdir (dirname (__FILE__) . '/../../');

include_once ('./config.php');
include_once ('./lib/loader.php');


require('yamusic.class.php');
$class = new yamusic();

$class->getConfig();

?>
<html>
	<head>
		<script type="text/javascript"  src="/3rdparty/jquery/jquery-3.3.1.min.js"></script>
		<script type="text/javascript"  src="/templates/yamusic/js/jquery.fullscreen.js"></script>
		<link rel="stylesheet" href="/3rdparty/bootstrap/css/bootstrap.min.css" type="text/css">
		<script type="text/javascript" src="/3rdparty/bootstrap/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="/templates/yamusic/css/line-awesome.min.css">
		
		<script>
		function startPlayMusic(command) {
			//Покажем лоадинг
			$('#startPlayMusic').hide();
			$('#nextTrack').hide();
			$('#prevTrack').hide();
			$('#shaffleBotton').hide();
			$('#startPlayMusicLoad').show();
			
			//Узнаем, чью музыку запускать
			onwer = $('#ownerMusic').text();
			isShaffle = $('#shaffleMusic').text();
			playlist = $('#playlistMusic').text();
			next = $('#currentMusicTrack').text();
			prev = $('#currentMusicTrack').text();
			
			songID = '&songID='+$('#playSongCommand').text();
			
			if(command == 'prev') {
				urlGenerate = '/modules/yamusic/json.php?mode=track&sizecover=1000x1000&playlist='+playlist+'&count=1&owner='+onwer+'&shaffle='+isShaffle+'&prev='+prev+songID;
			} else if(command == 'next') {
				urlGenerate = '/modules/yamusic/json.php?mode=track&sizecover=1000x1000&playlist='+playlist+'&count=1&owner='+onwer+'&shaffle='+isShaffle+'&next='+next+songID;
			} else {
				urlGenerate = '/modules/yamusic/json.php?mode=track&sizecover=1000x1000&playlist='+playlist+'&count=1&owner='+onwer+'&shaffle='+isShaffle+songID;
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
						$('#playSongCommand').text('');
					})
					
					audio.addEventListener('loadedmetadata', function () {
						//Инфо о треке
						if(responce[0].NAMESONG.length > 16) {
							$('#songName').html('<marquee behavior="alternate" direction="left" style="margin-bottom: -6px;" scrollamount="3">'+responce[0].NAMESONG+'</marquee>');
						} else {
							$('#songName').html(responce[0].NAMESONG);
						}
						
						$('#artistsName').html(responce[0].ARTISTS);
						$('#coverSong').attr('src', responce[0].COVER);
					
						//Меняем кнопки
						$('#startPlayMusicLoad').hide();
						//Воспроизводим после того как метаданные загрузятся
						$('#musicPlayer').get(0).play();
						$('#pausePlayMusic').show();
						$('#nextTrack').show();
						$('#shaffleBotton').show();
						if(isShaffle != 1) $('#prevTrack').show();
						
						$('#backgroundCoverBlur').attr('style', 'position: absolute;background-image: url('+responce[0].COVER_SIZED+');-webkit-filter: blur(15px);-moz-filter: blur(15px);filter: blur(15px);background-size: cover;background-position: center center;background-repeat: no-repeat;height: 100%;width: 100%;bottom: 0;right: 0;');
						$('#backgroundCoverBlur').show();
					})

				},
				error: function(responce) {
					showNoty('error', 'Ошибка получения треков =( Музыки не будет...');
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
			$('#songName').html('');
			$('#artistsName').html('');
			$('#coverSong').attr('src', '/img/modules/yamusic.png');
		}
		
		function shaffleMusicTrack() {
			shaffleStatus = $('#shaffleMusic').text();
			
			if(shaffleStatus == 1) {
				$('#shaffleMusic').text('0');
				$('#shaffleMusicTrackIcon').attr('style', 'font-size: 6rem;margin-top: 20px;');
				$('#prevTrack').show();
			} else {
				$('#shaffleMusic').text('1');
				$('#shaffleMusicTrackIcon').attr('style', 'font-size: 6rem;margin-top: 20px;color: orange;');
				$('#prevTrack').hide();
			}
		}
		
		
		$(function() {
			audio = document.querySelector('audio');
			audio.volume = <?php echo $class->config['VOLUME_TVLG']; ?>;
			
			<?php if($shaffle == 1) echo "$('#shaffleMusicTrackIcon').attr('style', 'font-size: 6rem;margin-top: 20px;color: orange;');"; ?>
			
			startPlayMusic();
		});
		</script>
	</head>
	<body id="fullDisplay">
		<div id="backgroundCoverBlur" style=""></div>
		
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="row" style="margin-top: 340px;background: white;border-radius: 0.25rem !important;border: 20px solid #ffcc00;width: 100%;margin-right: 0px;margin-left: 0px;padding: 20px;">
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12" style="padding: 0px;">
						<div style="float: left;">
							<img id="coverSong" src="/img/modules/yamusic.png" style="width: 100px;">
						</div>
						<div style="margin-left: 110px;padding-top: 4px;font-weight: 100;font-size: 3rem;">
							<span id="songName" style="font-weight: bold;color: #b35b81;"></span><br>
							<span id="artistsName" style="color: #5d5386;"></span>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 text-center" style="padding: 0px;">
						<i class="las la-backward" style="font-size: 10rem;color: #5d5386;margin-right: 15px;display: none;" id="prevTrack" onClick="prevPlayMusic();"></i>
						
						<img src="/templates/yamusic/img/load.gif" id="startPlayMusicLoad" style="width: 100px;vertical-align: top;display: none;">
						<i class="las la-play" style="font-size: 10rem;color: #5cb85c;" id="startPlayMusic" onclick="startPlayMusic();"></i>
						
						<i class="las la-pause-circle" style="font-size: 10rem;display:none;color: #b35b81;" id="pausePlayMusic" onclick="pausePlayMusic();"></i>
						<i class="las la-forward" style="font-size: 10rem;color: #5d5386;margin-left: 15px;display: none;" id="nextTrack" onClick="nextPlayMusic();"></i>
						
						<audio id="musicPlayer" src="" preload="auto"></audio>
						
						<div id="currentMusicTrack" style="display: none;">0</div>
						<div id="playSongCommand" style="display: none;"><?php echo $songID;?></div>
						<div id="shaffleMusic" style="display: none;"><?php echo $shaffle;?></div>
						<div id="ownerMusic" style="display: none;"><?php echo $owner;?></div>
						<div id="playlistMusic" style="display: none;"><?php echo $playlist;?></div>
						
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 text-right" style="padding: 0px;">
						<i class="las la-random" id="shaffleMusicTrackIcon" onclick="shaffleMusicTrack();" style="font-size: 6rem;margin-top: 20px;"></i>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>