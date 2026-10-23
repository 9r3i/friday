/* index.js */
var W,D,WS=null,
WS_PROTOCOL=W.location.protocol.match(/^https/ig)?'wss':'ws:',
WS_HOST=W.location.hostname,
WS_PORT=9333,
WS_TOKEN='2jmj7l5rSw0yVbvlWAYkKYBwk',
WS_EXT_PATH='js/ext/',
WS_EXTS=['ai'],
WS_SERVER=WS_PROTOCOL+'//'+WS_HOST+':'+WS_PORT,
WS_CONNECTOR_PORT=W.location.port?W.location.port:W.location.protocol.match(/^https/ig)?443:80,
WS_CONNECTOR_PROTOCOL=W.location.protocol,
WS_CONNECTOR_PATH=W.location.pathname.substr(1).replace(/[^\/]+$/ig,'')+'server/',
WS_CONNECTOR_URL=WS_CONNECTOR_PROTOCOL+'//'+WS_HOST+':'+WS_CONNECTOR_PORT+'/'+WS_CONNECTOR_PATH;


/* initialize connection  */
function wsInit(){
  var index=gebi('index');
  if(index){index.innerText='Connecting...';}
  wsLoadExtension(function(e){
    return wsConnect();
  });
}

/* start ws connection */
function wsConnect(has){
  WS=new ws(WS_SERVER,WS_TOKEN,WS_EXTS,function(e){
    var index=gebi('index');
    if(index){index.innerText=e;}
    if(has){return false;}
    var btn=ce('button');
    btn.classList.add('index-button');
    btn.innerText='Start Websocket Server';
    index.innerHTML='';
    index.appendChild(btn);
    btn.onclick=function(e){
      index.removeChild(btn);
      wsConnector();
      return wsInit();
    };
  });return true;
}

/* websocket connector */
function wsConnector(){
  if(typeof WS_CONNECTOR_URL!=='string'
    ||typeof WS_TOKEN!=='string'){
    return false;
  }
  W.post(WS_CONNECTOR_URL+'?ws='+WS_TOKEN,function(r){
    console.log('websocket closed',r);
  });return true;
}

/* load extension */
function wsLoadExtension(cb,i,p){
  cb=typeof cb==='function'?cb:function(){};
  i=i?parseInt(i):0;
  p=p?parseInt(p):0;
  if(!WS_EXTS||!WS_EXTS[i]){
    return cb(true);
  }
  var ext=WS_EXTS[i];
  var path=WS_EXT_PATH+ext+'.js';
  if(!qs('script[src="'+path+'"]')){
    load_script(path);
  }
  if(p>=99||typeof W[ext]==='function'){
    i++;
    return wsLoadExtension(cb,i);
  }
  setTimeout(function(e){
    p++;
    return wsLoadExtension(cb,i,p);
  },9);
}


