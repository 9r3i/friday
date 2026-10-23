<?php
/* chat class
 * ~ websocket client extension
 * ~ reponse for websocket client api
 * authored by 9r3i
 * https://github.com/9r3i
 * started at serptember 20th 2018
 * require: wsClientExts class
 */
class chat extends wsClientExts{
  private $started=false;
  public function __construct(){
    
  }
  public function onOpen($data){
    echo "\r\n--- Chat is Opened ---\r\n";
    $send=$this->send('chat','select',[]);
    print_r($data->status);
    echo "\r\n-------------------------------\r\n";
  }
  public function data($raw){
    $data=(object)[];
    if(isset($raw->insert)){$data=$raw->insert;}
    elseif(isset($raw->select)){$data=$raw->select;}
    elseif(isset($raw->clear)){$data=$raw->clear;}
    if(isset($data->data)){
      $last=count($data->data)-1;
      if(isset($data->data[$last])){
        print_r($data->data[$last]);
        if($this->started&&$data->data[$last]->user!=='mechine'){
          sleep(0.5);
          $send=$this->send('chat','insert',['mechine','aku juga']);
          $this->started=false;
        }else{$this->started=true;}
      }else{
        print_r($data->data);
      }
    }else{
      print_r($data);
    }
    echo "\r\n-------------------------------\r\n";
  }
}
