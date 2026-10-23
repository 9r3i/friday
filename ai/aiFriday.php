<?php
/* aiFriday [response]
 * ~ ai friday server response
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 24th 2018
 */
/* start server aiFriday */
(new aiFriday)->serverStart();
/* class aiFriday */
class aiFriday{
  const version='1.0.0';
  protected $server=false;
  protected $dir=null;
  protected $statements=null;
  public $error=false;
  public function __construct(){
    @set_time_limit(false);
    @date_default_timezone_set('Asia/Jakarta');
    $this->statements=[
      'meanSet','meanGet','meanGetAll','logSave','tempDir'
    ];
    $this->dir=str_replace('\\','/',__DIR__).'/';
    if(!is_dir($this->dir)){@mkdir($this->dir,0755,true);}
    return $this;
  }
  /* ----- public of request methods ----- */
  public function tempDir(){
    return str_replace('\\','/',dirname(__DIR__)).'/temp/';
  }
  public function logSave($data=null){
    if(!is_string($data)){return $this->error('Invalid log input');}
    $logFile=$this->dir.'friday.log';
    $o=fopen($logFile,'ab');
    $w=fwrite($o,$data."\r\n");
    fclose($o);
    return $w?'OK':$this->error('Failed to write');
  }
  public function meanGetAll($word=null){
    $meanFile=$this->dir.'mean.json';
    if(!is_file($meanFile)){
      return $this->error('File doesn\'t exist');
    }$get=@file_get_contents($meanFile);
    if(!$get){
      return $this->error('Failed to get content file');
    }$data=@json_decode($get,true);
    if(!$data||!is_array($data)){
      return $this->error('Failed to decode json');
    }
    if(!$this->server){return $data;}
    $json=@json_encode($data,JSON_PRETTY_PRINT);
    if(!$json){
      return $this->error('Failed to encode to json');
    }return $json;
  }
  public function meanGet($word=null){
    $meanFile=$this->dir.'mean.json';
    if(!is_file($meanFile)){
      return $this->error('File doesn\'t exist');
    }$get=@file_get_contents($meanFile);
    if(!$get){
      return $this->error('Failed to get content file');
    }$data=@json_decode($get,true);
    if(!$data||!is_array($data)){
      return $this->error('Failed to decode json');
    }
    if(!isset($data[$word])){
      return $this->error('Word does not exist');
    }return $data[$word];
  }
  public function meanSet($word=null,$mean=null){
    $meanFile=$this->dir.'mean.json';
    if(!is_file($meanFile)){
      return $this->error('File doesn\'t exist');
    }$get=@file_get_contents($meanFile);
    if(!$get){
      return $this->error('Failed to get content file');
    }$data=@json_decode($get,true);
    if(!$data||!is_array($data)){
      return $this->error('Failed to decode json');
    }
    if(isset($data[$word])){
      return $this->error('Word has been added');
    }
    $data[$word]=$mean;
    $json=@json_encode($data,JSON_PRETTY_PRINT);
    if(!$json){
      return $this->error('Failed to encode to json');
    }
    if(!@file_put_contents($meanFile,$json)){
      return $this->error('Failed to put json to file');
    }return 'OK';
  }
  /* public method of server request */
  public function serverStart(){
    $this->server=true;
    $this->head();
    if(isset($_POST['aiFriday'])){
      return $this->start($_POST['aiFriday']);
    }return $this->error('Unauthorized',401);
  }
  /* ----- private out of request methods ----- */
  private function start($s=null){
    $d=@json_decode(@base64_decode($s));
    if(!is_object($s)&&!isset($d->method,$d->token,$d->args)){
      return $this->error('Invalid request',400);
    }
    if(!$this->valid($d->token)){
      return $this->error('Invalid access token',400);
    }
    if(!method_exists($this,$d->method)||!in_array($d->method,$this->statements)){
      return $this->error('Invalid method',400);
    }
    $args=is_array($d->args)?$d->args:(!is_object($d->args)?[$d->args]:[]);
    $out=@\call_user_func_array([$this,$d->method],$args);
    return $this->output($out);
  }
  private function valid($p=null){
    if(!is_string($p)||!preg_match('/^ai/',$p)){
      return false;
    }$d=preg_replace('/^ai/','',$p);
    $t=base_convert($d,36,10);
    return $t>time()?true:false;
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
    $s=is_string($s)?$s:' ';
    if($this->server){
      header('HTTP/1.1 200 OK');
      header('Content-Length: '.strlen($s));
      exit($s);
    }return $s;
  }
  private function error($s=null,$c=200){
    $s=is_string($s)?$s:'Unknown error';
    $t='Error: '.$s.'.';
    if($this->server){
      header('HTTP/1.1 '.$c.' '.$s);
      header('Content-Length: '.strlen($t));
      exit($t);
    }$this->error=$t;
    return false;
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
