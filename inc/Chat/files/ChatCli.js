var ChatCli = function() {
  this.message = null;
};

/**
 * Run once commands after the client receives it's identification.
 */
ChatCli.prototype.runOnce = function() {
  var payload = {type:"join",message:my_client_id};
  __sm('chat/join', payload);
}

/**
 * Process messages received from the server.
 */
ChatCli.prototype.serverMessage = function(message) {
  var handled = false;
  try {
    var msg_obj = eval("(" + message + ")");
    if (msg_obj.hasOwnProperty("code")) {
      var code = msg_obj.code;
    }
  } catch(err) {
    var code = 0;
  }

  if (code == 'chat') {
    for (x in msg_obj.payload) {
      user_id = msg_obj.payload[x].from_user_id;
      from = msg_obj.payload[x].from_user_name;
      msg  = msg_obj.payload[x].chat.message;
      if (msg_obj.payload[x].chat.type == 'say') {
        printPlus("text_div", '<span class="cli-normal"><b>'+from+'</b>: '+msg+'</span><br />');
      } else if (msg_obj.payload[x].chat.type == 'emote') {
        printPlus("text_div", '<span class="cli-normal">* '+from+' '+msg+'</span><br />');
      } else if (msg_obj.payload[x].chat.type == 'image') {
        printPlus("text_div", '<img src="'+msg+'" style="width:100%;height:100%;" /><br />');
      } else {
        printPlus("text_div", '<div class="cli-warning">Received unknown chat message:'+message+'</div>');
      }
    }
    handled = true;
  }

  return handled;
};

/**
 * Process commands entered on the command line.
 */
ChatCli.prototype.inputMessage = function(message) {
  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  if (command == '/me') {
    var payload = {payload:{type:"emote",message:remainder}};
    __sm('chat/set_chat', payload);
  } else if (command == '/image') {
    var payload = {payload:{type:"image",message:remainder}};
    __sm('chat/set_chat', payload);
  } else if (command.substring(0, 1) != "/") {
    var payload = {payload:{type:"say",message:message}};
    __sm('chat/set_chat', payload);
  }

  return;
}

chatCli = new ChatCli();
PM.registerServer(chatCli);
PM.registerInput(chatCli);
PM.registerRunOnce(chatCli);

