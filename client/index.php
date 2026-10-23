<?php
/* ws (client side)
 * ~ stands for websocket
 * ~ reponse for websocket client api
 * authored by 9r3i
 * https://github.com/9r3i
 * started at serptember 20th 2018
 * require: wsClient class
 */

/* define root directory */
defined('CLIENT_ROOT') or define('CLIENT_ROOT',str_replace('\\','/',__DIR__).'/');

/* prepare auto-load */
spl_autoload_register(function($c){
  $f=CLIENT_ROOT.'classes/'.$c.'.php';
  if(!is_file($f)){
    header('content-type:text/plain');
    exit('Error: Failed to load "'.$c.'".');
  }require_once($f);
});

/* set header */
header('content-type:text/plain');

/* connect to server */
$wsClient=new wsClient(
  '127.0.0.1',
  9333,
  '2jmj7l5rSw0yVbvlWAYkKYBwk',
  CLIENT_ROOT.'ext',
  [
    'ai'
  ]
);

/* find error */
if($wsClient->error){
  echo $wsClient->error;
}


