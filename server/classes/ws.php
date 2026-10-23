<?php
/* ws - stands for websocket
 * ~ a simple websocket handshake class
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 24th 2016
 * continued at september 12th 2018
 */
class ws{
  const version='1.2.1';
  private $clients;
  private $host;
  private $port;
  private $path;
  private $magickey;
  private $limit;
  private $socket;
  protected $directory=null;
  protected $extDir=null;
  protected $extensions=null;
  protected $token=null;
  protected $connection=null;
  /* construction */
  public function __construct($port=null,$limit=1,$mk=null,$token=null,$extDir=null,$tz=null){
    /* websocket require arguments */
    $this->port=isset($port)&&$port!=$_SERVER['SERVER_PORT']?$port:9333;
    $this->limit=isset($limit)?floor(floatval($limit)*1024):1024;
    $this->magickey=is_string($mk)?$mk:'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    /* prepare host name */
    if(isset($_SERVER['HTTP_HOST'])){
      $host=explode(':',$_SERVER['HTTP_HOST'])[0];
    }/* prepare requst path */
    if(isset($_SERVER['REQUEST_URI'])){
      $path=explode('?',$_SERVER['REQUEST_URI'])[0];
      $path=preg_replace('/[^\/]+$/i','',substr($path,1));
    }
    /* websocket requirements */
    $this->clients=[];
    $this->host=isset($host)?$host:'127.0.0.1';
    $this->path=isset($path)?$path:'';
    /* set time limit */
    @set_time_limit(false);
    /* set default timezone */
    if(!is_string($tz)||!@date_default_timezone_set($tz)){
      @date_default_timezone_set('Asia/Jakarta');
    }
    /* set working api directory */
    $dir=defined('WS_CLI_DIR')?WS_CLI_DIR:__DIR__;
    $dir=str_replace('\\','/',$dir);
    $dir.=substr($dir,-1)!='/'?'/':'';
    $this->directory=$dir;
    if(!is_dir($this->directory)){@mkdir($this->directory,0755,true);}
    /* setup extension directory */
    $extDir=is_string($extDir)?$extDir:'ext';
    $extDir=str_replace('\\','/',$extDir);
    $extDir.=substr($extDir,-1)!='/'?'/':'';
    $this->extDir=$extDir;
    if(!is_dir($this->directory.$this->extDir)){
      @mkdir($this->directory.$this->extDir,0755,true);
    }
    /* get extensions */
    $exts=$this->extensions($this->directory.$this->extDir);
    /* setup extensions */
    $this->extensions=[];
    if(is_array($exts)){foreach($exts as $ext){
      $file=$this->directory.$this->extDir.$ext.'.php';
      if(!is_file($file)){continue;}
      $load=@require_once($file);
      if(!$load||!class_exists($ext,false)){continue;}
      $this->extensions[$ext]=new $ext;
    }}
    /* setup token */
    $this->token=is_string($token)
      &&preg_match('/^[a-z0-9]+$/i',$token)
      ?$token:$this->token();
    /* write user log */
    $this->userlog('initialize',func_get_args());
    /* setup connection file */
    $this->connection=$this->directory.$this->token;
    /* start generate the response */
    return $this->start();
  }
  /* ----- private api methods ----- */
  private function start(){
    /* use api header */
    $this->head();
    /* chek request scheme */
    if(isset($_SERVER['REQUEST_SCHEME'])
      &&preg_match('/^http/',$_SERVER['REQUEST_SCHEME'])){
      if(!isset($_GET['ws'])||$_GET['ws']!==$this->token||$_SERVER['SERVER_PORT']===$this->port){
        return $this->error('Unauthorized.',401,'Unauthorized');
      }elseif(is_file($this->connection)){
        return $this->error('Websocket has been connected.',400,'Bad Request');
      }@file_put_contents($this->connection,$this->token);
    }
    /* remove previous headers */
    $this->removeHeaders();
    /* write user log */
    $this->userlog('websocket',func_get_args());
    /* request for websocket connection */
    return $this->websocket();
  }
  /* userlog */
  private function userlog(){
    $logfile=$this->directory.'/userlog.txt';
    $time=date('ymd-His');
    $json=@json_encode(func_get_args());
    $o=@fopen($logfile,'ab');
    $w=@fwrite($o,$time.'|'.($json?$json:'FAILED')."\r\n");
    @fclose($o);
    return true;
  }
  /* generate token */
  private function token($n=null){
    return preg_replace('/[^a-z0-9]/i','',base64_encode(hash('sha1',$n,true)));
  }
  /* get extensions */
  private function extensions($d=null){
    if(!is_dir($d)){return $d;}
    $d=str_replace('\\','/',$d);
    $d.=substr($d,-1)!='/'?'/':'';
    $s=@scandir($d);$r=[];
    if(!is_array($s)){return false;}
    foreach($s as $f){
      if(!is_file($d.$f)
        ||!preg_match('/^[a-z][a-z0-9]+\.php$/i',$f)){
        continue;
      }$r[]=preg_replace('/\.php$/','',$f);
    }return $r;
  }
  /* execute incoming data for extension */
  private function execute($decoded=null,&$error=false){
    $error=false;
    if(!is_string($decoded)){
      $error='Require a valid request.';
      return false;
    }
    /* decode data json */
    $data=@json_decode($decoded);
    if(!isset($data->ext,$data->method,$data->args,$data->token)){
      $error='Require ext, method, args and token.';
      return false;
    }
    /* check access token validity */
    if($data->token!==$this->token){
      $error='Invalid access token.';
      return false;
    }
    /* checking the method */
    if(!array_key_exists($data->ext,$this->extensions)){
      $error='Extension is not available.';
      return false;
    }
    /* checking the method */
    if(!method_exists($this->extensions[$data->ext],$data->method)){
      $error='Method is not available.';
      return false;
    }
    /* execute request */
    $args=is_array($data->args)?$data->args:[];
    $out=@\call_user_func_array([$this->extensions[$data->ext],$data->method],$args);
    /* return as array - using extension key */
    return [$data->ext=>[$data->method=>$out]];
  }
  /* default response error */
  private function error($s=null,$c=null,$h=null){
    $s=is_string($s)?'Error: '.$s:'Error: Unknown error.';
    $c=is_numeric($c)?intval($c):200;
    $h=is_string($h)?$h:'Unknown error';
    header('HTTP/1.1 '.$c.' '.$h);
    header('Content-Length: '.strlen($s));
    exit($s);
  }
  /* default response output */
  private function output($s=null){
    $s=is_string($s)?$s:'OK';
    header('HTTP/1.1 200 OK');
    header('Content-Length: '.strlen($s));
    exit($s);
  }
  /* default headers for server api access */
  private function removeHeaders(){
    $heads=[
      'Access-Control-Allow-Origin',
      'Access-Control-Request-Method',
      'Access-Control-Request-Headers',
      'Access-Control-Max-Age',
      'Access-Control-Allow-Credentials',
      'Content-Type',
    ];
    foreach($heads as $head){
      header_remove($head);
    }return true;
  }
  /* default headers for server api access */
  private function head(){
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
  /* ----- private socket methods ----- */
  /* websocket - method */
  private function websocket(){
    /* build TCP stream socket */
    $this->socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
    /* reuseable port */
    socket_set_option($this->socket,SOL_SOCKET,SO_REUSEADDR,1);
    /* bind socket to specified host */
    socket_bind($this->socket,0,$this->port);
    /* listen */
    socket_listen($this->socket);
    /* create & add listning socket to the list */
    $this->clients=[$this->socket];
    /* start looping the socket */
    return $this->loop();
  }
  /* start looping the socket */
  private function loop(){
    /* start endless loop */
    while(true){
      /* manage multipal connections */
      $ch=$this->clients;
      /* null must be a variable */
      $null=null;
      /* returns the socket resources in $ch array */
      socket_select($ch,$null,$null,0,10);
      /* check for new socket */
      if(in_array($this->socket,$ch)){
        /* accpet new socket */
        $ns=socket_accept($this->socket);
        /* add socket to client array */
        $this->clients[]=$ns;
        /* read data that's been sent by this socket */
        $header=socket_read($ns,$this->limit);
        /* perform new websocket handshake */
        $this->handshake($header,$ns);
        /* get ip address from the connected socket */
        socket_getpeername($ns,$ip);
        /* system notification to notify all clients about new connection */
        $res=json_encode([
          'code'=>11,
          'type'=>'system',
          'status'=>'connected',
          'ip'=>$ip,
          'version'=>self::version,
          'extensions'=>array_keys($this->extensions)
        ],JSON_PRETTY_PRINT);
        $this->send($res);
        $fs=array_search($this->socket,$ch);
        unset($ch[$fs]);
      }
      /* loop through all connected sockets */
      foreach($ch as $cs){
        /* check for any incomming data */
        while(@socket_recv($cs,$buf,$this->limit,0)>=1){
          /* decode data */
          $decoded=$this->decode($buf);
          /* write user log */
          $req=@json_decode($decoded);
          $req=$req?$req:$decoded;
          $this->userlog('request',$ip,$req);
          /* closing socket */
          if($decoded==''||$decoded=='close:'.$this->token){
            break 3;
          }
          /* execute incoming request for extensions */
          $result=$this->execute($decoded,$error);
          /* send all clients about result message */
          $res=json_encode([
            'code'=>$result?13:14,
            'type'=>'extension',
            'status'=>$result?'ok':'error',
            'error'=>$error,
            'ip'=>$ip,
            'version'=>self::version,
            'result'=>$result?$result:null,
          ],JSON_PRETTY_PRINT);
          /* write user log */
          $this->userlog('result',$ip,$result,$error);
          /* send data */
          $this->send($res);
          /* exist this loop */
          break 2;
        }
        $buf=@socket_read($cs,$this->limit,PHP_NORMAL_READ);
        /* checking disconnected client */
        if($buf===false){
          /* remove client for $this->clients array */
          $fs=array_search($cs,$this->clients);
          socket_getpeername($cs,$ip);
          unset($this->clients[$fs]);
          /* system notification to notify all clients about disconnected connection */
          $res=json_encode([
            'code'=>12,
            'type'=>'system',
            'status'=>'disconnected',
            'ip'=>$ip,
            'version'=>self::version
          ],JSON_PRETTY_PRINT);
          $this->send($res);
        }
      }
    }
    /* close the connection */
    return $this->close();
  }
  /* close the connected socket */
  private function close(){
    /* write user log */
    $this->userlog('close',$this->host,$this->port,$this->path);
    /* close socket resource */
    socket_close($this->socket);
    /* delete connection statement */
    if(isset($_SERVER['REQUEST_SCHEME'])
      &&preg_match('/^http/',$_SERVER['REQUEST_SCHEME'])){
      @unlink($this->connection);
    }
    /* return as OK */
    print('OK');
    return true;
  }
  /* send socket data */
  private function send($t=null){
    if(isset($t)){
      $t=$this->encode($t);
      foreach($this->clients as $c){
        @socket_write($c,$t,strlen($t));
      }return true;
    }return false;
  }
  /* decode incoming framed message */
  private function decode($t){
    $l=ord($t[1])&127;
    if($l==126){
      $m=substr($t,4,4);
      $d=substr($t,8);
    }elseif($l==127){
      $m=substr($t,10,4);
      $d=substr($t,14);
    }else{
      $m=substr($t,2,4);
      $d=substr($t,6);
    }
    $t="";
    for($i=0;$i<strlen($d);++$i){
      $t.=$d[$i]^$m[$i%4];
    }return $t;
  }
  /* encoding data for transfering to client */
  private function encode($t){
    $b1=0x80|(0x1&0x0f);
    $l=strlen($t);
    if($l<=125){
      $h=pack('CC', $b1, $l);
    }elseif($l>125&&$l<65536){
      $h=pack('CCn',$b1,126,$l);
    }elseif($l>=65536){
      $h=pack('CCNN',$b1,127,$l);
    }return $h.$t;
  }
  /* new client handshake */
  private function handshake($heads,$socket){
    $host=$this->host;
    $port=$this->port;
    $path=$this->path;
    $magickey=$this->magickey;
    $protocol=isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'?'wss':'ws';
    $head=[];
    foreach(preg_split('/\r\n/',$heads) as $i){
      $i=chop($i);
      if(preg_match('/\A(\S+): (.*)\z/',$i,$a)){
        $head[$a[1]]=$a[2];
      }
    }
    $key=base64_encode(pack('H*', sha1(@$head['Sec-WebSocket-Key'].$magickey)));
    $swp=@$head['Sec-WebSocket-Protocol'];
    $header="HTTP/1.1 101 Web Socket Protocol Handshake\r\n"
      ."Upgrade: websocket\r\n"
      ."Connection: Upgrade\r\n"
      ."WebSocket-Origin: $host\r\n"
      ."WebSocket-Location: $protocol://$host:$port\r\n"
      ."Sec-WebSocket-Protocol: $swp\r\n"
      ."Sec-WebSocket-Accept: $key\r\n\r\n";
    socket_write($socket,$header,strlen($header));
  }
}
