
                           // TO SERVER. *************************** // FROM SERVER. ******************************
var SRV_VERSION   = 9;     // N/A                                    // version_id
var SRV_FAILURE   = 6;     // N/A                                    // msg
var SRV_IMPROPER  = 16;    // N/A                                    // msg
var SRV_NAC       = 8;     // N/A                                    // N/A
var SRV_WAIT      = 2;     // N/A                                    // N/A

var RM_LOCKED     = 1001;  // room_id, switch, [message]             // source_id, room_id, switch, [message]
var RM_MODERATED  = 1002;  // room_id, switch, [message]             // source_id, room_id, switch, [message]
var RM_ADMIN      = 1003;  // client_id, room_id, switch, [password] // source_id, client_id, room_id, switch
var RM_JOIN       = 1005;  // room_id, [password]                    // client_id, room_id, client_name
var RM_PART       = 1006;  // room_id, [message]                     // client_id, room_id, [message]
var RM_DETAIL     = 1007;  // room_id                                // room_id, title, moderated, locked
var RM_USERS      = 1010;  // room_id                                // CL_DETAIL, [...]
var RM_ALL        = 1008;  // N/A                                    // RM_DETAIL, [...]
var RM_TITLE      = 1009;  // room_id, title                         // source_id, room_id, title
var RM_KICK       = 1012;  // client_id, room_id, [message]          // source_id, target_id, room_id, [message]
var RM_VOICE      = 1013;  // client_id, room_id, switch, [message]  // source_id, target_id, room_id, switch, [message]
var RM_MSG        = 1100;  // room_id, msg                           // source_id, room_id, msg
var RM_MSG_IMAGE  = 1101;  // room_id, msg, [comment]                // source_id, room_id, msg, [comment]
var RM_MSG_FLASH  = 1102;  // room_id, msg, [comment]                // source_id, room_id, msg, [comment]
var RM_MSG_ACTION = 1103;  // room_id, msg                           // source_id, room_id, msg

var CL_NAME       = 2000;  // name                                   // source_id, name
var CL_RETRIEVE   = 2001;  // N/A                                    // N/A
var CL_KILL       = 2004;  // client_id, [message]                   // source_id, target_id, [message]
var CL_DETAIL     = 2005;  // client_id, room_id                     // client_id, room_id, name, voice, admin, image
var CL_IMAGE      = 2006;  // image_url                              // source_id, image_url
var CL_ALL        = 2007;  // N/A                                    // CL_DETAIL, [...]
var CL_MSG        = 2100;  // client_id, msg                         // source_id, msg
var CL_MSG_IMAGE  = 2101;  // client_id, image_url, [message]        // source_id, image_url, [message]
var CL_MSG_FLASH  = 2102;  // client_id, flash_url, [message]        // source_id, flash_url, [message]
var CL_MSG_ACTION = 2103;  // client_id, msg                         // source_id, msg
var CL_ID         = 2200;  // N/A                                    // client_id


/* Ajax for when client is not loaded via a container. -------------- */
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

/* Main callback function to receive messages from server. ---------- */
/* Format received message and send it over for processing. */
function pmRaw (messages) {
  if (my_container) {
    var text = messages['text'];
  } else {
    var text = messages;
  }
  var main = text.split("\n");
  var msg  = '';
  for (y in main) {
    if (msg = main[y].replace("\n", '')) {
      var parts      = msg.split("\t");
      var header     = new Array();
      var message    = new Array();
      var part       = '';
      header['to']   = parts[0];
      header['time'] = parts[1];
      header['code'] = parts[2];
      if (parts[3]) {
        part           = parts[3].replace('%25', '%');
        part           = part.replace('%3b', ';');
        message        = part.split(';');
      }
      pmProcessed(header, message, msg);
    }
  }

  return;
}

