<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.4.0 -- 1-May-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */
   
  /**
   * Model Security
   * 
   * filter to check permissions in $model->access_list,
   * which can be set in the data model via:
   * $model->let_read/let_write/let_access( 'group:callback' )
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Mapper $req
   * @param Database $db
   * @return boolean
   * @todo modify to handle a partial set of fields
   */

function model_security( &$req, &$db ) {
  
  $action = $req->action;
  
  if ( isset( $req->resource ) )
    if ( in_array( $req->resource, array( 'introspection' ) ))
      return true;
  
  if ( isset( $req->resource ) )
    $model =& $db->get_table( $req->resource );
  else
    return true; // request is not for a resource
  
  if ( !( in_array( $action, $model->allowed_methods, true )))
    $action = 'get';
  
  $failed = false;
  
  if ( isset( $request->resource ) && !(public_resource()) )
    authenticate_with_openid();
  
  switch( $action ) {
    case 'get':
      if (!($model && $model->can_read_fields( $model->field_array )))
        $failed = true;
      break;
    case 'put':
      $submitted = $model->fields_from_request( $req );
      foreach ( $submitted as $table=>$fieldlist ) {
        $model =& $db->get_table($table);
        if (!($model && $model->can_write_fields( $fieldlist )))
          $failed = true;
      }
      break;
    case 'post':
      $submitted = $model->fields_from_request( $req );
      foreach ( $submitted as $table=>$fieldlist ) {
        $model =& $db->get_table($table);
        if (!($model && $model->can_write_fields( $fieldlist )))
          $failed = true;
        if (!($model && $model->can_create( $table )))
          $failed = true;
      }
      break;
    case 'delete':
      if (!($model && $model->can_delete( $req->resource )))
        $failed = true;
      break;
    default:
      $failed = true;
  }
  
  if (!$failed)
    return true;
  
  authenticate_with_openid();
  
  trigger_error( "Sorry, you do not have permission to $action ".$req->resource, E_USER_ERROR );
  
}

function authenticate_with_openid() {
  
  global $request;
  
  if ( $request->openid_complete )
    complete_openid_authentication( $request );
  else
    begin_openid_authentication( $request );
  
}

function begin_openid_authentication( &$request ) {
  
  
  
  if ( !( isset( $request->openid_url ))) {
    $_SESSION['requested_url'] = $request->uri;
    render( 'action', 'email' );
    return;
  }
  
  unset_cookie();

  $_SESSION['openid_url'] = $request->openid_url;

    global $openid;

    if( !empty( $request->openid_url ) ) {
      if( !WordPressOpenID_Logic::late_bind() ) return; // something is broken
      $redirect_to = '';
      if( !empty( $_SESSION['requested_url'] ) ) $redirect_to = $_SESSION['requested_url'];
      //WordPressOpenID_Logic::start_login( $_POST['openid_url'], 'login', array('redirect_to' => $redirect_to) );
    
    $claimed_url = $request->openid_url;

			set_error_handler( array('WordPressOpenID_Logic', 'customer_error_handler'));
			$consumer = WordPressOpenID_Logic::getConsumer();
			$auth_request = $consumer->begin( $claimed_url );
			restore_error_handler();

		if ( null === $auth_request ) {
			$openid->error = 'Could not discover an OpenID identity server endpoint at the url: '
			. htmlentities( $claimed_url );
			if( strpos( $claimed_url, '@' ) ) {
				$openid->error .= '<br/>The address you specified had an @ sign in it, but OpenID '
				. 'Identities are not email addresses, and should probably not contain an @ sign.';
			}
			$openid->log->debug('OpenIDConsumer: ' . $openid->error );
			return;
		}
			
		$openid->log->debug('OpenIDConsumer: Is an OpenID url. Starting redirect.');


		// build return_to URL
		$return_to = $request->url_for( 'openid_continue' );
		
		/* If we've never heard of this url before, do attribute query */
		$store =& WordPressOpenID_Logic::getStore();
		if( $store->get_user_by_identity( $auth_request->endpoint->identity_url ) == NULL ) {
			$attribute_query = true;
		}
		if ($attribute_query) {
			// SREG
			$sreg_request = Auth_OpenID_SRegRequest::build(array(),array('nickname','email','fullname'));
			if ($sreg_request) $auth_request->addExtension($sreg_request);
		}
			
		$_SESSION['oid_return_to'] = $return_to;
		WordPressOpenID_Logic::doRedirect($auth_request, $request->protected_url, $return_to);
		exit(0);
  }

  //include_once $GLOBALS['PATH']['library'] . 'openid.php';
  
  //$openid = new SimpleOpenID;
  
  //$openid->SetIdentity( $request->openid_url );
  
  
  //$openid->SetApprovedURL( $request->url_for( 'openid_continue' )); // y'all come back now
  
  //$openid->SetTrustRoot( $request->protected_url ); // protected site
  //$openid->SetTrustRoot( $request->protected_url ); // protected site
  
  //$openid->SetOptionalFields(environment('profile')); 
  //$openid->SetRequiredFields(array());
  // 'email','fullname','dob','gender','postcode','country','language','timezone'
  //$server_url = $openid->GetOpenIDServer();
  
  //$_SESSION['openid_server_url'] = $server_url;
  #echo $server_url; exit;
  //$openid->SetOpenIDServer( $server_url );
  
  //redirect_to( $openid->GetRedirectURL() );
  
}


