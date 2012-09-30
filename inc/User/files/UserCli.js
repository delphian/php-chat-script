var UserApiCli = function() {
  this.users = new Array();
};

/**
 * Run once commands after the client receives it's identification.
 */
UserApiCli.prototype.runOnce = function() {
  var payload = {};
  /** Request a list of all current users. */
  __sm('api/user/list/id', payload);
}

/**
 * Process messages received from the server.
 */
UserApiCli.prototype.serverMessage = function(message) {
  var handled = false;
  try {
    var msg_obj = eval("(" + message + ")");
    if (msg_obj.hasOwnProperty("UserApi")) {
      var sup = msg_obj.UserApi;
    }
  } catch(err) {
    var sup = 0;
  }

  if (sup) {
    for (x in sup) {
      type = sup[x].type;
      if (type == 'api_list_ids') {
        for (y in sup[x].ids) {
          printPlus('text_div', '<div class="cli-info">UserApi:'+sup[x].ids[y]+'</div>');
        }
      } else if (type == 'api_list_id') {
        for (y in sup[x].user) {
          printPlus('text_div', '<div class="cli-info">UserApi:'+y+':'+sup[x].user[y]+'</div>');
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
UserApiCli.prototype.inputMessage = function(message) {
  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  if (command == '/user') {
    var subcommand = remainder.split(' ')[0];
    if (subcommand == 'register') {
      var email   = remainder.split(' ')[1];
      var pass    = remainder.split(' ')[2];
      var payload = {api:{user:{register:{email:email,password:pass}}}}
      __sm('api/user/register', payload);
    }
    else if (subcommand = 'login') {
      var email   = remainder.split(' ')[1];
      var pass    = remainder.split(' ')[2];
      var payload = {api:{user:{login:{email:email,password:pass}}}}
      __sm('api/user/login', payload);
    }
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
  }

  return;
}

userApiCli = new UserApiCli();
PM.registerServer(userApiCli);
PM.registerInput(userApiCli);
PM.registerRunOnce(userApiCli);