/* Process message code. -------------------------------------------- */
function pmProcessed (header, message, raw_message) {

  switch(Number(header['code'])) {
    case SRV_FAILURE:
      printPlus("text_div", '<span class="cln_err">'+message[0]+'<span><br />');
      break;
    case SRV_NAC:
      //printPlus("text_div", '<span class="cln_all">'+"NAC</span><br />");
      break;
    case SRV_VERSION:
      pmSrvVersion(message);
      break;
    case RM_MSG:
      pmRmMsg(message);
      break;
    case RM_MSG_IMAGE:
      pmRmMsgImage(message);
      break;
    case RM_MSG_FLASH:
      pmRmMsgFlash(message);
      break;
    case RM_MSG_ACTION:
      pmRmMsgAction(message);
      break;
    case RM_JOIN:
      pmRmJoin(message);
      break;
    case RM_PART:
      pmRmPart(message);
      break;
    case RM_DETAIL:
      pmRmDetail(message);
      break;
    case RM_ADMIN:
      pmRmAdmin(message);
      break;
    case RM_LOCKED:
      pmRmLocked(message);
      break;
    case RM_KICK:
      pmRmKick(message);
      break;
    case RM_VOICE:
      pmRmVoice(message);
      break;
    case CL_ID:
      pmClId(message);
      break;
    case CL_NAME:
      pmClName(message);
      break;
    case CL_DETAIL:
      pmClDetail(message);
      break;
    default:
      err_msg = "Unrecognized Information From Server : "+raw_message.replace("\n", '')+".<br />";
      printPlus("text_div", '<span class="cln_err">'+err_msg+'<br /></span>');
      //alert(err_msg);
  }
  return;
}

/* Send a message to server. ---------------------------------------- */
function __sm(type) {
  var output  = new Array();
  var element = '';
  var message = '';

  for (x=1;x<arguments.length;x++) {
    if (x != 1) message += ';';
    element = String(arguments[x]);
    element = element.replace('%', '%25');
    element = element.replace(';', '%3b');
    message += element;
  }

  output['time']    = 0;
  output['code']    = type;
  output['from']    = my_client_id;
  output['message'] = message;

  if (my_container) {
    var vals   = gadgets.io.encodeValues(output);
    var url    = my_server_url+"?"+vals;
    var params = {};
    params[gadgets.io.RequestParameters.CONTENT_TYPE]     = gadgets.io.ContentType.TEXT;
    params[gadgets.io.RequestParameters.REFRESH_INTERVAL] = 2;
    gadgets.io.makeRequest(url, pmRaw, params);
  } else {
    var url = my_server_url+"?time="+output['time']+"&code="+output['code']+"&from="+output['from']+"&message="+output['message'];
    ajaxFunction(url);
  }

  return;
}

/* Server reports version. ------------------------------------------ */
function pmSrvVersion (message) {
  printPlus("text_div", '<span class="cln_all">'+"Server Version : "+message[0]+".<span><br />");
  my_server_version = message[0];

  if (my_container) {
    var prefs = new gadgets.Prefs();
    var default_room = prefs.getString("default_room");
  } else {
    default_room = "New";
  }

  __sm(RM_JOIN, default_room);
  return 1;
}

/* Server reports our client's id. ---------------------------------- */
function pmClId (message) {
  my_client_id = message[0];
  printPlus("text_div", '<span class="cln_all">'+"Granted Client ID : "+my_client_id+".<span><br />");
  __sm(SRV_VERSION);
  return 1;
}

/* Client has changed name ------------------------------------------ */
function pmClName (message) {
  client_id = message[0];
  name      = message[1];
  x         = userFind(client_id);
  if (x >= 0) {
    printPlus("text_div", '<span class="msg_name">'+g_user_name[x]+' is Now : '+name+'</span><br />');
    userUpdate (client_id, g_user_room[x], name, g_user_voice[x], g_user_admin[x], g_user_image[x]);
  } else {
    printPlus("text_div", '<span class="cln_err">CL_NAME for '+client_id+' to '+name+'</span><br />');
  }
  return 1;
}

