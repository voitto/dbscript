<?php

after_filter( 'broadcast_omb_notice', 'insert_from_post' );

function broadcast_omb_notice( &$model, &$rec ) {
  
  global $request, $db;
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $Identity =& $db->model( 'Identity' );
  
  $Identity->has_many( 'id:subscriptions.subscribed' );
  
  $i = $Identity->find( get_profile_id() );
  
  $listenee_uri = $i->profile;
  
  $notice_uri = $request->url_for( array(
    'resource'=>'__'.$rec->id,
  ));
  
  $notice_content = $rec->title;
  $notice_url = $notice_uri;
  $license = $i->license;
  
  $sent_to = array();
  
  while ( $sub = $i->NextChild( 'subscriptions' )) {

    $sub_token = $sub->token;
    $sub_secret = $sub->secret;
    
    $sid = $Identity->find( $sub->subscriber );
    $url = $sid->post_notice;
    
    $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $consumer = new OAuthConsumer($request->base, '', NULL);
    $token = new OAuthToken($sub_token, $sub_secret);
    $parsed = parse_url($url);
    $params = array();
    
    parse_str($parsed['query'], $params);
    $req = OAuthRequest::from_consumer_and_token($consumer, $token, "POST", $url, $params);
    $req->set_parameter( 'omb_version', OMB_VERSION );
    $req->set_parameter( 'omb_listenee', $listenee_uri );
    $req->set_parameter( 'omb_notice', $notice_uri );
    $req->set_parameter( 'omb_notice_content', $notice_content );
    $req->set_parameter( 'omb_notice_url', $notice_url );
    $req->set_parameter( 'omb_notice_license', $license );
    $req->sign_request( $sha1_method, $consumer, $token );
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $req->to_postdata());
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    echo $result; exit;
    curl_close($curl);
    
  }
}

?>