function complete_openid_authentication( &$request ) {

  if (!(check_cookie())) {
    
    // cookie not set, DO IT
    
    $openid_to_identity = array(
      'email'=>'email_value',
      'dob'=>'dob',
      'postcode'=>'postal_code',
      'country'=>'country_name',
      'gender'=>'gender',
      'language'=>'language',
      'timezone'=>'tz'
    );
    
    if ( isset( $_SESSION['openid_url'] )) {
      global $db;
      $Identity =& $db->get_table( 'identities' );
      $Person =& $db->get_table( 'people' );
      $i = $Identity->find_by( 'url', $_SESSION['openid_url'] );
      if ($i) {
        // found an existing identity
        $p = $Person->find( $i->person_id );

      } else {
        // need to create the identity (and person?) because it was not found
        if (isset($_GET['openid_sreg_email']))
          $i = $Identity->find_by( 'email_value', $_GET['openid_sreg_email'] );
        elseif (isset($_SESSION['openid_email']))
          $i = $Identity->find_by( 'email_value', $_SESSION['openid_email'] );
        if ($i) {
          $p = $Person->find( $i->person_id );
        } else {
          $p = $Person->base();
          $p->save();
          $i = $Identity->base();
        }
        $i->set_value( 'url', $_SESSION['openid_url'] );
        $i->set_value( 'given_name', '' );
        $i->set_value( 'label', 'profile 1' );
        $i->set_value( 'person_id', $p->id );
        if (isset($_SESSION['openid_email']))
          $i->set_value( 'email_value', $_SESSION['openid_email'] );
        foreach($openid_to_identity as $k=>$v ) {
          if (isset($_GET['openid_sreg_'.$k])) {
            $i->set_value( $v, $_GET['openid_sreg_'.$k] );
          }
        }
        if (isset($_GET['openid_sreg_fullname'])) {
          $names = explode(' ',$_GET['openid_sreg_fullname']);
          if (strlen($names[0]) > 0)
            $i->set_value( 'given_name', $names[0] );
          if (isset($names[2])) {
            $i->set_value( 'family_name', $names[2] );
          } elseif (isset($names[1])) {
            $i->set_value( 'family_name', $names[1] );
          }
        }
        $i->save_changes();
        $i->set_etag($p->id);
      }
      if ( isset( $p->id ) && $p->id != 0) {
        // person id is valid
        set_cookie( $p->id );
        if (!(empty($_SESSION['requested_url'])))
          redirect_to( $_SESSION['requested_url'] );
        else
          redirect_to( $request->base );
      } else {
        // need to create a person, redirect to input form
        // manually build URL du
        trigger_error( "unable to find the Person, sorry", E_USER_ERROR );
        #redirect_to( "http://" . $request->domain . $request->path . "?people/new.html" );
      }
    }
  }
  
}



function ldap_login( &$vars ) {
  extract( $vars );
  $_SESSION['requested_url'] = $request->base;
  render( 'action', 'ldap' );
}

