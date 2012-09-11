
var my_client_id      = 0;
var my_client_version = '0.9.42a';
var my_server_version = '';
var my_int_id         = 0;
var my_server_url     = 'server.php';
var my_font_size      = 1.0;

/**
 * Callback for when text has been entered into the input box.
 * 
 * Forward message to server via ajax.
 */
function clientInput() {
  message = document.getElementById('input_box').value;

  msg = message.split(' ');

  if (msg[0] == '/route') {
    var route = msg[1];
    msg = msg.slice(2);
    msg = msg.join(' ');
    __sm(route, msg);
  } else {
    // Do what by default?
  }

  document.getElementById('input_box').value = '';
  document.getElementById('input_box').focus();

  return;
}

/**
 * Track keystrokes entered in the input box. Send message when carriage
 * return is pressed.
 */
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

/**
 * Increase the font size of the entire display.
 */
function clientInputFontUp() {
  my_font_size = my_font_size + 0.1;
  size = my_font_size + 'em';
  document.body.style.fontSize = size;
  return;
}

/**
 * Decrease the font size of the entire display.
 */
function clientInputFontDown() {
  my_font_size = my_font_size - 0.1;
  size = my_font_size + 'em';
  document.body.style.fontSize = size;
  return;
}

/**
 * Setup the script to run. This should be called once after script has finished
 * loading.
 */
function initChat() {
  print("text_div", '<span class="cln_all">'+"Client Version : "+my_client_version+".</span><br />");
  document.getElementById('input_box').focus();
  
  // Call the send message function at interval to poll the server for updates.
  my_int_id = setInterval("__sm('admin/get_message')", 5000);

  return;
}

// Run initChat() once the script is done loading.
window.onload = initChat;
