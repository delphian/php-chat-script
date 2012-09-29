var SimpleUserCli = function() {
  this.users = new Array();
};

/**
 * Run once commands after the client receives it's identification.
 */
SimpleUserCli.prototype.runOnce = function() {
  var payload = {};
  __sm('api/simpleuserplugin/list/id', payload);
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

  if (sup) {
    for (x in sup) {
      type = sup[x].type;
      if (type == 'api_list_ids') {
        for (y in sup[x].ids) {
          printPlus('text_div', '<div class="cli-info">SimpleUser:'+sup[x].ids[y]+'</div>');
        }
      } else if (type == 'api_list_id') {
        for (y in sup[x].user) {
          printPlus('text_div', '<div class="cli-info">SimpleUser:'+y+':'+sup[x].user[y]+'</div>');
        }
      } else if (type == 'api_request_id') {
        my_client_id = sup[x].user.user_id;
        my_secret_key = sup[x].user.secret_key;
        printPlus("text_div", '<span class="cli-info">'+"Client identification : "+my_client_id+".</span><br />");
        PM.runOnce();        
      } else {
        printPlus("text_div", '<div class="cli-warning">Received unknown SimpleUser message:'+message+'</div>');
      }
    }
    handled = true;
  }

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