function _ldap( &$vars ) {
  extract( $vars );
}

function ldap_submit( &$vars ) {
  extract($vars);
  global $request;
}



function _email( &$vars ) {
  
  extract( $vars );
  
  $submit_url = $request->url_for( environment('authentication').'_submit' );
  
  $return_url = $request->url_for( 'openid_continue' );
  if (isset($_SESSION['requested_url']))
    $return_to = $_SESSION['requested_url'];
  else
    $return_to = $request->base;
  $protected_url = $request->base;
  if (isset($request->params['ident'])) {
    $ident = $Identity->find_by('token',$request->params['ident']);
    if ($ident) {
      $email = $ident->email_value;
      $ident->set_value('token','');
      $ident->save_changes();
    } else {
      $email = false;
    }
  } else {
    $email = false;
  }
  return vars(
    array(
      
      &$email,
      &$protected_url,
      &$return_url,
      &$submit_url,
      &$return_to
      
    ),
    get_defined_vars()
  );
  
}

function _login( &$vars ) {
  extract( $vars );
  $submit_url = $request->url_for( 'openid_submit' );
  $return_url = $request->url_for( 'openid_continue' );
  if (isset($_SESSION['requested_url']))
    $return_to = $_SESSION['requested_url'];
  else
    $return_to = $request->base;
  $protected_url = $request->base;
  
  return vars(
    array(
      
      &$protected_url,
      &$return_url,
      &$submit_url,
      &$return_to
      
    ),
    get_defined_vars()
  );
  
}

function normalize_url() {
  //
}

function password_submit( &$vars ) {
  extract($vars);
  global $request;
  $Identity =& $db->get_table( 'identities' );
  $i = $Identity->find_by(array(
    'nickname'=>$request->nickname,
    'password'=>md5($request->password)
  ),1);
  $p = $Person->find( $i->person_id );
  if ( isset( $p->id ) && $p->id != 0) {
    $_SESSION['openid_complete'] = true;
    set_cookie( $p->id );
    if (!(empty($_SESSION['requested_url'])))
      redirect_to( $_SESSION['requested_url'] );
    else
      redirect_to( $request->base );
  } else {
    trigger_error( "unable to find the Person, sorry", E_USER_ERROR );
  }
}

function openid_submit( &$vars ) {
  authenticate_with_openid();
}

function email_submit( &$vars ) {
  extract($vars);
  global $request;
  $Identity =& $db->get_table( 'identities' );
  $i = $Identity->find_by( 'email_value', $request->email );
  $_SESSION['openid_email'] = $request->email;
  if ( $i && !(strstr( $i->url, "@" )) && !empty($i->url)) {
    $request->openid_url = $i->url;
    authenticate_with_openid();
  } else {
    $url = environment('openid_server')."/?action=seek&email=".$request->email;
    $curl = curl_init($url);
    $method = "GET";
    $params = array();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
    curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
    if ($method == "POST") curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    
    if ( curl_errno($curl) == 0 ) {
      
      if (strstr( $response, "http" )) {
        
        // found a url, need to put it in the openid form
        
        $request->set_param('openid_url',trim($response));
        
        authenticate_with_openid();
        
      } else {
        
        // need to create a URL?
        
        
      }
    }
    
    $_SESSION['requested_url'] = $request->base;
    redirect_to(environment('openid_server')."/?action=register&return=".urlencode($request->base)."&email=".urlencode($request->email));
  }

}

function openid_logout( &$vars ) {
  unset_cookie();
  extract( $vars );
  $_SESSION['openid_complete'] = false;
  $_SESSION['requested_url'] = '';
  redirect_to( environment('openid_server')."/?action=logout&return=".urlencode($request->base) );
}

function email_login( &$vars ) {
  extract( $vars );
  $_SESSION['requested_url'] = $request->base;
  render( 'action', 'email' );
}

