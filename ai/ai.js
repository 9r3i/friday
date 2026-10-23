/* ai.js */
function ai(f,x,url){
  if(CURRENT_DIR.match(/^(\.|:root)/ig)){
    return splash('Error: Cannot run AI in this directory.');
  }
  var script=qs('script[src="'+INI.core.apps_dir+'/ai/ai.js"]');
  if(!script){return splash('Error: Failed to get ai.js path.');}
  var server=dirname(script.src)+'/aiServer.php';
  var td=ce('textarea');
  td.classList.add('explorer-text');
  td.style.maxHeight=(W.innerHeight-88)+'px';
  td.style.height=(W.innerHeight-88)+'px';
  td.spellcheck=false;
  var di=explorerDialog(td,'AI (Ayyub-Isa)','xpl');
  td.focus();
  window.AI_LOADING=false;
  td.onkeyup=function(e){
    if(window.AI_LOADING||e.keyCode!==13){return false;}
    var sp=td.value.split(/\r\n|\r|\n/ig);
    var cmd=sp[sp.length-2];
    window.AI_LOADING=true;
    var aiData={aiRequest:btoa(JSON.stringify({dir:CURRENT_DIR,cmd:cmd}))};
    return W.post(server,function(r){
      window.AI_LOADING=false;
      td.value+=(r=='OK'?'':r.toString()+'\r\n');
      td.scrollTop=td.offsetHeight*sp.length;
    },aiData,false,null,null,null,function(e){
      window.AI_LOADING=false;
      td.value+=e.toString()+'\r\n$ ';
    });
  };
}

/* setup right click */
isOpenWithAI();
var aiRC={name:'AI (Ayyub-Isa)',title:'AI (Ayyub-Isa)',icon:'terminal',href:RCHref('ai')};
RIGHT_CLICK_MENU.content_blank['ai']=aiRC;
RIGHT_CLICK_MENU.global['ai']=aiRC;

/* remove ai from open with child */
function isOpenWithAI(fn,p){
  p=p?parseInt(p):0;
  if(p>=99||(RIGHT_CLICK_MENU.content.openWith
    &&RIGHT_CLICK_MENU.content.openWith.child
    &&RIGHT_CLICK_MENU.content.openWith.child.ai)){
    if(RIGHT_CLICK_MENU.content.openWith
      &&RIGHT_CLICK_MENU.content.openWith.child
      &&RIGHT_CLICK_MENU.content.openWith.child.ai){
      RIGHT_CLICK_MENU.content.openWith.child.ai=null;
      return true;
    }return false;
  }return setTimeout(function(e){
    p++;return isOpenWithAI(fn,p);
  },100);
}


