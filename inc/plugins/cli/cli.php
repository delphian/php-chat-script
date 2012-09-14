<?php
// http://www.oocities.org/maurice_osborn/Serpo.htm
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

class Cli extends Plugin {

  protected static $routes = array(
    '/',
    'cli/get_message',
    'cli/set_message',
  );

  // Main function to process a message.
  public function receive_message(&$route, $caller) {
    switch($route) {
      case '/':
        $this->route_root();
        break;
      case 'cli/set_message':
        $this->route_set_message();
        break;
      case 'cli/get_message':
        $this->route_get_message();
        break;
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $caller->set_output($this->output);
    }

    return;
  }

  // Load up the javascript bare bones interface.
  public function route_root() {
    // Load up the interface.
    $client_file = file_get_contents('inc/plugins/cli/files/client.html');

    $this->output['body'] = $client_file;
    $this->output['headers'][] = 'Content-Type: text/html';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

  public function route_get_message() {
    $response = array(
      'code' => 'NAC',
    );

    $this->output['body'] = json_encode($response);
    $this->output['headers'][] = 'Content-Type: text/text';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

  /**
   * A command was sent to us from the client.
   */
  public function route_set_message() {
    $input = json_decode($this->payload, TRUE);

    switch ($input['code']) {
      case 'help':
        $this->subcode_help();
        break;
      case 'say':
        $this->subcode_say();
        break;
    }

    // Let plugins change what we have done.
    $this->invoke_all($input['code']);

    return;
  }

  /**
   * Help
   */
  public function subcode_help() {
    $response = array(
      'code'    => 'output',
      'payload' => 'Everybody wants help...',
    );

    $this->output['body'] = json_encode($response);
    $this->output['headers'][] = 'Content-Type: text/text';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;  
  }

  /**
   * Say
   */
  public function subcode_say() {
    $this->variables['say']++;  
    $response = array(
      'code'    => 'output',
      'payload' => $this->variables['say'],
    );

    $this->output['body'] = json_encode($response);
    $this->output['headers'][] = 'Content-Type: text/text';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
    return;  
  }

}

// Register our plugin.
Server::register_plugin('Cli', Cli::get_routes());

?>