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
  $Comment->update_from_post( $request );
  header( 'Status: 200 OK' );
  redirect_to( 'comments' );
}

function post( &$vars ) {
  extract( $vars );
  if (strlen($request->comment['comment']) > 0)
    $Comment->insert_from_post( $request );
  if (isset($request->review['rating']))
    if ($request->review['rating'] > 0)
      $Review->insert_from_post( $request );
  header( 'Status: 201 Created' );
  $e = $Entry->find($request->comment['target_id']);
  redirect_to( array('resource'=>$e->resource, 'id'=>$e->record_id));
}

function delete( &$vars ) {
  extract( $vars );
  $Comment->delete_from_post( $request );
  header( 'Status: 200 OK' );
  redirect_to( 'comments' );
}

function index( &$vars ) {
  extract( $vars );
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$profile,
      &$Comment,
      &$atomfeed,
      &$collection
      
    ),
    get_defined_vars()
  );
}



function _index( &$vars ) {

  extract( $vars );

  return vars(
    array(

      &$collection
    ),
    get_defined_vars()
  );

}



function _edit( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  if ( $request->error )
    $Comment = session_restore( $db->models['comments'] );
  else
    $Comment = $Comment->find( $request->id );

  $Entry = $Comment->FirstChild( "entries" );


  return vars(
    array(

      // return vars to the _edit partial
      &$Comment,
      &$Entry

    ),
    get_defined_vars()
  );

}



function _new( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  if ( $request->error )
    $Comment = session_restore( $db->models['comments'] );
  else
    $Comment = $Comment->find( $request->id );


  return vars(
    array(

      // return vars to the _new partial
      &$Comment,
    ),
    get_defined_vars()
  );

}


function _entry( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  $Comment = $Comment->find( $request->id );

  if (!$Comment)
    trigger_error( "Sorry, I could not find that entry in comments.", E_USER_ERROR );

  $Entry = $Comment->FirstChild( "entries" );

  return vars(
    array(

      // return vars to the _entry partial
      &$Comment,
      &$Entry

    ),
    get_defined_vars()
  );

}

?>
