<?php

  /** 
   * structal -- Social Media Programming Language
   * @version 0.1.0 -- 01-January-2009
   * @author Brian Hendrickson <brian@structal.net>
   * @link http://structal.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @package structal
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   */

  /**
   * Generic Iterator
   * 
   * For looping over arrays, directories, file contents, etc.
   * 
   * More info...
   * {@link http://structal.net/genericiterator}
   * 
   * @package structal
   * @author Brian Hendrickson <brian@structal.net>
   * @access public
   * @version 0.1.0 -- 01-January-2009
   */

class GenericIterator {
  
  var $EOF;
  var $_currentRow;
  var $collection;
  
  function rewind() {
    $this->MoveFirst();
  }
  
  function valid() {
    return !$this->EOF;
  }
  
  function key() {
    return $this->_currentRow;
  }
  
  function current() {
    $this->Load();
  }
  
  function next() {
    $this->MoveNext();
  }
  
  function call($func, $params) {
    return call_user_func_array(array($this->collection, $func), $params);
  }
  
  function hasMore() {
    return !$this->EOF;
  }
  
}

?>