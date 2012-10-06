/**
 * Generic message handler to process messages received from server.
 *
 * Third party javascript plugins will register themselves here to process
 * custom messages from the server.
 */
var ProcessMessage = function() {
  this.message = null;
  this.handlersServer = new Array();
  this.handlersInput = new Array();
  this.handlersRunOnce = new Array();
  this.handlersOutput = new Array();
}
ProcessMessage.prototype.registerServer = function(name) {
  this.handlersServer.push(name);  
}
ProcessMessage.prototype.registerInput = function(name) {
  this.handlersInput.push(name);  
}
/**
 * Plugins can register to execute setup code to run once after id is obtained.
 */
ProcessMessage.prototype.registerRunOnce = function(name) {
  this.handlersRunOnce.push(name);
}
/**
 * Plugins can register to change or alter the outbound client message.
 */
ProcessMessage.prototype.registerOutput = function(name) {
  this.handlersOutput.push(name);
}
/**
 * Invoke all plugins that have requested access to raw server messages.
 *
 * @return bool
 *   true if a handler claimed ownership of the message, false otherwise.
 */
ProcessMessage.prototype.serverMessage = function(msg_obj) {
  var handled = false;
  for (x in this.handlersServer) {
    handled = Math.max(this.handlersServer[x].serverMessage(msg_obj), handled);
  }
  return handled;
}
/**
 * Execute plugin setup code after client id has been received.
 */
ProcessMessage.prototype.runOnce = function() {
  for (x in this.handlersRunOnce) {
    this.handlersRunOnce[x].runOnce();
  }
}
ProcessMessage.prototype.inputMessage = function(msg_obj) {
  for (x in this.handlersInput) {
    this.handlersInput[x].inputMessage(msg_obj);
  }
}
ProcessMessage.prototype.outputMessage = function(route, payload) {
  for (x in this.handlersOutput) {
    this.handlersOutput[x].outputMessage(route, payload);
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
        alert("Problem retrieving XML data: ("+xmlhttp.status+") (" + xmlhttp.statusText+") Request: ("+url+")");
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

  var handled = PM.serverMessage(message);

  switch(code) {
    case 'NAC':
      // Server has nothing to report.
      handled = true;
      break;
    case 'output':
      pmRmMsg(msg_obj);
      handled = true;
      break;
  }

  if (handled == false) {
    printPlus("text_div", '<span class="cli-failure">'+message+'<br /></span>');
  }

  return;
}

/* Send a message to server. ---------------------------------------- */
function __sm(route, payload) {

  /** Allow plugins to alter or append the outbound message. */
  PM.outputMessage(route, payload);

  if (payload) {
    var url = route+"?payload="+JSON.stringify(payload);
  } else {
    var url = route;
  }
  //printPlus("text_div", '<div class="cli-failure">OUT:'+url+'</div>');

  ajaxFunction(url);

  return;
}

/* Remote client posts message in room. ----------------------------- */
function pmRmMsg (message) {
  printPlus("text_div", '<span class="cln_err">'+message.payload+'<br /></span>');
  return 1;
}


