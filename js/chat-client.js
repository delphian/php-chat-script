
var my_client_id      = 0;
var my_room_id        = 0;
var my_client_version = '0.9.42a';
var my_server_version = '';
var my_int_id         = 0;
// TODO only use 'server.php' unless we are in a container.
var my_server_url     = 'server.php';
var my_container      = false;
var my_font_size      = 1.0;
var my_sound_msg      = 'sound/wine-glass-01.wav';

function getViewName() {
  return gadgets.views.getCurrentView().getName();
}

function playSound (source) {
  soundEmbed = document.getElementById('sound_emb');
  soundEmbed.src = source;
  soundEmbed.Play();

  return 1;
}

/* Text has been entered in the input box. */
function clientInput() {
  message = document.getElementById('input_box').value;

  msg = message.split(' ');

  if (msg[0] == '/nick') {
    __sm(CL_NAME, msg[1]);
  } else if (msg[0] == '/join') {
    __sm(RM_JOIN, msg[1], msg[2]);
  } else if (msg[0] == '/image') {
    __sm(RM_MSG_IMAGE, my_room_id, msg[1], msg[2]);
  } else if (msg[0] == '/youtube') {
    clientInputYoutube(msg[1], msg[2]);
  } else if (msg[0] == '/hulu') {
    __sm(RM_MSG_FLASH, my_room_id, msg[1], msg[2]);
  } else if (msg[0] == '/flash') {
    __sm(RM_MSG_FLASH, my_room_id, msg[1], msg[2]);
  } else if (msg[0] == '/lock') {
    __sm(RM_LOCKED, my_room_id, msg[1], msg[2]);
  } else if (msg[0] == '/moderate') {
    __sm(RM_MODERATED, my_room_id, msg[1], msg[2]);
  } else if (msg[0] == '/kick') {
    __sm(RM_KICK, msg[1], my_room_id, msg[2]);
  } else if (msg[0] == '/voice') {
    __sm(RM_VOICE, msg[1], my_room_id, msg[2], msg[3]);
  } else if (msg[0] == '/admin') {
    __sm(RM_ADMIN, msg[1], my_room_id, msg[2], msg[3]);
  } else if (msg[0] == '/me') {
    msg = msg.slice(1);
    msg = msg.join(' ');
    __sm(RM_MSG_ACTION, my_room_id, msg);
  } else {
    __sm(RM_MSG, my_room_id, message);
  }

  document.getElementById('input_box').value = '';
  document.getElementById('input_box').focus();

  return;
}

function mboxView(element) {
  document.getElementById('media_div').style.display = 'none';
  document.getElementById('ulist_div').style.display = 'none';
  document.getElementById('rlist_div').style.display = 'none';
  document.getElementById('user_div').style.display  = 'none';

  document.getElementById(element).style.display = 'inline';
}

function clientInputKeystroke(e) {
  var keycode;
  if (window.event) keycode = window.event.keyCode;
  else if (e) keycode = e.which;
  else return true;

  if (keycode == 13) {
    clientInput();
    return false;
  } else {
    return true;
  }
}

function clientInputFontUp() {
  my_font_size = my_font_size + 0.1;
  size = my_font_size + 'em';
  document.body.style.fontSize = size;
  return;
}

function clientInputFontDown() {
  my_font_size = my_font_size - 0.1;
  size = my_font_size + 'em';
  document.body.style.fontSize = size;
  return;
}

function clientInputSetCancel() {
  document.getElementById('set_div').style.display = 'none';
  document.getElementById('encap_div').style.opacity = 1.0;
  document.getElementById('encap_div').style.filter = 'alpha(opacity=100)';
  return;
}

function clientInputSetOK() {
  var set_name  = document.getElementById('set_type').value;
  var set_value = document.getElementById('set_input').value;
  if (set_name == 'youtube') {
    clientInputYoutube(set_value, 'Youtube');
  } else if (set_name == 'name') {
    __sm(CL_NAME, set_value);
  } else if (set_name == 'image') {
    if (!set_value.length) set_value = 'http://www.randomsmiley.com/random.php?'+Math.floor(Math.random()*1001);
    __sm(RM_MSG_IMAGE, my_room_id, set_value);
  } else {
    printPlus('text_div', '<span class="cln_all">Bad Name</span>');
  }
  clientInputSetCancel();
  return;
}

function clientInputSetup(parm) {
  mboxView('ulist_div');
  document.getElementById('set_type').value = parm;
  if (parm == 'name') {
    print('setl_div', "Enter your new name :");
  } else if (parm == 'youtube') {
    print('setl_div', "Enter the URL for the youtube video :");
  } else if (parm == 'image') {
    print('setl_div', "Enter the URL for the image :");
  } else {
    print('setl_div', "HOW DID YOU DO THAT???");
  }
  document.getElementById('encap_div').style.filter = 'alpha(opacity=50)';
  document.getElementById('encap_div').style.opacity = 0.5;
  document.getElementById('set_div').style.display = 'inline';
  return;
}

function clientInputImage(url, comment) {
  __sm(RM_MSG_IMAGE, my_room_id, url, comment);
  return;
}

function clientInputYoutube(url, comment) {
  elements = url.split("?v=");
  code     = elements[1];
  new_url  = 'http://www.youtube.com/v/'+code+'&h1=en&fs=1&';
  __sm(RM_MSG_FLASH, my_room_id, new_url, comment);
  return;
}

function initChat() {
  if (my_container) {
    var prefs = new gadgets.Prefs();
    my_font_size = prefs.getString("default_size");
    var size = my_font_size+"em";
    document.body.style.fontSize = size;
  }
  print("text_div", '<span class="cln_all">'+"Client Version : "+my_client_version+".</span><br />");
  __sm(CL_ID);
  document.getElementById('input_box').focus();
  my_int_id = setInterval("__sm("+CL_RETRIEVE+")", 5000);

  return;
}

//if (getViewName() == 'canvas') {
//  html_output = '<link href="http://www.piticalculator.com/chat-test/client.css" rel="stylesheet" type="text/css">';
//} else {
//  html_output = '<link href="http://www.piticalculator.com/chat-test/client.css" rel="stylesheet" type="text/css">';
//}
//document.write(html_output);

if (typeof(gadgets) != 'undefined')
  my_container = true;

if (my_container) {
  //gadgets.util.registerOnLoadHandler(initChat());
  window.onload = initChat;
} else {
  window.onload = initChat;
}
