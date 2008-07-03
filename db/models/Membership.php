<?php

class Membership extends Model {
  
  function Membership() {
    
    // fields
    
    $this->int_field( 'group_id' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'person_id' );
        
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    $this->has_one( 'person' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
    $this->set_hidden();
    
  }
  
  function init() {
    
    $M = $this->base();
    $M->set_value( 'group_id', 2 );
    $M->set_value( 'person_id', 1 );
    $M->save_changes();
    
  }
  
}

?>