var UserApiCli = function() {
  this.users = new Array();
  this.id = null;
  this.password = null;
};

/**
 * Run once commands after the client receives it's identification.
 */
UserApiCli.prototype.runOnce = function() {
  // Request unique client identification. Wait for this syncronous call to
  // finish, running the result through the message processor right away.
  pmRaw(__sm('api/user/request'));  
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
      } else if (type == 'api_request') {
        this.id = sup[x].user.user_id;
        this.password = sup[x].user.password;
        printPlus("text_div", '<span class="cli-info">'+"Client identification : "+this.id+".</span><br />");
        // Request a list of all online users.
        __sm('api/user/list/online');
      } else {
        printPlus("text_div", '<div class="cli-warning">Received unknown User message:'+message+'</div>');
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
      var payload = {api:{user:{register:{email:email,password:pass}}}};
      __sm('api/user/register', payload);
    }
    else if (subcommand == 'login') {
      var email   = remainder.split(' ')[1];
      var pass    = remainder.split(' ')[2];
      var payload = {api:{user:{login:{email:email,password:pass}}}};
      __sm('api/user/login', payload);
    }
    else if (subcommand == 'list') {
      var type    = remainder.split(' ')[1];
      var payload = {};
      __sm('api/user/list/'+type, payload)
    }
    else if (subcommand == 'update') {
      var id = remainder.split(' ')[1];
      var remainder = message.split(' ').slice(3).join(' ');
      var key = remainder.split('=')[0];
      var value = remainder.split('=')[1];
      var pairs = {};
      pairs[key] = value;
      var payload = {api:{user:{update:pairs}}};
      __sm('api/user/update/'+id, payload);
    }
  }

  return;
}

/**
 * Alter or append the message from the client to the server.
 */
UserApiCli.prototype.outputMessage = function(route, payload) {
  /** Insert our client credentials. */
  if (this.id) {
    if (typeof payload == 'undefined') {
      payload = {};
    }
    if (typeof payload.api == 'undefined') {
      payload.api = {};
    }
    if (typeof payload.api.user == 'undefined') {
      payload.api.user = {};
    }
    if (typeof payload.api.user.auth == 'undefined') {
      payload.api.user.auth = {};
    }
    payload.api.user.auth.user_id = this.id;
    payload.api.user.auth.password = this.password;
  }
  return payload;
}

userApiCli = new UserApiCli();
PM.registerServer(userApiCli);
PM.registerInput(userApiCli);
PM.registerRunOnce(userApiCli);
PM.registerOutput(userApiCli);

