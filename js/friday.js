/* friday.js
 * ~ ai friday
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 20th 2018
 */
var SPEECH_RESPONSE,
SPEECH_SEARCH_MEAN,
AI_SERVER='ai/aiServer.php',
AI_FRIDAY='ai/aiFriday.php',
SPEECH_RECOGNITION=null,
SPEECH_TIMEOUT=55,
SPEECH_RESULT=null,
SPEECH_MEAN=null,
SPEECH_MEANS=null,
SPEECH_SEARCH_MEAN=null,
SPEECH_TEMP_DIR=null,
SPEECH_TEMP=null;

/* initialize */
function init(){
  __log('initialize');
  try{
    SPEECH_RECOGNITION=window.SpeechRecognition
      ||window.webkitSpeechRecognition;
  }catch(e){}
  if(!SPEECH_RECOGNITION){
    __log('the recognition is not available');
    return __speak('the recognition is not available, sir.');
  }
  __aiFriday('tempDir',[],function(r){
    SPEECH_TEMP_DIR=r;
  });
  return __aiFriday('meanGetAll',[],function(r){
    SPEECH_MEANS=r;
    //__speak('i\'m ready.');
    __status('ready');
    var head=__qs('.header');
    if(head){head.onclick=function(e){
      if(window.speechSynthesis.speaking){
        window.speechSynthesis.pause();
      }
      __speak('do you want to reload, sir?');
      var con=confirm('Reload?');
      if(!con){return false;}
      window.location.reload();
    };}
    return __rec();
  },function(e){
    __log(e);
    __speak('i\'m sorry, sir. failed to get mean data.');
    __status('failed to get mean data',true);
  });
}

/* response
 * @param:
 *   text = string of speech transcript
 *   pr   = int of percentage of confidence
 * @return: object with keys, text and timeout
 */
