var ChatCli = function() {
  this.message = null;
};

ChatCli.prototype.processMessage = function(message) {
  this.message = message;
  //printPlus("text_div", '<span class="cln_all">Woot!</span><br />');
  alert('woot!');
  return;
};

chatCli = new ChatCli();
PM.register(chatCli);

PM.process('test');
