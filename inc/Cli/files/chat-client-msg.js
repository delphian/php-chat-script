/**
 * Generic message handler to process messages received from server.
 *
 * Third party javascript plugins will register themselves here to process
 * custom messages from the server.
 */
var ProcessMessage = function() {
  this.message = null;
  this.handlers = new Array();
}
ProcessMessage.prototype.register = function(name) {
  this.handlers.push(name);  
}
ProcessMessage.prototype.serverMessage = function(msg_obj) {
  for (x in this.handlers) {
    //alert(handler);
    this.handlers[x].serverMessage(msg_obj);
  }
}

var PM = new ProcessMessage();


/**
 *  Make a request to the server and send the response to a callback function.
 */
function ajaxFunction(url)
{
  var xmlhttp;
  if (window.XMLHttpRequest) {
    xmlhttp=new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  } else {
    alert("Your browser does not support XMLHTTP!");
  }

  xmlhttp.onreadystatechange=function() {
    if(xmlhttp.readyState==4) {
      if (xmlhttp.status==200) {
        // Send all valid responses to pmRaw().
        pmRaw(xmlhttp.responseText);
      } else {
        clearInterval(my_int_id);
        alert("Problem retrieving XML data: ("+xmlhttp.status+") (" + xmlhttp.statusText+")");
      }
    }
  }
  xmlhttp.open("GET",url,true);
  xmlhttp.send(null);
}

/**
 * Ajax Callback. Process a message from the server.
 *
 * Assume that all messages are JSON encoded.
 *
 * @param string messages
 *   JSON encoded messages each seperated by line feeds.
 *
 * @return TRUE
 */
function pmRaw (messages) {
  var main = String(messages).split("\n");
  if (main instanceof Array) {
    for (y in main) {
      if (msg = main[y].replace("\n", '')) {
        // Process each line individually.
        pmProcessed(msg);
      }
    }
  } else {
    pmProcessed(messages);
  }

  return true;
}

/* Process message code. -------------------------------------------- */
function pmProcessed (message) {
  try {
    var msg_obj = eval("(" + message + ")");
    if (msg_obj.hasOwnProperty("code")) {
      var code = msg_obj.code;
    }
  } catch(err) {
    var code = 0;
  }

  PM.serverMessage(message);

  switch(code) {
    case 'NAC':
      // Server has nothing to report.
      break;
    case 'user_id':
      my_client_id = msg_obj.payload.user_id;
      my_secret_key = msg_obj.payload.secret_key;
      printPlus("text_div", '<span class="cln_all">'+"Client identification : "+my_client_id+".</span><br />");
      break;
    case 'output':
      pmRmMsg(msg_obj);
      break;
    default:
      printPlus("text_div", '<span class="cln_err">'+message+'<br /></span>');
  }
  return;
}

/* Send a message to server. ---------------------------------------- */
function __sm(route, payload) {

  /** Insert our client credentials. */
  if (my_client_id) {
    if (typeof payload == 'undefined') {
      payload = {};
    }
    payload.user = {};
    payload.user.user_id = my_client_id;
    payload.user.secret_key = my_secret_key;
  }

  if (payload) {
    var url = route+"?payload="+JSON.stringify(payload);
  } else {
    var url = route;
  }
  ajaxFunction(url);

  return;
}

/* Remote client posts message in room. ----------------------------- */
function pmRmMsg (message) {
  printPlus("text_div", '<span class="cln_err">'+message.payload+'<br /></span>');
  return 1;
}


