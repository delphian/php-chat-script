<?php

/**
 * @file
 *
 * Provides very basic storage and retrieval of objects into a text file.
 *
 * "Objects" will generally mean arrays, but could be only a single value or
 * possibly a more complex object. This is not a replacement for a real
 * database. It is not intended to hold large amounts of information, provide
 * any advanced query, or operate quickly. Variables can be namespaced, saved
 * and loaded by specifing the namespace and specific variable name.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */

/**
 * @class
 *
 * Usage:
 * @code
 *   SimpleTextStorage::load()->write('MyNameSpace', 'MyVar', 'My Str Value');
 *   print SimpleTextStorage::load()->read('MyNameSpace', 'MyVar');
 * @endcode
 */
class SimpleTextStorage extends SingletonLoader {

  /** Absolute path where our php file resides. Set by constructor. */
  private $path = NULL;
  /** Absolute path where our data directory resides. Set by constructor. */
  private $path_data = NULL;

  /**
   * Set our path and path_data properties.
   */
  public function __construct() {
    $this->path = preg_replace('/\/[^\/]*$/', '', __FILE__);
    $this->path_data = $this->path . '/data';
    
    /** Create the data directory if it does not exist. */
    if (!is_dir($this->path_data)) {
      if (!mkdir($this->path_data, 755)) {
        throw new Exception('Data directory not found and unable to create.');
      }    
    }

    return $this;
  }

  /**
   * Read in variables persisted in a text file.
   *
   * Individual variables are stored as json strings in a namespaced textfile.
   * The entire text file is one large json string, keyed by variable name.
   *
   * @param string $namespace
   *   The namespace this variable resides in. Basicly this is transformed into
   *   a file name.
   * @param string $var_name
   *   The variable to retrieve from the text file.
   *
   * @return mixed $value
   *   The value of the variable if found, NULL if not found.
   */
  public function read($namespace, $var_name) {
    $value = NULL;

    if (preg_match('/^(A-Za-z_)+$/', $namespace)) {
      throw new Exception('Invalid name space.');
    }
    $file_name = $this->path_data . '/' . $namespace . '.txt';
    if (file_exists($file_name)) {
      $file_array = json_decode(file_get_contents($file_name), TRUE);
      if (isset($file_array['data'][$var_name])) {
        $value = $file_array['data'][$var_name];
      }
    }

    return $value;
  }

  /**
   * Write variables to persistant storage.
   *
   *
   * @param string $namespace
   *   The namespace this variable resides in. Basicly this is transformed into
   *   a file name.
   * @param string $var_name
   *   The variable name to assign data to.
   * @param mixed $var_data
   *   The data to assign to the $var_name variable. Generally this is an
   *   object.
   *
   * @return
   *   SimpleTextStorage $this
   *
   * @see SimpleTextStorage::read()
   */
  public function write($namespace, $var_name, $var_data) {
    $file_array = array();
    if (preg_match('/^(A-Za-z_)+$/', $namespace)) {
      throw new Exception('Invalid name space.');
    }
    $file_name = $this->path_data . '/' . $namespace . '.txt';
    if (file_exists($file_name)) {
      $file_array = json_decode(file_get_contents($file_name), TRUE);
    }
    $handle = fopen($file_name, 'w');
    if ($handle) {
      $file_array['data'][$var_name] = $var_data;
      $encoded = json_encode($file_array);
      if (!fwrite($handle, $encoded)) {
        throw new Exceptioon('Could not write variable to file.');
      }
      fclose($handle);
    }
    else {
      throw new Exception('Could not open namespace file for writing.');
    }

    return $this;
  }

}

?>