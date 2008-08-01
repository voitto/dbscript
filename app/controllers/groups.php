<?php

function get( &$vars ) {
  extract( $vars );

  switch ( count( $collection->members )) {
    case ( 1 ) :
      if ($request->id && $request->entry_url())
        render( 'action', 'entry' );
    default :
      render( 'action', 'index' );
  }
}

function put( &$vars ) {
  
  extract( $vars );
  
  $Group->update_from_post( $request );
  
  $subscribers = explode( "\n", $request->subscribers );
  
  $g = $Group->find($request->id);
  
  if ($g && count($subscribers) > 0)
    $result = $db->get_result( "DELETE FROM memberships WHERE group_id = ".$g->id );
  
  foreach ( $subscribers as $addr ) {
    $p = false;
    $i = false;
    $a = trim( $addr );
    $i = $Identity->find_by( 'email_value', $a );
    if (is_email($a) && $i) {
      $p = $i->FirstChild( 'people' );
    } elseif (is_email($a)) {
      $p = $Person->base();
      $p->save();
      $i = $Identity->base();
      $i->set_value( 'url', $a );
      $i->set_value( 'email_value', $a );
      $i->set_value( 'given_name', '' );
      $i->set_value( 'label', 'profile 1' );
      $i->set_value( 'token', dechex(crc32($p->id.microtime())));
      $i->set_value( 'person_id', $p->id );
      $i->save_changes();
    }
    if ($g && is_email($a) && $p) {
      $m = $Membership->base();
      $m->set_value( 'group_id', $g->id );
      $m->set_value( 'person_id', $p->id );
      $m->save_changes();
    }
  }
  
  header( 'Status: 200 OK' );
  redirect_to( 'groups' );
}

function post( &$vars ) {
  
  
  extract( $vars );
  
  $g = $Group->base();
  
  $fields = $Group->fields_from_request( $request );
  
  foreach ( $fields['groups'] as $field=>$type )
    $g->set_value( $field, $request->params['group'][$field] );
  
  $g->save_changes();
  
  $subscribers = explode( "\n", $request->subscribers );
  
  foreach ( $subscribers as $addr ) {
    $p = false;
    $i = false;
    $a = trim( $addr );
    $i = $Identity->find_by( 'email_value', $a );
    if (is_email($a) && $i) {
      $p = $i->FirstChild( 'people' );
    } elseif (is_email($a)) {
      $p = $Person->base();
      $p->save();
      $i = $Identity->base();
      $i->set_value( 'url', $a );
      $i->set_value( 'email_value', $a );
      $i->set_value( 'given_name', '' );
      $i->set_value( 'label', 'profile 1' );
      $i->set_value( 'token', dechex(crc32($p->id.microtime())));
      $i->set_value( 'person_id', $p->id );
      $i->save_changes();
    }
    if (is_email($a) && $p) {
      $m = $Membership->base();
      $m->set_value( 'group_id', $g->id );
      $m->set_value( 'person_id', $p->id );
      $m->save_changes();
    }
  }
  
  header( 'Status: 201 Created' );
  
  redirect_to( 'groups' );
  
}

function delete( &$vars ) {
  extract( $vars );
  $Group->delete_from_post( $request );
  header( 'Status: 200 OK' );
  redirect_to( 'groups' );
}

function index( &$vars ) {
  extract( $vars );
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$profile,
      &$atomfeed,
      &$collection
      
    ),
    get_defined_vars()
  );
}



function _index( &$vars ) {

  extract( $vars );
  $Group->find();
  return vars(
    array(

      &$collection
    ),
    get_defined_vars()
  );

}


function _entry( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  $Member = $Group->find( $request->id );
  
  $Member->set_etag();

  if (!$Member)
    trigger_error( "Sorry, I could not find that entry in groups.", E_USER_ERROR );

  $Membership = $Member->FirstChild( "memberships" );
  
  $Entry = $Member->FirstChild( "entries" );

  return vars(
    array(

      // return vars to the _entry partial
      &$Member,
      &$Membership,
      &$Entry

    ),
    get_defined_vars()
  );

}



function _edit( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  if ( $request->error )
    $Member = session_restore( $db->models['groups'] );
  else
    $Member = $Group->find( $request->id );

  $Entry = $Member->FirstChild( "entries" );


  return vars(
    array(

      // return vars to the _edit partial
      &$Member,
      &$Entry

    ),
    get_defined_vars()
  );

}



function _new( &$vars ) {

  // bring controller vars into scope
  extract( $vars );


  $Member = $Group->base();


  return vars(
    array(

      // return vars to the _new partial
      &$Member
      
    ),
    get_defined_vars()
  );

}

?>