/* Client has changed name ------------------------------------------ */
function pmClDetail (message) {
  var client_id = message[0];
  var room_id   = message[1];
  var name      = message[2];
  var voice     = message[3];
  var admin     = message[4];
  var image     = message[5];
  var x         = userFind(client_id);
  if (x >= 0) {
    userUpdate(client_id, room_id, name, voice, admin, image);
  } else {
    userAdd(client_id, room_id, name, voice, admin, image);
  }
  return 1;
}

/* Room detail update. ---------------------------------------------- */
function pmRmDetail (message) {
  var room_id   = message[0];
  var title     = message[1];
  var moderated = message[2];
  var locked    = message[3];
  var x         = roomFind(room_id);

  if (x >= 0) {
    roomUpdate(room_id, title, moderated, locked);
  } else {
    roomAdd(room_id, title, moderated, locked);
  }
  return 1;
}

/* Client promotes another to admin. -------------------------------- */
function pmRmAdmin (message) {
  var source_id = message[0];
  var client_id = message[1];
  var room_id   = message[2];
  var value     = message[3];
  var x         = userFind(source_id);
  var y         = userFind(client_id);
  if (value == 1) {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' promoted '+g_user_name[y]+' to moderator.</span><br />');
  } else {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' demoted '+g_user_name[y]+'.</span><br />');
  }
  return 1;
}

/* Client grants voice another. ------------------------------------- */
function pmRmVoice (message) {
  var source_id = message[0];
  var client_id = message[1];
  var room_id   = message[2];
  var value     = message[3];
  var x         = userFind(source_id);
  var y         = userFind(client_id);
  if (value == 1) {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' voiced '+g_user_name[y]+'.</span><br />');
  } else {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' muted '+g_user_name[y]+'.</span><br />');
  }
  return 1;
}

/* Client kicks another. -------------------------------------------- */
function pmRmKick (message) {
  var source_id = message[0];
  var client_id = message[1];
  var room_id   = message[2];
  var x         = userFind(source_id);
  var y         = userFind(client_id);
  if (client_id == my_client_id) {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' kicked you out of the chat room!</span><br />');
    userClean();
  } else {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' kicked '+g_user_name[y]+'.</span><br />');
  }
  userRemove(client_id);
  userHtmlList();
  return 1;
}

/* Client has locked a room. ---------------------------------------- */
function pmRmLocked (message) {
  var source_id = message[0];
  var room_id   = message[1];
  var value     = message[2];
  var msg       = message[3];
  var x         = userFind(source_id);
  if (value == 1) {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' has locked the room.</span><br />');
  } else {
    printPlus("text_div", '<span class="msg_act">'+g_user_name[x]+' has opened the room.</span><br />');
  }
  return 1;
}

/* Client joins room. ----------------------------------------------- */
function pmRmJoin (message) {
  var client_id = message[0];
  var room_id   = message[1];
  var name      = message[2];
  if (my_client_id == client_id) {
    printPlus("text_div", '<span class="msg_join">You Have Joined the Chat. Type a greeting in the box below and click send.</span><br />');
  } else {
    printPlus("text_div", '<span class="msg_join">'+name+' Joined Chat.</span><br />');
    playSound('sound/ding.mp3');
  }
  return 1;
}

/* Client parts room. ----------------------------------------------- */
function pmRmPart (message) {
  client_id = message[0];
  if ((x = userFind(message[0])) >= 0) {
    printPlus("text_div", '<span class="msg_part">'+g_user_name[x]+" Parted Chat.</span><br />");
    userRemove(client_id);
    if (client_id == my_client_id) {
      userClean();
      userHtmlList();
      printPlus("text_div", '<span class="cln_all">-------------------------------</span><br />');
    }
    return 1;
  } else {
    printPlus("text_div", '<span class="cln_err">'+client_id+" Parted Chat.</span><br />");
    return 0;
  }
}

