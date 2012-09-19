var SimplePageCli = function() {
  this.message = null;
};

/**
 * Process messages received from the server.
 */
SimplePageCli.prototype.serverMessage = function(message) {
  try {
    var msg_obj = eval("(" + message + ")");
    if (msg_obj.hasOwnProperty("code")) {
      var code = msg_obj.code;
    }
  } catch(err) {
    var code = 0;
  }

  if (code == 'simplepage') {
    if (msg_obj.payload.code == 'page_new') {
      if (msg_obj.payload.result == true) {
        printPlus('text_div', '<span>Page created.</span>');
      } else {
        printPlus('text_div', '<span>Page not created.</span>');
      }
    } else {
      printPlus('text_div', '<span>Unknown SimplePage message received.</span>');
    }
  }

  return;
};

/**
 * Process commands entered on the command line.
 */
SimplePageCli.prototype.inputMessage = function(message) {
  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  /** Examine raw message. */
  if (message == '/help simplepage') {
    var payload = {};
    __sm('simplepage/help', payload);
  }

  /** Only examine simplepage commands. */
  if (command == '/simplepage') {
    var sub_command = remainder.split(' ')[0];
    var remainder   = remainder.split(' ').slice(1).join(' ');

    if (sub_command == 'new') {
      var payload = {payload:{simpletest:{code:"new",path:remainder}}};
      __sm('simplepage/page/new', payload);
    }
    if (sub_command == 'list') {
      var payload = {};
      __sm('simplepage/page/list', payload);
    }
  }

  return;
}

simplePageCli = new SimplePageCli();
PM.registerServer(simplePageCli);
PM.registerInput(simplePageCli);

