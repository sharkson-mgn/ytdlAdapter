<?php

  namespace sharksonmgn\YtdlAdapter;

  use Archive7z\Archive7z;
  use Symfony\Component\Process\Process;

  if (!defined('DS')) {
    define('DS',DIRECTORY_SEPARATOR);
  }

  class Adapter {

    private $os                         = null;

    private $youtubedlDir               = 'bin';
    private $youtubedlFilename          = 'youtube-dl';
    private $youtubedlPath              = null;

    private $ffmpegDir                  = 'bin';
    private $ffmpegFilename             = 'ffmpeg';
    private $ffmpegPath                 = null;
    private $ffmpegPathTmp              = null;
    private $ffmpegArchiveWin           = 'ffmpeg-git-essentials.7z';

    private $execOutput;
    private $execRetval;
    private $execOutputDir              = '/exec_output';

    private $downloadDir                = 'download/';
    private $downloadPath;

    private $url                        = null;

    private $urlHash                    = null;
    private $userHash                   = null;
    private $sessionKeyHash             = 'ytdlAdapter_userHash';
    private $outputPath;
    private $lastAction;
    private $lastParams;

    private $isValid                    = false;
    private $isPlaylist                 = false;

    private $thisDirname;

    private $regexp                     = '//i';

    private $allowed                    = [
                                          'youtubedlDir',
                                          'youtubedlPath',
                                          'ffmpegDir',
                                          'ffmpegPath',
                                          'ffmpegPathTmp',
                                          'thisDirname',
                                          'downloadDir',
                                        ];

    public function __construct($url = null) {

      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }

      if ($this->isWindows()) {
        $this->youtubedlFilename .= '.exe';
        $this->ffmpegFilename .= '.exe';
      }

      $this->createUserHash();

      $this->resetPathes();

      if ($url === null)
        return;

      $this->setUrl($url);

    }

    public function setUrl($url) {
      $this->url = $url;

      $this->validate();

      if ($this->isValid()) {

        $this->urlHash = sha1($this->url);

      }
    }

    private static function joinPaths() {
      $args = func_get_args();
      $paths = array();
      foreach ($args as $arg) {
          $paths = array_merge($paths, (array)$arg);
      }

      // $paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
      $paths = array_map(function($p){ return trim($p, "/"); },$paths);
      $paths = array_filter($paths);
      return self::fixDS(join('/', $paths));
    }

    public function resetPathes() {
      if ($this->thisDirname === null)
        $this->thisDirname = dirname(__FILE__).'/ytdl_storage';

      $this->youtubedlPath = self::joinPaths($this->thisDirname,$this->youtubedlDir,$this->youtubedlFilename);

      $this->ffmpegPath = self::joinPaths($this->thisDirname,$this->ffmpegDir,$this->ffmpegFilename);
      $this->ffmpegPathTmp = $this->thisDirname;

      $this->execOutputPath = self::joinPaths($this->thisDirname,$this->execOutputDir);

      $this->downloadPath = self::joinPaths($this->thisDirname,$this->downloadDir);

      $this->outputPath = self::joinPaths($this->thisDirname,'/output/',$this->userHash);

    }

    public function passConfig($yt) {
      foreach($this->allowed as $a) {
        $yt->setPath($a,$this->$a);
      }
      return $yt;
    }

    public function setPath($path,$val) {
      if (in_array($path,$this->allowed)) {
        $this->$path = $val;
        $this->resetPathes();
      }
    }

    public function createUserHash() {
      if (!isset($_SESSION[$this->sessionKeyHash])) {
        $this->userHash = $this->generateRandomString();
        $_SESSION[$this->sessionKeyHash] = $this->userHash;
      }
      else {
        $this->userHash = $_SESSION[$this->sessionKeyHash];
      }

      return $this->userHash;
    }

    public function detectOs() {
      $this->os = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'windows' : 'linux';
    }

    public function isOs(String $os) {
      if ($this->os === null) {
        $this->detectOs();
      }
      return $this->os === $os;
    }

    public function isLinux() {
      return $this->isOs('linux');
    }

    public function isWindows() {
      return $this->isOs('windows');
    }

    public function validate() {

      $this->isValid = (bool)preg_match('/^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $this->url);

      parse_str( parse_url( $this->url, PHP_URL_QUERY ), $array_of_url_vars );
      if (isset($array_of_url_vars['v'])) {
        $this->url = 'https://youtube.com/watch?v=' . $array_of_url_vars['v'];
        if (isset($array_of_url_vars['list'])) {
          $this->url .= '&list=' . $array_of_url_vars['list'];
        }
      }

      return null;

    }

    public static function getValidUrls($urls, $delimiter = '\r\n', $asObjects = false) {
      if (is_string($urls)) {
        $isMultiline = true;
        if (strpos($urls, $delimiter) == false && strpos($urls, '\n') !== false) {
          $delimiter = '\n';
        }
        else {
          $isMultiline = false;
        }

        if ($isMultiline) {
          $urls = explode($delimiter,$urls);
        }
        else {
          $urls = [$urls];
        }
      }

      if (is_array($urls)) {
        $validUrls = [];
        foreach($urls as $url) {
          $yt = new Adapter($url);
          if ($yt->isValid()) {
            $validUrls[] = $asObjects ? $yt : $url;
          }
        }
        return $validUrls;
      }

      return false;
    }

    public function infoRequest($urls) {

      $urls = self::getValidUrls($urls);

      if (!$this->downloadIfNotExist())
        return false;

      $ret = true;

      foreach ($urls as $url) {

        $yt = new Adapter($url);
        $yt = $this->passConfig($yt);
        $r = $yt->getInfo();
        if (!$r) {
          $ret = false;
        }
      }
      return $ret;
    }

    public function infoRequestStatus($urls) {
      $urls = self::getValidUrls($urls);

      if (!$this->downloadIfNotExist())
        return false;

      $return = [];

      foreach ($urls as $url) {

        $yt = new Adapter($url);
        $yt = $this->passConfig($yt);
        $res = $yt->getInfoResult();
        $md5 = md5($url);
        $return[$md5] = $res === false ? 'inProgress' : $res;

      }

      return $return;
    }

    public function downloadRequest($url) {

      if (!file_exists($this->downloadDir))
        mkdir($this->downloadDir,0766,true);

      $urls = self::getValidUrls($url);

      $return = [];

      foreach ($urls as $url) {

        $yt = new Adapter($url);
        $md5 = md5($url);
        $yt->getInfo();
        $res = $yt->download();
        $return[$md5] = $res === false ? false : $res;
      }

      return $return;

    }

    public function getDownloadPath() {
      $path = $this->thisDirname . '/download/';
      return $this->fixDS($path . implode('',preg_grep('~^sid'.$this->urlHash.'_.*\.mp3$~', scandir($path))));
    }

    public function download() {

      if (!$this->downloadIfNotExist())
        return false;

      if (!$this->downloadFfmpegIfNotExists())
        return false;


      $params = [];
      $params[] = '-x';
      $params[] = '--audio-format mp3';
      $params[] = '--audio-quality 0';
      $params[] = '--embed-thumbnail';
      $params[] = '--metadata-from-title "(?P<artist>.+?) - (?P<title>.+)"';
      $params[] = '--add-metadata';
      $params[] = '--newline';
      $params[] = '--no-playlist';
      $params[] = '--prefer-ffmpeg';
      $params[] = '--no-warnings';
      $params[] = '--restrict-filenames';
      $params[] = '--cache-dir '.$this->fixDS($this->thisDirname . '/cache');
      $params[] = '--ffmpeg-location ' . $this->fixDS($this->ffmpegPath);
      // $params[] = '--postprocessor-args "-id3v2_version 3 -progress \'output/'.$this->userHash.'/dezd\'"';
      $params[] = '--postprocessor-args "-id3v2_version 3 -progress \''.$this->getOutputPath('ffmpeg').'\'"';
      $params[] = "-o \"".$this->downloadPath."/sid".$this->urlHash . "_%(title)s.%(ext)s\"";
      // $params[] = "-o \"src/download/sid".$this->urlHash . "_%(title)s.%(ext)s\"";

      $params[] = '"' . $this->url . '"';

      if (file_exists($this->getOutputPath('download')))
        @unlink($this->getOutputPath('download'));
      if (file_exists($this->getOutputPath('ffmpeg')))
        @unlink($this->getOutputPath('ffmpeg'));

      return $this->isValid() ? $this->exec('download',$params) : false;
    }

    public function downloadProgres() {

      if (!file_exists($this->getOutputPath('download')))
        return false;

      $progress = file_get_contents($this->getOutputPath('download'));

      preg_match_all('/\[download\]\s+([\d\.]+)%/', $progress, $matches, PREG_SET_ORDER);
			$matches = array_reverse($matches);
			if ($matches && isset($matches[0]))
				$percentage = $matches[0][1];

      $step1 = 0.45;
  		$step2 = 0.05;

      $ffmpeg = file_exists($this->getOutputPath('ffmpeg')) ? file_get_contents($this->getOutputPath('ffmpeg')) : null;

			if (!empty($ffmpeg))
			{
				preg_match('/\[ffmpeg\](?:.+)?\s+Destination:\s+(.*)/',$progress,$matches);
				if ($matches)
				{
					$outputfile = $matches[1];

					$filename = pathinfo($outputfile)['basename'];
					//$base = urlencode($this->base64url_encode($filename));
					//$base = $filename;
					$base = md5($filename);
					$md5key = realpath('./download/') . '/' . md5($filename).'.key';
				}

				preg_match_all('/out_time_ms=(\d+)|progress=([a-z]+)/',$ffmpeg, $matches, PREG_SET_ORDER);
				if ($matches && isset($filename))
				{
					$matches = array_reverse($matches);
					if (isset($matches[0][2]) && $matches[0][2] == 'end')
						$status = 'end';
					else
						$status = 'converting';

					$nowdur = $matches[1][1] * 0.000001;
				}

				preg_match('/\[ytdl\] duration: (.+)/',$progress,$matches);

				$info = file_get_contents($this->getOutputPath('info'));
				//$info = preg_replace('/(WARNING.*--restrict-filenames.*\n)/i','',$info);

				if (!$matches /*&& !empty($info)*/ && $status == 'converting')
				{
					$info = json_decode($info,true);
					$duration = $info['duration'];
					//if ($duration !== '')
							//$ytdl->appendProgress($pkey,'[ytdl] duration: '.$duration);
				}



				if (!isset($duration) && $matches)
					$duration = $matches[1] * 1.0;

				if (isset($duration) && $status !== 'downloading')
				{
					$percentage = ($nowdur * 100 / $duration);
				}

				if ($status == 'end' && $percentage > 90)
					$percentage = 100;

			}

      $json = Array(
				'percentage' => isset($percentage) ? $percentage : 0,
				'status'	=>	isset($status) ? $status : 'downloading',
				'duration'	=>	isset($duration) ? $duration : 0,
				'nowdur'	=>	isset($nowdur) ? $nowdur : 0,
				'dcounter'	=>	isset($_download) ? $_download : 0,
				'pkey'		=>	isset($base) ? $base : '',
				'filename'	=>	isset($filename) ? $filename : ''
				//'pkey'		=>	base64_encode($pkey)
			);

      return $json;

    }

    public function getUrl() {
      return $this->url;
    }

    public function isValid() {
      return $this->isValid;
    }

    public function isPlaylist() {
      return $this->isPlaylist;
    }

    public function youtubedlExists() {
      return file_exists($this->youtubedlPath);
    }

    public function downloadIfNotExist() {
      if (!file_exists(dirname($this->youtubedlPath)))
        mkdir(dirname($this->youtubedlPath),0766,true);

      $requestDownload = self::joinPaths(dirname($this->youtubedlPath),'youtubedlRequest');

      if (!$this->youtubedlExists() && !file_exists($requestDownload)) {
        file_put_contents($requestDownload,'');
        file_put_contents($this->youtubedlPath, file_get_contents('https://yt-dl.org/latest/' . $this->youtubedlFilename));
        unlink($requestDownload);
        return true;
      }
      elseif (!$this->youtubedlExists() && file_exists($requestDownload)) {
        return false;
      }
      else {
        return true;
      }

      //return $this->youtubedlExists() ? true : file_put_contents($this->youtubedlPath, file_get_contents('https://yt-dl.org/latest/' . $this->youtubedlFilename));
    }

    public function ffmpegExists() {
      return file_exists($this->ffmpegPath);
    }

    public function downloadFfmpegIfNotExists() {
      if (!file_exists(dirname($this->ffmpegPath)))
        mkdir(dirname($this->ffmpegPath),0766,true);

      $requestDownload = self::joinPaths(dirname($this->youtubedlPath),'ffmpegRequest');

      if (!$this->ffmpegExists() && !file_exists($requestDownload)) {
        file_put_contents($requestDownload,'');
        $this->downloadFfmpeg();
        unlink($requestDownload);
        return true;
      }
      elseif (!$this->ffmpegExists() && file_exists($requestDownload)) {
        return false;
      }
      else {
        return true;
      }

      //return $this->ffmpegExists() ? true : $this->downloadFfmpeg();
    }

    public function downloadFfmpeg() {
      if ($this->isWindows()) {
        if (!file_exists($this->ffmpegPathTmp)) {
          mkdir($this->ffmpegPathTmp,0777,true);
        }
        if (!file_exists($this->ffmpegPathTmp . $this->ffmpegArchiveWin)) {
          file_put_contents($this->ffmpegPathTmp . $this->ffmpegArchiveWin, file_get_contents('https://www.gyan.dev/ffmpeg/builds/'.$this->ffmpegArchiveWin));
        }
        $obj = new Archive7z($this->ffmpegPathTmp . $this->ffmpegArchiveWin);

        if (!$obj->isValid()) {
          throw new \RuntimeException('Incorrect archive');
        }
        //$obj->setOutputDirectory($this->ffmpegPathTmp)->extract();
        foreach($obj->getEntries() as $entry) {
          if ($this->endsWith($entry->getPath(),$this->ffmpegFilename)) {
            $entry->extractTo($this->thisDirname);
            rename(self::joinPaths($this->thisDirname,$entry->getPath()), $this->ffmpegPath);
            $this->rrmdir(self::joinPaths($this->thisDirname,explode(DS,$entry->getPath())[0]));
            break;
          }
        }
        unlink($this->ffmpegPathTmp . $this->ffmpegArchiveWin);
      }
      return $this->ffmpegExists();
    }

    function endsWith( $haystack, $needle ) {
      $length = strlen( $needle );
      if( !$length ) {
        return true;
      }
      return substr( $haystack, -$length ) === $needle;
    }

    public static function rrmdir($dir) {
      if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
          if ($object != "." && $object != "..") {
            if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
              self::rrmdir($dir. DIRECTORY_SEPARATOR .$object);
            else
              unlink($dir. DIRECTORY_SEPARATOR .$object);
          }
        }
        rmdir($dir);
      }
    }

    public function getInfo() {
      return $this->isValid() && !$this->getInfoStatus() ? $this->exec('info',['-J','"' . $this->url . '"']) : false;
    }


    public function getInfoStatus() {

      $output = $this->getOutputPath('info');
      $filemtime = file_exists($output) ? filemtime($output) : 0;

      return file_exists($output) && time()-$filemtime < 2 * 3600 && $this->isJson(file_get_contents($output));

    }

    public function getInfoResult() {
      return $this->getInfoStatus() ? file_get_contents($this->getOutputPath('info')) : false;
    }

    public function exec($action, $params = null) {

      if (is_array($params)) {
        $params = implode(' ',$params);
      }

      $output = $this->getOutputPath($action);

      /*if (file_exists($output)) {
        unlink($output);
      }*/

      $this->lastAction = $action;
      $this->lastParams = $params;

      $command = $this->youtubedlPath . ($params !== null ? ' ' . $params : '') . ' > "' . $output . '" 2>&1';

      if ($this->isWindows()) {
        $command = 'start /i /b ' . $command;
      }

      if (!$this->isWindows()) {
        $command = $command . ' &';
      }

      // if ($this->isWindows()) {
      //   $process = new Process([$command]);
      //   $process->start();
      // }
      // else {
        //$this->execRetval = shell_exec($command);
      // }
      pclose(popen($command,'r'));

      return;
    }

    public function execSync($action, $params = null) {

      if (is_array($params)) {
        $params = implode(' ',$params);
      }

      $this->lastAction = $action;
      $this->lastParams = $params;

      exec($this->youtubedlPath . ($params !== null ? ' ' . $params : '') . ' > "' . $output . '" 2>&1 &');
      // exec($this->youtubedlPath . ($params !== null ? ' ' . $params : ''), $output, $retval);

      $this->execOutput = $output;
      $this->execRetval = $retval;

      return (bool) $retval;
    }

    public function getExecOutput() {
      return $this->execOutput;
    }

    public function getExecRetval() {
      return $this->execRetval;
    }

    public function getLastAction() {
      return $this->lastAction;
    }

    public function getLastParams() {
      return $this->lastParams;
    }

    private static function fixDS($path) {
      return preg_replace('/(\/|\\\)/','/',$path);
    }

    public function getOutputPath($action) {
      if (!file_exists($this->outputPath)) {
        mkdir($this->outputPath, 0766, true);
      }
      return $this->fixDS(self::joinPaths($this->outputPath,$this->urlHash . '_' . $action));
    }

    public function isJson($string) {
      json_decode($string);
      return json_last_error() === JSON_ERROR_NONE;
    }

    public function generateRandomString($length = 35) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}

  }

?>
