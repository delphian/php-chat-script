<?php

/**
 * @file
 *
 * Create limited persistent variables that are stored in a text file.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 *
 * @todo Should we get rid of configuration and just assume the data file
 * is in the same directory as our class file? This might make the class
 * more portable in the future.
 */


/**
 * Extend this class to receive persistant variable storage.
 */
abstract class PersistentVariable {

  /** Configuration information about our current environment. */
  protected $config = NULL;

  /** This variable will be persistant and automatically loaded at 
      instantiation. Massive data storage needs are not intended to be met by
      this object. */
  protected $variables = array();

  /**
   * All parameters for paths should be absolute and not include a trailing 
   * slash.
   *
   * @param array $config
   *   Assocative array of strings containing configuration options.
   *   - path_root: (string) Path to the base directory of script.
   *   - path_inc: (string) Path to the include directory.
   *   - path_plugins: (string) Path to the plugins directory.
   *   - path_data: (string) Path to the data directory.
   *
   * @return mixed [Service superclass]
   */
  public function __construct($config) {
    $this->config = $config;
    $this->variables_read();

    return $this;  
  }

  /**
   * Save any changes to our variables.
   */
  public function __destruct() {
    // Persist our variables.
    $this->variables_write();

    return;
  }

  /**
   * Read in variables persisted in a text file.
   *
   * Text file contains a single json string keyed by class name.
   * $this->variables will be filled with the same object it contained before
   * being persisted during last execution. This is not intended to replace
   * a database, but only to record minor runtime variables.
   *
   * @return bool TRUE
   */
  public function variables_read() {
    $path_data = $this->config['path_data'];
    if (file_exists($path_data . '/variables.txt')) {
      $name = get_called_class();
      $file_array = json_decode(file_get_contents($path_data . '/variables.txt'), TRUE);
      if (isset($file_array[$name])) {
        $this->variables = $file_array[$name];
      }
    }

    return TRUE;
  }

  /**
   * Write variables to persistant storage.
   *
   * @see Subject::variables_read()
   */
  public function variables_write() {
    if (!empty($this->variables)) {
      $path_data = $this->config['path_data'];
      $name = get_called_class();
      $file_array = array(); 
      $file_array = json_decode(file_get_contents($path_data . '/variables.txt'), TRUE);
      $file_array[$name] = $this->variables;
      $handle = fopen($path_data . '/variables.txt', 'w');
      if ($handle) {
        $encoded = json_encode($file_array);
        if (!$x = fwrite($handle, $encoded)) {
          throw new Exception('Could not write to variables file.');
        }
        fclose($handle);
      } else {
        throw new Exception('Could not open variable file for writing.');
      }
    }
  }

  /**
   * Get property.
   */
  public function get_variables() {
    return $this->variables;
  }


}

?>