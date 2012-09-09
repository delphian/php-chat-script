<?php

/**
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * This should be used as an example to build custom server side plugins.
 * It is actually used so don't delete it unless you know what your doing.
 */

class PHPChatScript extends ServerPlugin {

  protected static $name = 'PHPChatScript';
  protected static $codes = array(
    '__halt',
    'request_client_id',
    '2001',
  );

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
    return;
  }

  // Main function to process a message.
  public function receive_message(&$code, Server $server) {
    switch($code) {
      case '__halt':
        $this->code_halt();
        break;
      case '2001':
        $this->code_update();
        break;
    }

    // Allow plugins to change what we have done.
    parent::receive_message($code, $server);

    if ($this->output) {
      $this->headers[] = 'Content-Type: text/plain';
      $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
      $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
      $server->set_headers($this->headers);
      $server->set_output($this->output);
    }

    return;
  }

  // halt.
  public function code_halt() {
    // Parent will persist our variables.
    parent::halt();
    return;
  }

  // Update client with any changes.
  public function code_update() {
    if (isset($this->variables['2001'])) {
      $this->variables['2001']++;
    }
    else {
      $this->variables['2001'] = 0;
    }
    $this->output = 'No soup for you!' . ' ' . $this->variables['2001'];
    return;
  }

}


// Register our plugin.
Server::register_plugin('PHPChatScript', PHPChatScript::get_codes());

?>