<?php
/* websocket server
 * ~ server for websocket
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 12th 2018
 */

/* define root directory */
defined('SERVER_ROOT') or define('SERVER_ROOT',str_replace('\\','/',__DIR__).'/');

/* prepare auto-load */
spl_autoload_register(function($c){
  $f=SERVER_ROOT.'classes/'.$c.'.php';
  if(!is_file($f)){
    header('content-type:text/plain');
    exit('Error: Failed to load "'.$c.'".');
  }require_once($f);
});

/* set ws cli directory */
defined('WS_CLI_DIR') or define('WS_CLI_DIR',SERVER_ROOT);

/* set sdb cli directory */
defined('SDB_CLI_DIR') or define('SDB_CLI_DIR',SERVER_ROOT.'sdb/');

/* initialize websocket */
new ws(
  9333, /* websocket server port */
  3     /* limit socket buffer size in kb */
);


