/* response.js
 * ~ response for ai friday
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 21st 2018
 */
var SPEECH_RESPONSE={
good:function(text,cb){
  var nt=text.replace(/(friday)$/ig,'').trim();
  var text='good '+nt+', sir.';
  return SPEECH_RESPONSE.__callback(cb,text);
},
find:function(text,cb){
  text=text.replace(/^(me)/ig,'');
  return SPEECH_RESPONSE.search(text,cb);
},
search:function(text,cb){
  var cmd=false;
  if(text.match(/^((from|in)\s)?(wikipedia|google|bing|being|lyric)\s((something\s)?about|of)?/ig)){
    var nc=text.match(/(wikipedia|google|bing|being|lyric)/i)[0].toLowerCase();
    var nt=text.replace(/^((from|in)\s)?(wikipedia|google|bing|being|lyric)\s((something\s)?about|of)?/ig,'').trim();
    SPEECH_SEARCH_MEAN=null;
    nc=nc=='being'?'bing':nc;
    cmd='search '+nc+' "'+nt+'"';
  }else if(SPEECH_SEARCH_MEAN){
    var key=SPEECH_SEARCH_MEAN;
    if(text.match(/(wikipedia|google|bing|being|lyric)/ig)){
      var nc=text.match(/(wikipedia|google|bing|being|lyric)/i)[0].toLowerCase();
      SPEECH_SEARCH_MEAN=null;
      nc=nc=='being'?'bing':nc;
      cmd='search '+nc+' "'+key+'"';
    }else{
      var answers=['wikipedia','bing','lyric'];
      var akey=parseInt(Math.random().toString().substr(-1))%answers.length;
      SPEECH_MEAN='search in '+answers[akey]+' about '+SPEECH_SEARCH_MEAN;
      text='i\m sorry, sir. i didn\'t get it. do you want me to search in '+answers[akey]+', sir?';
      return SPEECH_RESPONSE.__callback(cb,text);
    }
  }else if(text.match(/(from|in)\s(wikipedia|google|bing|being|lyric)$/ig)){
    var nc=text.match(/(wikipedia|google|bing|being|lyric)$/i)[0].toLowerCase();
    var nt=text.replace(/(from|in)\s(wikipedia|google|bing|being|lyric)$/ig,'')
               .replace(/^((something\s)?about)?/ig,'').trim();
    nc=nc=='being'?'bing':nc;
    cmd='search '+nc+' "'+nt+'"';
  }else{
    text=text.replace(/^((something\s)?about)?/ig,'').trim();
    SPEECH_SEARCH_MEAN=text;
    SPEECH_MEAN='search in google about '+text;
    text='do you want me to search in google, sir?';
    return SPEECH_RESPONSE.__callback(cb,text);
  }
  if(!cmd){
    text='i\'m sorry, sir. there is nothing to search with "'+text+'".';
    return SPEECH_RESPONSE.__callback(cb,text);
  }
  var dl=null,ul=null,hd=null;
  var data={aiRequest:btoa(JSON.stringify({
    cmd:'ai '+cmd,
    dir:SPEECH_TEMP_DIR?SPEECH_TEMP_DIR:'/',
  }))};
  __status('searching...');
  __speak('searching');
  return __post(AI_SERVER,function(r){
    __status('stop');
    var parsed=SPEECH_RESPONSE.__stringHTML(r);
    var encoded=false;
    try{encoded=btoa(parsed);}catch(e){}
    if(encoded){
      var f=document.createElement('iframe');
      f.src='data:text/html;base64,'+encoded;
      f.style.border='1px none';
      f.style.width='100%';
      f.style.height='350px';
      var p=__qs('#result');
      if(p){p.appendChild(f);}
    }else{
      var p=__qs('#result');
      if(p){p.innerHTML=parsed;}
    }text='here we go, sir.';
    return SPEECH_RESPONSE.__callback(cb,text);
  },data,function(e){
    __status('failed');
    __log(e);
    text='i\'m sorry, sir. there is some problem in connection.';
    return SPEECH_RESPONSE.__callback(cb,text);
  },hd,ul,dl,true);
},
give:function(text,cb){
  if(text.match(/^me\s(a|the)?\s?(console)$/ig)){
    var p=__qs('#console');
    var text='i\'m sorry, sir. console has been ready.';
    if(!p){
      p=document.createElement('input');
      p.id='console';
      p.type='text';
      document.body.insertBefore(p,document.body.firstChild);
      text='here we go, sir.';
      p.focus();
      p.onkeyup=function(e){
        if(e.keyCode!==13){return false;}
        var val=this.value;
        this.value='';
        var out=val.match(/^clear\sconsole$/ig)?false:true;
        return __response(val,100,__result,out);
      };
    }return SPEECH_RESPONSE.__callback(cb,text);
  }
  var text='i\'m sorry, sir. there is nothing to give with "'+text+'".';
  return SPEECH_RESPONSE.__callback(cb,text);
},
clean:function(text,cb){
  text=text.replace(/^(all)/ig,'result');
  SPEECH_SEARCH_MEAN=null;
  SPEECH_MEAN=null;
  return SPEECH_RESPONSE.clear(text,cb);
},
clear:function(text,cb){
  if(text.match(/^(the|a)?\s?results?$/ig)){
    var text='i\'m sorry, sir. i can\'t find the result.';
    var p=__qs('#result');
    if(p){
      p.innerText='';
      text='result field has been cleared, sir.';
    }return SPEECH_RESPONSE.__callback(cb,text);
  }
  if(text.match(/^(console)$/ig)){
    var text='i\'m sorry, sir. i can\'t find the console.';
    var p=__qs('#console');
    if(p){
      p.parentElement.removeChild(p);
      text='console has been cleared, sir.';
    }return SPEECH_RESPONSE.__callback(cb,text);
  }
  var text='i\'m sorry, sir. there is nothing to clear with "'+text+'".';
  return SPEECH_RESPONSE.__callback(cb,text);
},
read:function(text,cb){
  if(text.match(/^(the|a)?\s?results?$/ig)){
    var text='i\'m sorry, sir. i can\'t find the result.';
    var p=__qs('#result');
    if(p&&p.innerText){
      text=p.innerText;
    }return SPEECH_RESPONSE.__callback(cb,text);
  }
  var text='i\'m sorry, sir. there is nothing to read with "'+text+'".';
  return SPEECH_RESPONSE.__callback(cb,text);
},
forget:function(text,cb){
  if(text.match(/^(it|that)/ig)){
    SPEECH_SEARCH_MEAN=null;
    SPEECH_MEAN=null;
    text='yes, sir.';
  }else{
    text='i\'m sorry, sir. there is nothing to forget with "'+text+'".';
  }return SPEECH_RESPONSE.__callback(cb,text);
},
check:function(text,cb){
  var cmd=false;
  if(text.match(/^space drives?$/ig)){
    cmd='space calc';
  }else if(text.match(/^globals?$/ig)){
    cmd='test globals';
  }else if(text.match(/^(em|them|am|in|and)\s(server|surfer|suffer)\s?(\d+)?$/ig)){
    var port=text.replace(/^(em|them|am|in|and)\s(server|surfer|suffer)/ig,'').trim();
    cmd='test mserver '+port;
  }else if(text.match(/^(electric)\s([\d\.]+)$/ig)){
    var number=text.replace(/^(electric)/ig,'').trim();
    cmd='kwh calc now '+parseFloat(number);
  }else if(text.match(/^(boruto|burrito|comic)\s(\d+)$/ig)){
    var number=text.replace(/^(boruto|burrito|comic)/ig,'').trim();
    cmd='test boruto '+parseInt(number);
  }
  if(!cmd){
    text='i\'m sorry, sir. there is nothing to check with "'+text+'".';
    return SPEECH_RESPONSE.__callback(cb,text);
  }
  var dl=null,ul=null,hd=null;
  var data={aiRequest:btoa(JSON.stringify({
    cmd:'ai '+cmd,
    dir:SPEECH_TEMP_DIR?SPEECH_TEMP_DIR:'/',
  }))};
  __status('loading...');
  return __post(AI_SERVER,function(r){
    __status('stop');
    var p=__qs('#result');
    if(p){p.innerText=r;}
    text='here we go, sir.';
    return SPEECH_RESPONSE.__callback(cb,text);
  },data,function(e){
    __status('failed');
    __log(e);
    text='i\'m sorry, sir. there is some problem in connection.';
    return SPEECH_RESPONSE.__callback(cb,text);
  },hd,ul,dl,true);
},
ai:function(text,cb){
  var dl=null,ul=null,hd=null;
  var data={aiRequest:btoa(JSON.stringify({
    cmd:'ai '+text,
    dir:SPEECH_TEMP_DIR?SPEECH_TEMP_DIR:'/',
  }))};
  __status('loading...');
  return __post(AI_SERVER,function(r){
    __status('stop');
    var p=__qs('#result');
    if(p){p.innerText=r;}
    text='here we go, sir.';
    return SPEECH_RESPONSE.__callback(cb,text);
  },data,function(e){
    __status('failed');
    __log(e);
    text='i\'m sorry, sir. there is some problem in connection.';
    return SPEECH_RESPONSE.__callback(cb,text);
  },hd,ul,dl,true);
},
__question:function(nc,nt,cb){
  var pp=/^(do(es)?|did|am|are|is|w(as|ere|h(at|y|ere|en|o)|ould|ill)|how|c(an|ould)|sh(all|ould))\b/ig;
  var ps=/^(do(es)?|did|am|are|is|w(as|ere|ould|ill)|c(an|ould)|sh(all|ould))$/ig;
  var text='i\'m afraid if "'+nc+'" key is not ready yet, sir.';
  if(nc.match(/^how$/ig)&&nt.match(/^(are\s)?you\sdoing/ig)){
    text='i\'m good, sir.';
  }else if(nc.match(/^how$/ig)&&nt.match(/^((is|'s)\s)?it\sgoing/ig)){
    text=text=SPEECH_RESPONSE.__condition();;
  }else if(nc.match(/^(would|can)$/ig)&&nt.match(/^you\s(please\s)?(just\s)?[a-z]+/ig)){
    var com=nt.replace(/^you\s(please\s)?(just\s)?/ig,'').trim();
    var nnc=com.match(/^[a-z]+/ig)[0].toLowerCase();
    var nnt=com.replace(/^[a-z]+/ig,'').trim();
    if(typeof SPEECH_RESPONSE[nnc]==='function'){
      return SPEECH_RESPONSE[nnc].apply(SPEECH_RESPONSE[nnc],[nnt,cb]);
    }text='i\'m afraid if "'+nnc+'" key is not ready yet, sir.';
  }else if(nc.match(ps)){
    text=SPEECH_RESPONSE.__auxilary(nc,nt);
  }
  
  
  
  return SPEECH_RESPONSE.__callback(cb,text);
},
__stringHTML:function(s){
  if(typeof s!=='string'){return false;}
  var doc=(new DOMParser()).parseFromString(s,'text/html');
  var href=doc.querySelectorAll('[href]');
  var i=href.length;
  while(i--){
    if(href[i].tagName.toLowerCase()=='a'){
      href[i].target='_blank';
    }else{
      href[i].href='#';
    }
  }
  var scr=doc.querySelectorAll('script');
  var i=scr.length;
  while(i--){scr[i].parentElement.removeChild(scr[i]);}
  return doc.body.innerHTML;
},
__callback:function(cb,text,stop){
  stop=stop===true?true:false;
  cb=typeof cb==='function'?cb:function(){};
  if(typeof text!=='string'){
    return cb(false);
  }var timeout=__length(text)*SPEECH_TIMEOUT;
  return cb({
    text:text,
    timeout:timeout,
    stop:stop
  });
},
__auxilary:function(nc,nt){
  var subject=nt.match(/^you\b/ig)?'i':nt.match(/^i\b/ig)?'you'
    :nt.match(/^[a-z]+\b/ig)?nt.match(/^[a-z]+\b/ig)[0]:'that';
  var positive=nc==='are'?'am'
    :nc==='were'?'was'
    :nc==='am'?'are'
    :nc==='was'?'ware':nc;
  var negative=nc==='are'?' am not'
    :nc==='were'?' wasn\'t'
    :nc==='am'?'\'re not'
    :nc==='was'?'\'re not'
    :nc==='is'?'\'s not'
    :nc.match(/((wi|sha)ll)/ig)?' won\'t'
    :' '+nc+'n\'t';
  var answers=[
    'yes, '+subject+' '+positive+', sir.',
    'no, '+subject+negative+', sir.',
    'maybe, i don\'t know, sir.'
  ];
  var akey=parseInt(Math.random().toString().substr(-1))%answers.length;
  return answers[akey];
},
__condition:function(){
  var answers=[
    'the engine is stable, sir.',
    'the engine is unstable, sir.',
    'the script renderer is not complete yet, sir.',
    'i don\'t know, sir. something is going wrong.'
  ];
  var akey=parseInt(Math.random().toString().substr(-1))%answers.length;
  return answers[akey];
},
__temp:function(){
  return false;
}
};


