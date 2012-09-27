var SimpleUserCli = function() {
  this.users = new Array();
};

/**
 * Run once commands after the client receives it's identification.
 */
SimpleUserCli.prototype.runOnce = function() {
  var payload = {};
  __sm('api/simpleuserplugin/list/ids', payload);
}

/**
 * Process messages received from the server.
 */
SimpleUserCli.prototype.serverMessage = function(message) {
  var handled = false;
  try {
    var msg_obj = eval("(" + message + ")");
    if (msg_obj.hasOwnProperty("SimpleUserPlugin")) {
      var sup = msg_obj.SimpleUserPlugin;
    }
  } catch(err) {
    var sup = 0;
  }

//  if (sup) {
//    for (x in msg_obj.payload) {
//      user_id = msg_obj.payload[x].from_user_id;
//      from = msg_obj.payload[x].from_user_name;
//      msg  = msg_obj.payload[x].chat.message;
//      if (msg_obj.payload[x].chat.type == 'say') {
//      } else if (msg_obj.payload[x].chat.type == 'nick') {
//        printPlus('text_div', '<div class="cli-normal">'+this.clients[user_id].name+' is now known as '+msg+'</div>');
//        this.clients[user_id] = {name:msg};
//      } else {
//        printPlus("text_div", '<div class="cli-warning">Received unknown chat message:'+message+'</div>');
//      }
//    }
//    handled = true;
//  }

  return handled;
};

/**
 * Process commands entered on the command line.
 */
SimpleUserCli.prototype.inputMessage = function(message) {
  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

//  if (command == '/me') {
//    var payload = {payload:{type:"emote",message:remainder}};
//    __sm('chat/set_chat', payload);
//  } else if (command == '/nick') {
//    var payload = {payload:{type:"nick",message:remainder}};
//    __sm('chat/nick', payload);
//  } else if (command == '/image') {
//    var payload = {payload:{type:"image",message:remainder}};
//    __sm('chat/set_chat', payload);
//  } else if (command.substring(0, 1) != "/") {
//    var payload = {payload:{type:"say",message:message}};
//    __sm('chat/set_chat', payload);
//  }

  return;
}

simpleUserCli = new SimpleUserCli();
PM.registerServer(simpleUserCli);
PM.registerInput(simpleUserCli);
PM.registerRunOnce(simpleUserCli);

