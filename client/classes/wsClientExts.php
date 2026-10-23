<?php
/* wsClientExts
 * ~ websocket data client extentsions class
 * ~ to help client extension to call the send method as added at wsClient
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 20th 2018
 */
class wsClientExts{
  public function __call($method=null,$args=[]){
    if(!isset($this->$method)){return false;}
    return @\call_user_func_array($this->$method,$args);
  }
}