/* Remote client posts message in room. ----------------------------- */
function pmRmMsg (message) {
  var client_id = message[0];
  var room_id   = message[1];
  var msg       = message[2];
  var x         = userFind(client_id);

  if (x < 0) {
    printPlus("text_div", '<span class="cln_err">RM_MSG from '+client_id+'</span>');
    return 0;
  }

  g_user_msg[x] = msg;

  printPlus("text_div", '<span class="msg_room"><b>'+g_user_name[x]+'</b>: '+msg+'<span><br />');
  return 1;
}

/* Remote client posts action in room. ----------------------------- */
function pmRmMsgAction (message) {
  var client_id = message[0];
  var room_id   = message[1];
  var comment   = message[2];
  var x         = userFind(client_id);

  if (x < 0) {
    printPlus("text_div", '<span class="cln_err">RM_MSG_ACTION from '+client_id+'</span>');
    return 0;
  }

  printPlus("text_div", '<span class="msg_act">* '+g_user_name[x]+' '+comment+'<span><br />');
  return 1;
}

/* Remote client posts image in room. ------------------------------- */
function pmRmMsgImage (message) {
  var client_id = message[0];
  var room_id   = message[1];
  var url       = message[2];
  var comment   = message[3];
  var x         = userFind(client_id);
  var output    = '';

  if (x < 0) {
    printPlus("text_div", '<span class="cln_err">RM_MSG_IMAGE from '+client_id+'</span>');
    return 0;
  }

  g_user_msg_i[x] = url;

  if ((my_client_id == client_id) || (g_user_admin[x] == 1)) {
    mboxView('media_div');
    print("media_div", '<img src="'+url+'" style="height:100%;width:100%;" />');
    output  = '<span class="media">';
    output += g_user_name[x]+" Loaded: ";
    output += "<a href=\"#\" onclick=\"document.getElementById('input_box').value='/image "+url+"';clientInput();\"><img src=\""+url+"\" alt=\"image\"/></a>";
    output += "</span><br />";
    printPlus("text_div", output);
  } else {
    output  = '<span class="media">';
    output += g_user_name[x]+" Loaded: ";
    output += "<a href=\"#\" onclick=\"document.getElementById('input_box').value='/image "+url+"';clientInput();\"><img src=\""+url+"\" alt=\"image\"/></a></span><br />";
    printPlus("text_div", output);
  }
  return 1;
}

/* Remote client posts flash media in room. ------------------------- */
function pmRmMsgFlash (message) {
  var client_id = message[0];
  var room_id   = message[1];
  var url       = message[2];
  var comment   = message[3];
  var x         = userFind(client_id);

  if (x < 0) {
    printPlus("text_div", '<span class="cln_err">RM_MSG_FLASH from '+client_id+'</span>');
    return 0;
  }

  g_user_msg_v[x] = url;

  if ((my_client_id == client_id) || (g_user_admin[x] == 1)) {
    mboxView('media_div');
    output  = '';
    output += '<object width="100%" height="100%">';
    output += '  <param name="movie" value="'+url+'"></param>';
    output += '  <param name="allowFullScreen" value="true"></param>';
    output += '  <param name="allowscriptaccess" value="always"></param>';
    output += '  <embed src="'+url+'&autoplay=1"';
    output += '   type="application/x-shockwave-flash"';
    output += '   allowscriptaccess="always"';
    output += '   allowfullscreen="true"';
    output += '   autoplay="true"';
    output += '   width="100%"';
    output += '   height="100%">';
    output += '  </embed>';
    output += '</object>';
    print("media_div", output);
  } else {
    output  = '<span class="media">';
    output += g_user_name[x]+" Loaded: ";
    output += "<a href=\"#\" onclick=\"document.getElementById('input_box').value='/flash "+url+"';clientInput();\">Video ("+comment+")</a></span><br />";
    printPlus("text_div", output);
  }
  return 1;
}
