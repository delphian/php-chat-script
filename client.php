<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>PHP Chat Script Client</title>
  <link href="client.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="js/dom-print.js"></script>
  <script type="text/javascript" src="js/chat-client-msg.js"></script>
  <script type="text/javascript" src="js/chat-client-room.js"></script>
  <script type="text/javascript" src="js/chat-client-user.js"></script>
  <script type="text/javascript" src="js/chat-client.js"></script>
</head>
<body>

<div id="encap_div">
  <div id="title_div">
    <div id="title_bord">
      <div id="title_marg">
        <a href="http://www.phpchatscript.com">PHP Chat Script</a> |
        <a href="git://github.com/delphian/php-chat-script.git">Git</a>
      </div>
    </div>
  </div>
  <div id="menu_div">
    <div id="menu_bord">
      <div id="menul_div">(None)</div>
      <div id="menur_div">
        <a href="#" onclick="mboxView('media_div')">Media</a>
        <a href="#" onclick="mboxView('ulist_div')">Users</a>
        <a href="#" onclick="mboxView('rlist_div')">Rooms</a>
      </div>
    </div>
  </div>
  <div id="mid_div">
    <div id="mid_bord">
      <div id="midl_div">
        <div id="midl_top">
          <div id="text_div"></div>
        </div>
        <div id="midl_bot">
          <div id="text_input"><input id="input_box" onkeypress="return clientInputKeystroke(event)" type="text" value="type here and hit enter." /></div>
          <div id="text_buts">
            (<a title="Decrease font size" href="#" onclick="clientInputFontDown()">-</a><a title="Increase font size" href="#" onclick="clientInputFontUp()">+</a>)
            <a title="Change your chat name" href="#" onclick="clientInputSetup('name')">N</a><a 
               title="Load a Youtube video" href="#" onclick="clientInputSetup('youtube')">Y</a><a
               title="Load an Image" href="#" onclick="clientInputSetup('image')">I</a>
          </div>
        </div>
      </div>
      <div id="midr_div">
        <div id="mbox_div">
          <div id="sbox_div">
            <div id="ulist_div"></div>
            <div id="media_div"></div>
            <div id="rlist_div"></div>
            <div id="user_div"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="bot_div">
    <div id="bot_bord">
      <div id="link_div">
        <a href="http://www.gmodules.com/ig/creator?url=http://hosting.gmodules.com/ig/gadgets/file/102453829241266631407/open-chat.xml">Add to my webpage</a>
      </div>
    </div>
  </div>
</div>

<div id="set_div">
  <div id="setl_div"></div>
  <div id="setm_div">
    <input id="set_type" />
    <input id="set_input" />
  </div>
  <div id="setr_div">
    <a href="#" onclick="clientInputSetOK()"><img alt="ok" src="image/ok.png" /></a>
    <a href="#" onclick="clientInputSetCancel()"><img alt="cancel" src="image/cancel.png" /></a>
  </div>
</div>

<embed id="sound_emb" src="sound/ding.mp3" />

</body>
</html>