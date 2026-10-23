<?php
/* wsClient
 * ~ websocket client class
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 20th 2018
 */
class wsClient{
  const version='1.0.0';
  protected $host=null;
  protected $port=null;
  protected $token=null;
  protected $path=null;
  protected $client=null;
  protected $exts=null;
  private $started=false;
  public $error=false;
  public function __construct($host=null,$port=null,$token=null,$path=null,$exts=[],$timeout=30,$headers=''){
    if(!is_string($host)||!isset($port)||!is_string($token)||!is_string($path)
      ||!preg_match('/^[a-z0-9]+$/i',$token)){
      $this->error='Error: Require parameter host, port, token and path of extensions';
      return false;
    }
    $this->host=$host;
    $this->port=intval($port);
    $this->token=$token;
    $key=base64_encode(uniqid());
    $header="GET / HTTP/1.1\r\n"
      ."Host: $host\r\n"
      ."Pragma: no-cache\r\n"
      ."Upgrade: WebSocket\r\n"
      ."Connection: Upgrade\r\n"
      ."Sec-WebSocket-Key: $key\r\n"
      ."Sec-WebSocket-Protocol: mserver\r\n"
      ."Sec-WebSocket-Version: 13\r\n";
    if(!empty($headers)){
      foreach($headers as $h){$header.=$h."\r\n";}
    }$header.="\r\n";
    $open=@fsockopen($host,$port,$errno,$errstr,intval($timeout));
    if(!is_resource($open)){
      $this->error='Error: Failed to connect to websocket, ('.$errno.') '.$errstr;
      return false;
    }
    stream_set_timeout($open,intval($timeout));
    if(!fwrite($open,$header)){
      $this->error='Error: Failed to send header to websocket, ('.$errno.') '.$errstr;
      return false;
    }
    $res=fread($open,1024);
    if(!strpos($res,' 101 ')||!strpos($res,'Sec-WebSocket-Accept: ')){
      $this->error='Error: Server did not accept to upgrade connection to websocket.';
      return false;
    }
    $path=str_replace('\\','//',$path);
    $path.=substr($path,-1)!='/'?'/':'';
    if(!is_dir($path)){@mkdir($path,0755,true);}
    $this->path=$path;
    $this->client=$open;
    $this->exts=[];
    $exts=is_array($exts)?$exts:[];
    foreach($exts as $ext){
      $path=$this->path.$ext.'.php';
      if(is_file($path)){
        require_once($path);
        if(class_exists($ext,false)&&method_exists($ext,'data')){
          $object=new $ext;
          if(is_object($object)){
            $object->send=function($ext,$method,$args=[]){
              return $this->send($ext,$method,$args);
            };
            $this->exts[$ext]=$object;
          }
        }
      }
    }return $this->start();
  }
  public function close(){
    if(!is_resource($this->client)){return false;}
    fclose($this->client);
    return $this->request('close:'.$this->token);
  }
  private function start(){
    if($this->started||!is_resource($this->client)){return false;}
    $this->started=true;
    $lastError=null;
    $lastTimeError=null;
    while(true){
      $read=$this->read($error);
      if($error){
        if($lastError!==$error){
          echo 'Error: '.$error."\r\n";
        }
        if($lastTimeError&&(microtime(true)-$lastTimeError)<1){
          echo 'Error: Server closed.'."\r\n";
          break;
        }
        $lastError=$error;
        $lastTimeError=microtime(true);
        $error=false;
        continue;
      }
      $data=@json_decode($read);
      if(!is_object($data)){
        echo 'Error: Failed to parse result.'."\r\n";
        continue;
      }
      if(!isset($data->error)||!isset($data->result)){
        if(!isset($data->code,$data->type)){
          print_r($data);
        }elseif($data->type=='system'){
          foreach($this->exts as $ext=>$object){
            if(method_exists($object,'onOpen')){
              @\call_user_func_array([$object,'onOpen'],[$data]);
            }
          }
        }continue;
      }
      if($data->error){
        echo 'Error: '.$data->error."\r\n";
        continue;
      }
      foreach($data->result as $ext=>$res){
        if(array_key_exists($ext,$this->exts)){
          @\call_user_func_array([$this->exts[$ext],'data'],[$res]);
        }
      }
    }return true;
  }
  private function read(&$error=null){
    if(!is_resource($this->client)){return false;}
    $data='';
    do{
      $header=fread($this->client,2);
      if(!$header){
        $error='Failed to read websocket header.';
        return false;
      }
      $opcode=ord($header[0])&0x0F;
      $final=ord($header[0])&0x80;
      $masked=ord($header[1])&0x80;
      $payload_len=ord($header[1])&0x7F;
      $ext_len=0;
      if($payload_len>=0x7E){
        $ext_len=2;
        if($payload_len==0x7F){$ext_len=8;}
        $header=fread($this->client,$ext_len);
        if(!$header){
          $error='Failed to read websocket extension header.';
          return false;
        }$payload_len=0;
        for($i=0;$i<$ext_len;$i++){
          $payload_len+=ord($header[$i])<<($ext_len-$i-1)*8;
        }
      }
      if($masked){
        $mask=fread($this->client,4);
        if(!$mask){
          $error='Failed to read websocket mask header.';
          return false;
        }
      }
      $frame_data='';
      while($payload_len>0){
        $frame= fread($this->client,$payload_len);
        if(!$frame){
          $error='Failed to read websocket.';
          return false;
        }
        $payload_len-=strlen($frame);
        $frame_data.=$frame;
      }
      if($opcode==9){
        fwrite($this->client,chr(0x8A).chr(0x80).pack("N",rand(1,0x7FFFFFFF)));    
        continue;
      }elseif($opcode==8){
        fclose($this->client);
      }elseif($opcode<3){ /* 0 = continuation frame, 1 = text frame, 2 = binary frame */
        $data_len=strlen($frame_data);
        if($masked){
          for($i=0;$i<$data_len;$i++){
            $data.=$frame_data[$i]^$mask[$i%4];
          }
        }else{
          $data.=$frame_data;
        }
      }continue;
    }while(!$final);return $data;
  }
  private function send($ext=null,$method=null,$args=[],$final=true){
    if(!is_string($ext)||!is_string($method)){return false;}
    $data=json_encode([
      'ext'=>$ext,
      'method'=>$method,
      'args'=>is_array($args)?$args:[],
      'token'=>$this->token,
    ]);
    return $this->request($data,true);
  }
  private function request($data=null,$final=true){
    $header=chr(($final?0x80:0)|0x02);
    if(strlen($data)<126){
      $header.=chr(0x80|strlen($data));
    }elseif(strlen($data)<0xFFFF){
      $header.=chr(0x80|126).pack("n",strlen($data));
    }else{
      $header.=chr(0x80|127).pack("N",0).pack("N",strlen($data));
    }
    $mask=pack("N",rand(1,0x7FFFFFFF));       
    $header.=$mask;
    for($i=0;$i<strlen($data);$i++){
      $data[$i]=chr(ord($data[$i])^ord($mask[$i%4]));
    }return fwrite($this->client,$header.$data);
  }
}
