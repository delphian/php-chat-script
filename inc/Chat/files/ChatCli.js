var ChatCli = function() {
  this.message = null;
  this.clients = new Array();
};

/**
 * Run once commands after the client receives it's identification.
 */
ChatCli.prototype.runOnce = function() {

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
    if (msg_obj.hasOwnProperty("UserApi")) {
      var userApi = msg_obj.UserApi;
    }
  } catch(err) {
    var code = 0;
  }

  if (userApi) {
    for (x in userApi) {
      type = userApi[x].type;
      if (type == 'api_request') {
        var payload = {type:"join",message:userApiCli.id};
        __sm('chat/join', payload);
      }
    }
  }

  if (code == 'chat') {
    for (x in msg_obj.payload) {
      user_id = msg_obj.payload[x].from_user_id;
      from = msg_obj.payload[x].from_user_name;
      msg  = msg_obj.payload[x].chat.message;
      if (msg_obj.payload[x].chat.type == 'say') {
        printPlus("text_div", '<span class="cli-normal"><b>'+this.clients[user_id].name+'</b>: '+msg+'</span><br />');
      } else if (msg_obj.payload[x].chat.type == 'emote') {
        printPlus("text_div", '<span class="cli-normal">* '+this.clients[user_id].name+' '+msg+'</span><br />');
      } else if (msg_obj.payload[x].chat.type == 'image') {
        //printPlus("text_div", '<img src="'+msg+'" style="width:100%;height:100%;" /><br />');
        this.showImage(msg);
      } else if (msg_obj.payload[x].chat.type == 'join') {
        this.clients[user_id] = {name:msg};
        printPlus('text_div', '<div class="cli-normal">'+msg+' Has joined</div>');
      } else if (msg_obj.payload[x].chat.type == 'nick') {
        printPlus('text_div', '<div class="cli-normal">'+this.clients[user_id].name+' is now known as '+msg+'</div>');
        this.clients[user_id] = {name:msg};
      } else {
        printPlus("text_div", '<div class="cli-warning">Received unknown chat message:'+message+'</div>');
      }
    }
    handled = true;
  }

  return handled;
};

ChatCli.prototype.showImage = function(image) {
  if (document.getElementById("cli_image") == null) {
    var img = new Image();
    img.id = "cli_image";
    //img.style.border = "0.25em solid #333333";
    img.style.position = "absolute";
    img.style.right = "0.5em";
    img.style.top = "1.5em";
    img.style.width = "25%";
    img.style.display = "block";
    img.style.opacity = "0";
    img.style.background = "-webkit-linear-gradient(top, rgba(255,255,255,0), rgba(255,255,255, 1))";
    document.getElementById("encap_div").appendChild(img);
  }
  document.getElementById("cli_image").src = image;
  fadeIn('cli_image', 0);
}

/**
 * Process commands entered on the command line.
 */
ChatCli.prototype.inputMessage = function(message) {
  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  if (command == '/me') {
    var payload = {payload:{type:"emote",message:remainder}};
    __sm('chat/set_chat', payload);
  } else if (command == '/nick') {
    var payload = {payload:{type:"nick",message:remainder}};
    __sm('chat/nick', payload);
  } else if (command == '/image') {
    var payload = {payload:{type:"image",message:remainder}};
    __sm('chat/set_chat', payload);
  } else if (command.substring(0, 1) != "/") {
    var payload = {payload:{type:"say",message:message}};
    __sm('chat/set_chat', payload);
  }

  return;
}

function fadeIn(objId, opacity) {
  obj = document.getElementById(objId);
  if (opacity <= 100) {
    setOpacity(obj, opacity);
    opacity += 10;
    window.setTimeout("fadeIn('"+objId+"',"+opacity+")", 100);
  }
}

function setOpacity(obj, opacity) {
  opacity = (opacity == 100)?99.999:opacity;
  // IE/Win
  obj.style.filter = "alpha(opacity:"+opacity+")";
  // Safari<1.2, Konqueror
  obj.style.KHTMLOpacity = opacity/100;
  // Older Mozilla and Firefox
  obj.style.MozOpacity = opacity/100;
  // Safari 1.2, newer Firefox and Mozilla, CSS3
  obj.style.opacity = opacity/100;
}

chatCli = new ChatCli();
PM.registerServer(chatCli);
PM.registerInput(chatCli);
PM.registerRunOnce(chatCli);

