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
   * Resource
   * 
   * A Restful HTTP client for accessing remote Models.
   * 
   * Usage:
   * <code>
   *   $jopeeps = $db->get_resource( 'http://joe.net/?people' );
   * </code>
   * 
   * More info...
   * {@link http://structal.net/resource}
   * 
   * @package structal
   * @author Brian Hendrickson <brian@structal.net>
   * @access public
   * @version 0.1.0 -- 01-January-2009
   * @todo implement
   */

class Resource {

  var $name;
  
  function Resource() {

    $this->defaults = array(
      
      'destination'=>'',
      'timeout'=>60
      
    );
    
  }

}

?>