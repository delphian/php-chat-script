
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
  PM.inputMessage(message);

  var command   = message.split(' ')[0];
  var remainder = message.split(' ').slice(1).join(' ');

  if (command == '/route') {
    var route = message.split(' ')[1];
    remainder = message.split(' ').slice(2).join(' ');
    payload   = {};
    __sm(route, payload);
  } else if (message == '/help') {
    __sm('__cli/command/help');
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
  printPlus("text_div", '<span class="cln_all">Try <b>/help</b> if you get lost.</span><br />');
  document.getElementById('input_box').focus();

  // Allow each javascript plugin to do something during startup.
  PM.runOnce();

  // Call the send message function at interval to poll the server for updates.
  my_int_id = setInterval("__sm('cli/get_message')", 5000);

  return;
}

/**
 * Function : dump()
 * Arguments: The data - array,hash(associative array),object
 *    The level - OPTIONAL
 * Returns  : The textual representation of the array.
 * This function was inspired by the print_r function of PHP.
 * This will accept some data as the argument and return a
 * text that will be a more readable version of the
 * array/hash/object that is given.
 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
 */
function dump(arr,level) {
    var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];
			
			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

// Run initChat() once the script is done loading.
window.onload = initChat;
