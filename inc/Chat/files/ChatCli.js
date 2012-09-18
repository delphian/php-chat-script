var ChatCli = function() {
  this.message = null;
};

/**
 * Process messages received from the server.
 */
ChatCli.prototype.serverMessage = function(message) {
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
      from = msg_obj.payload[x].from_user_id;
      msg  = msg_obj.payload[x].chat.message;
      if (msg_obj.payload[x].chat.type == 'say') {
        printPlus("text_div", '<span class="cln_all">'+from+'&gt; '+msg+'</span><br />');
      }
      if (msg_obj.payload[x].chat.type == 'emote') {
        printPlus("text_div", '<span class="cln_all">* '+from+' '+msg+'</span><br />');
      }
    }
  }

  return;
};

/**
 * Process commands entered on the command line.
 */
ChatCli.prototype.inputMessage = function(message) {
  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  if (command == '/say') {
    var payload = {payload:{type:"say",message:remainder}};
    __sm('chat/set_chat', payload);
  } else if (command == '/me') {
    var payload = {payload:{type:"emote",message:remainder}};
    __sm('chat/set_chat', payload);
  }

  return;
}

chatCli = new ChatCli();
PM.registerServer(chatCli);
PM.registerInput(chatCli);

