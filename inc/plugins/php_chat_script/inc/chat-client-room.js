
var g_room_id        = new Array();
var g_room_locked    = new Array();
var g_room_moderated = new Array();
var g_room_title     = new Array();


/* Reports the room index based on the name. ------------------------ */
function roomFind (id) {
  for (x in g_room_id) {
    if (g_room_id[x] == id) return x;
  }

  return -1;
}

/* Add a room ------------------------------------------------------- */
function roomAdd (room_id, title, moderated, locked) {
  if ((x = roomFind(room_id)) >= 0) {
    return x;
  } else {
    x = g_room_id.push   (room_id);
    g_room_title.push    (title);
    g_room_moderated.push(moderated);
    g_room_locked.push   (locked);
    roomHtmlList();

    if (room_id == my_room_id)
      roomHtmlLine();
    return x;
  }
}

/* Remove a room. --------------------------------------------------- */
function roomRemove (id) {
  x = roomFind(id);
  if (x >= 0) {
    g_room_id.splice(x, 1);
    g_room_locked.splice(x, 1);
    g_room_moderated.splice(x, 1);
    g_room_title.splice(x, 1);
  } else {
    printPlus("text_div", '<span class="cln_err">roomRemove() Room Not Found: '+id+".<span><br />");
    return 0;
  }
  roomHtmlList();
  return 1;
}

/* Update a room record. -------------------------------------------- */
function roomUpdate (room_id, title, moderated, locked) {
  if ((x = roomFind(room_id)) < 0) return 0;

  g_room_title[x]     = title;
  g_room_moderated[x] = moderated;
  g_room_locked[x]    = locked;
  roomHtmlList();

  if (room_id == my_room_id)
    roomHtmlLine();

  return 1;
}

function roomHtmlLine() {
  var x = userFind(my_client_id);
  var y = roomFind(my_room_id);
  var uname = '';
  var options = "(";

  if (g_room_moderated[y] == 1) options += "+M";
  if (g_room_locked[y] == 1)    options += "+L";
  options += ")";

  uname = '<a href="#" onclick="userHtmlUser('+g_user_client[x]+");mboxView('user_div');\">"+g_user_name[x]+'</a>';

  print("menul_div", uname+' / '+g_room_id[y]+options+' - '+g_room_title[y]+'<br />');
  return 1;
}

function roomHtmlList() {
  var output = '';
  for (x in g_room_id) {
    if (g_room_id[x] == my_client_id) {
      output += ''+g_room_id[x]+'<br />';
    } else {
      output += g_room_id[x]+'<br />'
    }
  }
  print("rlist_div", output);
  return 1;
}