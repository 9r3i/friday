<?php
/* ai class
 * ~ extension for ws (websocket)
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 21st 2018
 */
class ai{
  private $total=0;
  private $data=null;
  private $root=null;
  public function condition(){
    $db=$this->root();
    $get=$db->query('select count(id) as row from chat');
    $total=(int)$get[0]['row'];
    if($total!==$this->total){
      $this->total=$total;
      $this->data=$db->query('select * from chat');
      return true;
    }return false;
  }
  public function result(){
    return [
      'date'=>date(DATE_ISO8601),
      'total'=>$this->total,
      'data'=>$this->data,
    ];
  }
  public function select(){
    $db=$this->root();
    $sel=$db->query('select * from chat');
    return [
      'date'=>date(DATE_ISO8601),
      'data'=>$sel,
    ];
  }
  public function clear(){
    $db=$this->root();
    $del=$db->query('delete from chat where id>0');
    return $this->select();
  }
  public function insert($u=null,$s=null){
    $u=is_string($u)?$u:'[blank]';
    $s=is_string($s)?$s:'[blank]';
    $db=$this->root();
    $ins=$db->query('insert into chat '.http_build_query([
      'user'=>'string:'.$u,
      'text'=>'string:'.$s
    ]));
    return $this->select();
  }
  private function root(){
    if($this->root){return $this->root;}
    $db=new sdb('localhost','master','luthfie','chat');
    if(!$db->error){$this->root=$db;return $db;}
    $db=new sdb('localhost','root','','root');
    $db->query('create database db=chat&user=master&pass=luthfie');
    $db=new sdb('localhost','master','luthfie','chat');
    $db->query('create table chat id=aid()&user=string()&text=string()');
    return $db;
  }
}
