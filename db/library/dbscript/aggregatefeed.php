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
   * AggregateFeed
   * 
   * aggregates multiple collections
   * acts like a single collection
   * should use a "Join Scheme" Category
   * 
   * More info...
   * {@link http://structal.net/aggregatefeed}
   * 
   * @package structal
   * @author Brian Hendrickson <brian@structal.net>
   * @access public
   * @version 0.1.0 -- 01-January-2009
   */

class AggregateFeed extends GenericIterator {
  
  var $member_entry_iri;
  
  var $media_iri;
  
  var $collections;
  
  var $accept;
  
  var $members;
  
  var $fields;
  
  var $updated;
  
  var $per_page;
  
  function AggregateFeed( $collections, $find_by = NULL, $accept = "text/html" ) {
    
    $this->per_page = 10;
    
    $this->_currentRow = 0;
    
    $this->EOF = false;
    
    $this->members = array();
    
    $sortmembers = array();
    
    $this->collections = $collections;
    
    $this->accept = $accept;
    
    foreach($this->collections as $tab=>$coll) {
      foreach($coll->members as $pk=>$time) {
        $sortmembers[] = array(
          'time'=>$time,
          'resource'=>$coll->resource,
          'record_id'=>$pk
        );
      }
    }
    
    $this->members = array_sort($sortmembers, 'time',$this->per_page);
    
  }
  
  function rewind() {
    foreach($this->collections as $coll)
      $coll->rewind();
    $this->_currentRow = 0;
    $this->EOF = false;
  }
  
  function MoveNext() {
    if ($this->_currentRow < $this->per_page) {
      $item = $this->members[$this->_currentRow];
      $coll =& $this->collections[$item['resource']];
      $this->_currentRow = $this->_currentRow + 1;
      return $coll->MoveNext();
    }
    $this->EOF = true;
    return false;
    
  }
  
  function MoveFirst() {
    $item = $this->members[0];
    $coll =& $this->collections[$item['resource']];
    return $coll->MoveFirst();
  }
  
}

?>