
var my_client_id      = 0;
var my_secret_key     = 0;
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

  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  if (command == '/route') {
    var route = message.split(' ')[1];
    remainder = message.split(' ').slice(2).join(' ');
    __sm(route, payload);
  } else if (command.substring(0, 1) == "/") {
    var payload = {code:command.substring(1),payload:remainder};
    __sm('cli/set_message', payload);
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

  /** Request unique client identification. */
  pmRaw(__sm('cli/get_id'));

  // Call the send message function at interval to poll the server for updates.
  my_int_id = setInterval("__sm('cli/get_message')", 5000);

  return;
}

// Run initChat() once the script is done loading.
window.onload = initChat;