function __response(text,pr,cb,stop){
  cb=typeof cb==='function'?cb:function(){};
  if(typeof text!=='string'){return cb(false);}
  pr=pr?parseInt(pr):0;
  stop=stop===true?true:false;
  var pcom=/^(((al)?right|okay|so|well|please|hi|hey|hello)\s)?(\bfriday\s)?(\bjust\s)?[a-z]+/ig;
  var pcomr=/^(((al)?right|okay|so|well|please|hi|hey|hello)\s)?(\bfriday\s)?(\bjust\s)?/ig;
  var ptime=/\bwhat\s(time is|times)\s(this|days|does|it)$/ig;
  var pday=/\bwhat\s(day is|does|days)\s(this|days|does|it)$/ig;
  var pout=/^(((al)?right|okay|so|well|you|please)\s)?(just\s)?(stop|closed?|(sign\s)?out)(\s(please|okay))?$/ig;
  var ppraise=/^(((al)?right|okay|that('?s)?|well)\s)?(friday\s)?(you(r|'re)?\s(doing\s)?)?((very\s)?good|great)(\sfriday)?$/ig;
  var pgrate=/^(((al)?right|okay|that('?s)?|well)\s)?(friday\s)?(thank\syou|(many\s)?thanks)(\s((so|very)\smuch|a\s?lot))?(\sfriday)?$/ig;
  var pquest=/^(do(es)?|did|am|are|is|w(as|ere|h(at|y|ere|en|o)|ould|ill)|how|c(an|ould)|sh(all|ould))\b/ig;
  /* mean suggestion */
  if(SPEECH_MEAN){
    if(text.match(/^(ye(s|p|ah))\b/ig)){
      text=SPEECH_MEAN;
      SPEECH_MEAN=null;
      return __response(text,100,cb,stop);
    }else if(text.match(/^(no(pe|w)?)\b/ig)){
      SPEECH_MEAN=null;
      var nt=text.replace(/^(no(pe|w)?)\b/ig,'').trim();
      if(nt.trim()!==''){
        return __response(nt,pr,cb,stop);
      }text='so, what can i do for you, sir?';
    }else{
      SPEECH_MEAN=null;
      return __response(text,100,cb,stop);
    }
  }
  /* stop/close/out of spech looping */
  else if(text.match(pout)){
    var statement=text.match(/close/ig)?'closed'
      :text.match(/out/ig)?'out':'stopped';
    var answers=[
      'alright, sir. i\'ll be '+statement+'.',
      'yes, sir. i\'m '+statement+'.'
    ];
    var akey=parseInt(Math.random().toString().substr(-1))%answers.length;
    text=answers[akey];
    stop=true;
  }
  /* praise and grate */
  else if(text.match(ppraise)){
    text='thank you, sir.';
  }else if(text.match(pgrate)){
    text='you\'re welcome, sir.';
  }
  /* time and day of today */
  else if(text.match(ptime)){
    var time=new Date().toTimeString().substr(0,5);
    text='time is '+time;
  }else if(text.match(pday)){
    var days=['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    var today=days[new Date().getDay()];
    text='today is '+today+', sir.';
  }
  /* command */
  else if(text.match(pcom)){
    var nt=text.replace(pcomr,'').trim();
    var nc=nt.match(/^[a-z]+/ig)[0].toLowerCase();
    var nnt=nt.replace(/^[a-z]+/ig,'').trim();
    if(SPEECH_RESPONSE&&typeof SPEECH_RESPONSE[nc]==='function'){
      return SPEECH_RESPONSE[nc].apply(SPEECH_RESPONSE[nc],[nnt,cb]);
    }else if(nc.match(pquest)){
      return SPEECH_RESPONSE.__question(nc,nnt,cb);
    }else{
      text='i don\'t think "'+nt+'" is in my dictionary, sir.';
    }
  }
  /* search for suggestions */
  else if(SPEECH_MEANS.hasOwnProperty(text.toLowerCase())){
    text=SPEECH_MEANS[text.toLowerCase()];
    SPEECH_MEAN=text;
    text='do you mean "'+text+'", sir?';
  }
  /* unsure statement */
  else if(pr<=50&&text!==''){
    SPEECH_MEAN=text;
    text='do you mean "'+text+'", sir?';
  }else{
    text='i don\'t think "'+text+'" is in my dictionary, sir.';
  }
  text=text===''?'   ':text;
  var timeout=__length(text)*SPEECH_TIMEOUT;
  return cb({
    text:text,
    timeout:timeout,
    stop:stop
  });
}
/* new recognition */
function __rec(){
  if(typeof SPEECH_RECOGNITION!=='function'){
    __log('the recognition is not available');
    return __speak('the recognition is not available, sir.');
  }
  var REC=new SPEECH_RECOGNITION();
  REC.continuous=true;
  REC.start();
  REC.onresult=function(r){
    var c=r.resultIndex;
    var s=r.results[c];
    SPEECH_RESULT=s;
  };
  REC.onstart=function(e){
    return __status(e.type);
  };
  REC.onerror=function(e){
    __status(e.error,true);
    var text='i\'m sorry, sir. there is something wrong with '+e.error+'.';
    var isOK=false;
    if(e.error=='no-speech'){
      //text='i\'m sorry, sir. i can\'t hear your voice.';
      text='   ';
      isOK=true;
    }else if(e.error=='not-allowed'){
      text='i\'m sorry, sir. i\'m not allowed to listen.';
    }else if(e.error=='aborted'){
      text='i\'m sorry, sir. i was aborted to listen.';
    }__speak(text);
    __log(e.type+': '+e.error);
    if(!isOK){return false;}
    setTimeout(function(){
      return __rec();
    },text.length*SPEECH_TIMEOUT);
  };
  REC.onspeechend=function(e){
    __status('stop');
    //var p=new parse('result');
    //p.json(SPEECH_RESULT,5);
    var pr=Math.floor(SPEECH_RESULT[0].confidence*100);
    var text=SPEECH_RESULT[0].transcript;
    __log(pr+'% - '+text);
    return __response(text,pr,__result);
  };
}
/* result */
function __result(res){
  if(typeof res!=='object'||res===null){
    text='i\'m sorry, sir. i can\'t response it.';
    res={
      text:text,
      timeout:text.length*SPEECH_TIMEOUT
    }
  }
  __log('----> '+res.text);
  __speak(res.text);
  if(!res.timeout||res.stop){return false;}
  setTimeout(function(){
    return __rec();
  },res.timeout);
}
/* length of string - convert numeric as longer length */
function __length(s){
  if(typeof s!=='string'){return 0;}
  var m=s.match(/\d|\*|\./ig);
  return m?s.length+(m.length*5*2):s.length;
}
/* status - require: __qs */
function __status(s,e){
  if(typeof s!=='string'){return false;}
  var status=__qs('#status');
  if(!status){return false;}
  var type=e===true?'error':'status';
  status.innerText=type+': '+s;
  return true;
}
/* log - require: __qs */
function __log(s){
  if(typeof s!=='string'){return false;}
  var log=__qs('#log');
  if(!log){return false;}
  log.value+='- '+s+'\r\n';
  log.scrollTop=log.value.length;
  return __aiFriday('logSave',[s],function(r){
    console.log(r);
  },function(e){
    console.log(e);
  });
}
/* save a new mean word */
function __saveMean(word,mean){
  if(typeof word!=='string'||typeof mean!=='string'){
    return false;
  }return __aiFriday('meanSet',[word,mean],function(r){
    console.log(r);
  },function(e){
    console.log(e);
  });
}
/* ai friday request */
function __aiFriday(method,args,cb,er){
  cb=typeof cb==='function'?cb:function(){};
  er=typeof er==='function'?er:function(){};
  if(typeof AI_FRIDAY!=='string'){return er('Error: Undefined variable AI_FRIDAY.');}
  if(typeof method!=='string'){return er('Error: Invalid method.');}
  args=Array.isArray(args)?args:typeof args!=='object'?[args]:[];
  var data={aiFriday:btoa(JSON.stringify({
    method:method,args:args,
    token:'ai'+((new Date).getTime()+3600).toString(36)
  }))};
  __post(AI_FRIDAY,function(r){
    if(r.toString().match(/^error/i)){
      return er(r.toString());
    }return cb(r);
  },data,er,null,null,null,false);
}



/* speak */
function __speak(s){
  if(typeof s!=='string'){return false;}
  var p=new SpeechSynthesisUtterance();
  p.text=s;
  p.volume=1;
  p.rate=1;
  p.pitch=1;
  window.speechSynthesis.speak(p);
  return true;
};
/* path - return dirname of friday.js path */
function __path(){
  var r=__qsa('script[src]'),i=r.length,x='';
  while(i--){
    if(r[i].src.match(/friday\.js$/g)){
      x=r[i].src.replace(/friday\.js$/g,'');
    }
  }return x;
}
/* load a script */
function __loadScript(f){
  if(typeof f!=='string'){return false;}
  var c=__qs('script[src="'+f+'"]');
  if(c){return false;}
  var j=document.createElement('script');
  j.type='text/javascript';
  j.async=true;
  j.src=f;
  var s=document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(j,s);
  return true;
}
/* query selector */
function __qs(i){
  return document.querySelector(i);
}
/* query selector all */
function __qsa(i){
  return document.querySelectorAll(i);
}
/* post */
function __post(u,cb,d,er,hd,ul,dl,txt){
  var x=false;
  cb=typeof cb==='function'?cb:function(){};
  er=typeof er==='function'?er:function(){};
  if(window.XMLHttpRequest){
    x=new XMLHttpRequest();
  }else{
    var xhf=[
      function(){return new ActiveXObject("Msxml2.XMLHTTP");},
      function(){return new ActiveXObject("Msxml3.XMLHTTP");},
      function(){return new ActiveXObject("Microsoft.XMLHTTP");}
    ];
    for(var i=0;i<xhf.length;i++){
      try{x=xhf[i]();}catch(e){continue;}break;
    }
  }if(!x){return;}
  x.open(typeof d==='object'&&d!==null?'POST':'GET',u,true);
  if(typeof d==='object'&&d!==null&&typeof d.append!=='function'){
    x.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    d=http_build_query(d);
  }
  if(typeof hd==='object'&&hd!==null){
    x.setRequestHeader(i,hd[i]);
  }
  if(typeof ul==='function'){x.upload.onprogress=ul;}
  if(typeof dl==='function'){x.addEventListener("progress",dl,false);}
  x.send(d);
  x.onreadystatechange=function(){
    if(x.readyState===4){
      if(x.status===200){
        var text=x.responseText?x.responseText:' ';
        if(txt){return cb(text);}
        try{var res=JSON.parse(text);}
        catch(e){var res=text;}
        return cb(res);
      }else if(x.status===0){
        return er('Error: No internet connection.');
      }return er('Error: ('+x.status+') '+x.statusText+'.');
    }else if(x.readyState!==4){
      /* console.log('state: '+x.readyState+',  status: '+x.status+', text: '+x.statusText+'.'); */
      return false;
    }return er('Error: ('+x.status+') '+x.statusText+'.');
  };
  function http_build_query(o,p){
    var r=[];for(var d in o){
      var k=p?p+'['+encodeURIComponent(d)+']':encodeURIComponent(d);
      if(Array.isArray(o[d])||(typeof o[d]=='object'&&o[d]!==null)){
        r.push(http_build_query(o[d],k));continue;
      }r.push(k+'='+encodeURIComponent(o[d]));
    }return r.join('&');
  }
}


