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

  private $name = 'PHPChatScript';
  private $weight = 0;
  private static $codes = array(
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
    parent::receive_message($code, $server);
    return;
  }

  public function halt() {
    parent::halt();
    return;
  }

}


// Register our plugin.
Server::register_plugin('PHPChatScript', PHPChatScript::get_codes());

?>