function openid_login( &$vars ) {
  
  extract( $vars );
  
  global $request;
  
  if (isset($request->openidurl)) {
    
    $openid = urldecode($request->openidurl);
    
    if (strstr($openid,'http://'))
      $openid = substr($openid,7);
    
    if (strstr($openid,'https://'))
      $openid = substr($openid,8);
    
    $request->set_param('return_url',$request->url_for( 'openid_continue' ));
    
    $request->set_param('protected_url',$request->base);
    
    $request->set_param('openid_url',trim($openid));
        
    authenticate_with_openid();
    
    if (!(empty($_SESSION['requested_url'])))
      redirect_to( $_SESSION['requested_url'] );
    else
      redirect_to( $request->base );
    
  }
  
  render( 'action', 'login' );
  
}

function openid_continue( &$vars ) {
  
  extract( $vars );
  
  //include $GLOBALS['PATH']['library'] . 'openid.php';
  
  //$openid = new SimpleOpenID;
  
  global $openid;

  //set_error_handler( array('WordPressOpenID_Logic', 'customer_error_handler'));
  $consumer = WordPressOpenID_Logic::getConsumer();
  $openid->response = $consumer->complete($_SESSION['openid_url']);
  //restore_error_handler();
    
  switch( $openid->response->status ) {
    case Auth_OpenID_CANCEL:
      $openid->error = 'OpenID assertion cancelled';
      break;

    case Auth_OpenID_FAILURE:
      $openid->error = 'OpenID assertion failed: ' . $openid->response->message;
      break;

    case Auth_OpenID_SUCCESS:
      $openid->error = 'OpenID assertion successful';
      $_SESSION['openid_complete'] = true;
      $identity_url = $openid->response->identity_url;
      $escaped_url = htmlspecialchars($identity_url, ENT_QUOTES);
      $openid->log->notice('Got back identity URL ' . $escaped_url);
      if ($openid->response->endpoint->canonicalID) {
        $openid->log->notice('XRI CanonicalID: ' . $openid->response->endpoint->canonicalID);
      }
      break;
      
    default:
      trigger_error( "Sorry, the openid server $server_url did not validate your identity.", E_USER_ERROR );
  }

      

  
  
  //$openid->SetIdentity( $_SESSION['openid_url'] );
  
  //$openid->SetApprovedURL( $request->url_for( 'openid_continue' ));
  
  //$openid->SetTrustRoot( $request->base );
  
  //$server_url = $_SESSION['openid_server_url'];
  
  //$openid->SetOpenIDServer( $server_url );
  
  //$valid = $openid->ValidateWithServer();
  
  
  $Identity =& $db->get_table( 'identities' );
  $Person =& $db->get_table( 'people' );
  $i = $Identity->find_by( 'url', $_SESSION['openid_url'] );
  
  complete_openid_authentication( $request );
  
  
  if (!(empty($_SESSION['requested_url'])))
    redirect_to( $_SESSION['requested_url'] );
  else
    redirect_to( $request->base );
  
}

function security_init() {
  
  global $request;
  
  // add Routes -- route name, pattern to match, and default request parameters
  
  $request->connect( 'openid_continue', 'openid_continue', array( 'action'=>'openid_continue' ));
  
  $request->connect( 'openid_login_return', 'openid_login/:openidurl', array( 'action'=>'openid_login' ));
  
  $request->connect( 'openid_submit', 'openid_submit', array( 'action'=>'openid_submit' ));
  
  $request->connect( 'password_submit', 'password_submit', array( 'action'=>'password_submit' ));
  
  $request->connect( 'openid_logout', 'openid_logout', array( 'action'=>'openid_logout' ));
  
  $request->connect( 'openid_login', 'openid_login', array( 'action'=>'openid_login' ));
  
  $request->connect( 'email_login', 'email_login', array( 'action'=>'email_login' ));
  
  $request->connect( 'email_submit', 'email_submit', array( 'action'=>'email_submit' ));
  
  $request->connect( 'ldap_login', 'ldap_login', array( 'action'=>'ldap_login' ));
  
  $request->connect( 'ldap_submit', 'ldap_submit', array( 'action'=>'ldap_submit' ));
  
  $request->routematch();
  
  if ( isset( $_SESSION['openid_complete'] ) && check_cookie() )
    if ( !isset($request->openid_url) && $_SESSION['openid_complete'] == true)
      $request->openid_complete = true;
  
}

function security_install() {
  //
}

function security_uninstall() {
  //
}

?>