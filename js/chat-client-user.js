
var g_user_client = new Array();
var g_user_name   = new Array();
var g_user_time   = new Array();
var g_user_voice  = new Array();
var g_user_admin  = new Array();
var g_user_image  = new Array();
var g_user_room   = new Array();
var g_user_msg_i  = new Array();
var g_user_msg_v  = new Array();
var g_user_msg    = new Array();

/* Updates the client user list. -------------------------------- */
function userHtmlList() {
  var output  = '';
  var prepend = '';
  for (x in g_user_client) {
    if (g_user_admin[x] == 1) prepend += '@';
    if (g_user_voice[x]  == 1) prepend += '+';
    output += '<a href="#" onclick="userHtmlUser('+g_user_client[x]+");mboxView('user_div');\">"+prepend+g_user_name[x]+'</a><br />';
    prepend = '';
  }
  print("ulist_div", output);

  return;
}

/* Updates the client user box. -------------------------------- */
function userHtmlUser(client_id) {
  var output = '';
  x = userFind(client_id);

  output += '<table>';
  output += '<tbody>';
  output += '<tr><td style="width:50%;">Client ID</td><td style="width:50%;">'+client_id+'</td></tr>';
  output += '<tr><td>Name</td><td>'+g_user_name[x]+'</td></tr>';
  output += '<tr><td>Voice</td><td>'+g_user_voice[x]+'</td></tr>';
  output += '<tr><td>Admin</td><td>'+g_user_admin[x]+'</td></tr>';
  output += '<tr><td>Last Image</td><td>'+g_user_msg_i[x]+'</td></tr>';
  output += '<tr><td>Last Video</td><td>'+g_user_msg_v[x]+'</td></tr>';
  output += '<tr><td>Last Post</td><td>'+g_user_msg[x]+'</td></tr>';
  output += '</tbody>';
  output += '</table>';

  print ("user_div", output);

  return;
}

/* Reports the user index based on a client_id. --------------------- */
function userFind(client_id) {
  for (x in g_user_client) {
    if (g_user_client[x] == client_id) return x;
  }

  return -1;
}

/* Add a user. ------------------------------------------------------ */
function userAdd (client_id, room_id, name, voice, admin, image) {
  x = g_user_client.push(client_id);
  g_user_room.push(room_id);
  g_user_name.push(name);
  g_user_voice.push(voice);
  g_user_admin.push(admin);
  g_user_image.push(image);
  g_user_msg.push();
  g_user_msg_i.push();
  g_user_msg_v.push();

  if ((client_id == my_client_id) && (my_room_id != room_id)) {
    my_room_id = room_id;
  }

  if (client_id == my_client_id)
    roomHtmlLine();

  userHtmlList();
  return x;
}

/* Remove all users. ------------------------------------------------ */
function userClean () {
  g_user_client = new Array();
  g_user_name   = new Array();
  g_user_voice  = new Array();
  g_user_admin  = new Array();
  g_user_image  = new Array();
  g_user_room   = new Array();
  g_user_msg    = new Array();
  g_user_msg_i  = new Array();
  g_user_msg_v  = new Array();
  return 1;
}

/* Remove a user. --------------------------------------------------- */
function userRemove (client_id) {
  x = userFind(client_id);
  if (x >= 0) {
    g_user_client.splice(x, 1);
    g_user_room.splice(x, 1);
    g_user_name.splice(x, 1);
    g_user_voice.splice(x, 1);
    g_user_admin.splice(x, 1);
    g_user_image.splice(x, 1);
    g_user_msg.splice(x, 1);
    g_user_msg_i.splice(x, 1);
    g_user_msg_v.splice(x, 1);
  } else {
    printPlus("text_div", '<span class="cln_err">userRemove() Client Not Found: '+client_id+".<span><br />");
    return 0;
  }
  userHtmlList();
  return 1;
}

/* Update a user record. -------------------------------------------- */
function userUpdate (client_id, room_id, name, voice, admin, image) {
  if ((x = userFind(client_id)) < 0) return 0;

  g_user_name[x]  = name;
  g_user_voice[x] = voice;
  g_user_admin[x] = admin;
  g_user_image[x] = image;
  g_user_room[x]  = room_id;
  userHtmlList();

  if (client_id == my_client_id)
    my_room_id = room_id;

  if (client_id == my_client_id)
    roomHtmlLine();

  return 1;
}
