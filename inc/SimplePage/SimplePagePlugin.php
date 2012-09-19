<?php

/**
 * @file
 * Provides route handlers for basic SimplePage manipulations.
 *
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

class SimplePagePlugin extends Plugin {

  // Main function to process a message.
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->output = $observed->get_output();
    $this->user = $observed->get_user();

    $user_id = 0;
    if ($this->user) {
      $user_id = $this->user->get_user_id();
    }

    /** Setup parameters. */
    $variables = array(
      'caller' => $observed,
      'user_id' => $user_id,
    );

    /** We will only process a route if we have a valid user. */
    if ($user_id) {
      switch($route) {
        case 'simplepage/page/get':
          $this->route_simplepage_page_get($observed);
          break;
        case '__cli/javascript':
          $this->cli_javascript($observed);
          break;
        case '__cli/command/help':
          $this->cli_command_help($observed);
          break;
      }
    }
    else {
      /** Do nothing for the moment. */
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $observed->set_output($this->output);
    }

    return;
  }

  public function route_simplepage_page_get($observed) {
    $path = $observed->payload['simplepage']['path'];
    if (SimplePage::exists($path)) {
      $page = new SimplePage($path);
      $page_array = $page->make_array();
      $response = array(
        'code'    => 'simplepage',
        'payload' => array(
          'code' => 'page_get',
          'payload' => get_class_vars($page),
        );
      );
      $this->output['body'] = json_encode($response);
      $this->headers_text();
    }

    return;
  }

  /** Add our javascript files to the command line interface. */
  public function cli_javascript($observed) {
    $javascript = $observed->get_javascript();
    // $javascript[] = 'inc/SimplePage/files/SimplePageCli.js';
    $observed->set_javascript($javascript);
  }

  /**
   * Add our commands to the CLI help text.
   */
  public function cli_command_help($variables) {
    $output = json_decode($this->output['body'], TRUE);
    $output['payload'] .= 'Anything typed without a forward slash will be spoken in common chat.<br />';
    $output['payload'] .= '<b>/simplepage</b> Perform page related functions.<br />';

    $response = array(
      'code' => 'output',
      'payload' => $output['payload'],
    );
    $this->output['body'] = json_encode($response);
  }

  /**
   * Generate straight text output.
   */
  public function headers_text() {
    $this->output['headers'] = NULL;
    $this->output['headers'][] = 'Content-Type: text/text';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

}

// Register our plugin for callback.
Server::register_plugin('SimplePagePlugin', array(
  'simplepage/page/get',
  '__cli/javascript',
  '__cli/command/help',
));


?>