<?php
/* aiServer
 * ~ server of AI for explorer
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 9th 2018
 */
/* start aiServer */
new aiServer;
/* aiServer class */
class aiServer{
  const version='1.0.0';
  const boundary='-9r3i -php';
  public function __construct(){
    $this->head();
    if(isset($_POST['aiRequest'])){
      return $this->start($_POST['aiRequest']);
    }return $this->error('401 Unauthorized');
  }
  private function start($s=null){
    $d=@json_decode(@base64_decode($s));
    if(!is_object($s)&&!isset($d->cmd,$d->dir)){
      return $this->output('Error: Invalid request.'."\r\n");
    }
    if(!is_dir($d->dir)){
      return $this->output('Error: Invalid working directory'."\r\n");
    }@chdir($d->dir);
    $args=[];
    $parse=@str_getcsv($d->cmd,' ');
    foreach($parse as $v){
      if(trim($v)!==''){$args[]=trim($v);}
    }
    if(empty($args)){
      return $this->output('OK');
    }
    if(strtolower($args[0])!=='ai'){
      return $this->output('Warning: Must be started with AI.'."\r\n");
    }
    /* setup ai file - get from server.ini */
    if(!$this->os()){
      return $this->output('Error: Failed to get OS name.'."\r\n");
    }$ini=@parse_ini_file(__DIR__.'/server.ini',true);
    if(!is_array($ini)||!isset($ini[$this->os()],$ini[$this->os()]['aiFile'])){
      return $this->output('Error: Invalid server.ini data.'."\r\n");
    }$aiFile=$ini[$this->os()]['aiFile'];
    /* setup global arguments */
    global $argv;
    $argv=[$aiFile,$d->dir,self::boundary];
    $argv=array_merge($argv,array_slice($args,1));
    $exec=@require($aiFile);
    return $this->output(print_r($exec,true));
  }
  private function os(){
    if(strtoupper(substr(PHP_OS,0,3))==="WIN"){
      return 'win';
    }elseif(strtoupper(substr(PHP_OS,0,5))==="LINUX"){
      return 'linux';
    }elseif(strtoupper(substr(PHP_OS,0,6))==="DARWIN"){
      return 'darwin';
    }return false;
  }
  private function output($s=null){
    $s=is_string($s)?$s:'';
    header('HTTP/1.1 200 OK');
    header('Content-Length: '.strlen($s));
    exit($s);
  }
  private function error($s=null){
    $s=is_string($s)?$s:'200 Unknown error';
    header('HTTP/1.1 '.$s);
    $t='Error: '.$s.'.';
    header('Content-Length: '.strlen($t));
    exit($t);
  }
  private function head(){
    /* set time limit */
    @set_time_limit(false);
    /* set default timezone */
    @date_default_timezone_set('Asia/Jakarta');
    /* access control - to allow the access via ajax */
    header('Access-Control-Allow-Origin: *'); /* allow origin */
    header('Access-Control-Request-Method: POST, GET, OPTIONS'); /* request method */
    header('Access-Control-Request-Headers: X-PINGOTHER, Content-Type'); /* request header */
    header('Access-Control-Max-Age: 86400'); /* max age (24 hours) */
    header('Access-Control-Allow-Credentials: true'); /* allow credentials */
    /* set content type of response header */
    header('Content-Type: text/plain;charset=utf-8;');
    /* checking options */
    if(isset($_SERVER['REQUEST_METHOD'])&&strtoupper($_SERVER['REQUEST_METHOD'])=='OPTIONS'){
      header('Content-Language: en-US');
      header('Content-Encoding: gzip');
      header('Content-Length: 0');
      header('Vary: Accept-Encoding, Origin');
      header('HTTP/1.1 200 OK');
      exit;
    }
  }
}
