<?php

/**
 * @file
 *
 * Create limited persistent variables that are stored in a text file. Only
 * singletons should be using this functionality.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */


/**
 * Extend this class to receive persistant variable storage.
 */
abstract class PersistentVariable extends SingletonLoader {

  /** This variable will be persistant and automatically loaded at 
      instantiation. Massive data storage needs are not intended to be met by
      this object. */
  protected $variables = array();

  /**
   * @return mixed [Service superclass]
   */
  public function __construct() {
    $name = get_called_class();
    $this->variables = SimpleTextStorage::load()->read($name, 'variables');

    return $this;  
  }

  /**
   * Save any changes to our variables.
   */
  public function __destruct() {
    // Persist our variables.
    $name = get_called_class();
    SimpleTextStorage::load()->write($name, 'variables', $this->variables);

    return;
  }

  /**
   * Get property.
   */
  public function get_variables() {
    return $this->variables;
  }


}

?>