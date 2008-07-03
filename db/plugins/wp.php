<?php

// dbscript
global $request, $db;

// wordpress
global $blogdata, $optiondata, $current_user, $user_login, $userdata;
global $user_level, $user_ID, $user_email, $user_url, $user_pass_md5;
global $wpdb, $wp_query, $post, $limit_max, $limit_offset, $comments;
global $req, $wp_rewrite, $wp_version, $openid, $user_identity;

// added the following line to ParanoidHTTPFetcher line 171

// curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

$blogdata = array(
  'name'=>'',
  'description'=>'',
  'wpurl'=>$request->base,
  'url'=>$request->base,
  'atom_url'=>$request->feed_url(),
  'rss_url'=>'',
  'rss2_url'=>'',
  'charset'=>'',
  'html_type'=>'',
  'stylesheet_url'=>$request->base."views/style.css",
  'pingback_url'=>$request->base,
  'template_url'=>$request->base."views"
);

$optiondata = array(
  'xrds_simple'=>array('oauth'=>true,'main'=>true),
  'oauth_services'=>array(),
  'oauth_version'=>0.12,
  'upload_path'=>'',
  'oid_enable_approval'=>true,
  'oid_enable_commentform'=>true,
  'home'=>$request->base,
  'comment_registration'=>true,
  'siteurl'=>$request->base,
  'posts_per_page'=>20,
  'prologue_recent_projects'=>''
);

define('OBJECT', 'OBJECT', true);
define('ARRAY_A', 'ARRAY_A', false);
define('ARRAY_N', 'ARRAY_N', false);


$wp_version = 2.6;
$wpdb = new wpdb();
$wp_query = new wpquery();
$post = new wppost();
$limit_max = get_option( 'posts_per_page' );
$limit_offset = 0;
$comments = false;
$user_ID = false;
$req = false;


class wpdb {
  
  var $base_prefix;
  var $prefix;
  var $show_errors;
  var $dbh;
  var $result;
  var $last_result;
  var $rows_affected;
  var $insert_id;
  var $col_info;
  var $posts;
  
  function wpdb() {
    $this->posts = 'posts';
    $this->col_info = array();
    $this->last_result = array();
    $this->base_prefix = "";
    $this->prefix = "";
    $this->show_errors = false;
    global $db;
    $this->dbh =& $db->conn;
  }
  
  /**
   * Escapes content for insertion into the database, for security
   *
   * @param string $string
   * @return string query safe string
   */
  function escape($string) {
    global $db;
    return $db->escape_string( $string );
  }
  
  function hide_errors() {
    return true;
  }
  
  /**
   * Get one variable from the database
   * @param string $query (can be null as well, for caching, see codex)
   * @param int $x = 0 row num to return
   * @param int $y = 0 col num to return
   * @return mixed results
   */
  function get_var($query=null, $x = 0, $y = 0) {
    $pos = strpos($query,"SHOW TABLES");
    if (!($pos === false)) return true;
    if ( $query )
      $this->query($query);
    if ( $this->last_result[$y] ) {
      $values = array_values(get_object_vars($this->last_result[$y]));
    } else {
      echo "<BR><BR>QUERY FAILED -- ".$query."<BR><BR>";
    }
    return (isset($values[$x]) && $values[$x]!=='') ? $values[$x] : null;
  }

  /**
   * Gets one column from the database
   * @param string $query (can be null as well, for caching, see codex)
   * @param int $x col num to return
   * @return array results
   */
  function get_col($query = null , $x = 0) {
    if ( $query )
      $this->query($query);

    $new_array = array();
    // Extract the column values
    for ( $i=0; $i < count($this->last_result); $i++ ) {
      $new_array[$i] = $this->get_var(null, $x, $i);
    }
    return $new_array;
  }

  /**
   * Get one row from the database
   * @param string $query
   * @param string $output ARRAY_A | ARRAY_N | OBJECT
   * @param int $y row num to return
   * @return mixed results
   */
  function get_row($query = null, $output = OBJECT, $y = 0) {
    if ( $query )
      $this->query($query);
    else
      return null;
    if ( !isset($this->last_result[$y]) )
      return null;
    if ( $output == OBJECT ) {
      return $this->last_result[$y] ? $this->last_result[$y] : null;
    } elseif ( $output == ARRAY_A ) {
      return $this->last_result[$y] ? get_object_vars($this->last_result[$y]) : null;
    } elseif ( $output == ARRAY_N ) {
      return $this->last_result[$y] ? array_values(get_object_vars($this->last_result[$y])) : null;
    } else {
      $this->print_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
    }
  }


/**
   * Return an entire result set from the database
   * @param string $query (can also be null to pull from the cache)
   * @param string $output ARRAY_A | ARRAY_N | OBJECT
   * @return mixed results
   */
  function get_results($query = null, $output = OBJECT) {
    if ( $query )
      $this->query($query);
    else
      return null;
    if ( $output == OBJECT ) {
      return $this->last_result;
    } elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
      if ( $this->last_result ) {
        $i = 0;
        foreach( $this->last_result as $row ) {
          $new_array[$i] = (array) $row;
          if ( $output == ARRAY_N ) {
            $new_array[$i] = array_values($new_array[$i]);
          }
          $i++;
        }
        return $new_array;
      } else {
        return null;
      }
    }
  }


  // ==================================================================
  //  Basic Query  - see docs for more detail

  function query($query) {
    $return_val = 0;
    
    $pos = strpos($query,"update comments");
    if (!($pos === false))
      return true;

    $pos = strpos($query,"update usermeta");
    if (!($pos === false))
      return true;
    global $db;
    $this->result = $db->get_result($query);
    if ( preg_match("/^\\s*(insert|delete|update|replace) /i",$query) ) {
      $this->rows_affected = $db->affected_rows($db->conn);
      if ( preg_match("/^\\s*(insert|replace) /i",$query) ) {
        // todo -- pass the table and pkfield to last_insert_id
        //$this->insert_id = last_insert_id( $this->result, $pkfield, $table );
      }
      $return_val = $this->rows_affected;
    } else {
      $i = 0;
      $resultfields = $db->num_fields($this->result);
      while ($i < $resultfields ) {
        // todo -- figure out how to make a pg_fetch_field
        $this->col_info[$i] = $db->fetch_field($this->result,$i);
        $i++;
      }
      $num_rows = 0;
      while ( $row = $db->fetch_object($this->result) ) {
        $this->last_result[$num_rows] = $row;
        $num_rows++;
      }
      $this->num_rows = $num_rows;
      $return_val = $this->num_rows;
    }
    return $return_val;
  }


}

function get_bloginfo( $var ) {
  global $blogdata;
  if (isset($blogdata[$var]))
    return $blogdata[$var];
  return "";
} 

function add_option( $opt, $newval ) {
  global $optiondata;
  $optiondata[$opt] = $newval;
}


class wppost {
  var $post_password = "";
  var $comment_status = "open";
  function wppost() {
  }
}

class wpcomment {
  var $user_id = 0;
  var $comment_author_email = "";
  var $comment_approved = false;
  function wpcomment() {
  }
}

function update_option( $opt, $newval ) {
  global $optiondata;
  $optiondata[$opt] = $newval;
}

class usermeta {
  
  var $ID = 0;
  var $oauth_consumers = array();
  var $has_openid = true;
  
  function usermeta($arr) {
    $this->ID = $arr['ID'];
    $this->oauth_consumers = $arr['oauth_consumers'];
    $this->has_openid = $arr['has_openid'];
  }
  
}

class WP_User {

  var $ID = 0;
  var $user_id = 0;
  var $user_email = "";
  var $first_name = "";
  var $last_name = "";
  
  var $data;
  var $user_login;
  var $user_level;
  var $user_url;
  var $user_pass;
  var $display_name;

  function WP_User( $uid, $name = "" ) {
    $this->ID = $uid;
    $this->user_id = $uid;
    $this->first_name = $name;
    $this->data = new usermeta(array(
      'ID'=>$uid,
      'has_openid'=>true,
      'oauth_consumers'=>array(
        'DUMMYKEY'=>array(
          'authorized'=>true,
          'endpoint1'=>'',
          'endpoint2'=>'')
      )
    //      $service = array('authorized' => true);
    //      foreach($services as $k => $v)
    //        if(in_array($k, array_keys($value)))
    //          $service[$k] = $v;
    //      $userdata->oauth_consumers[$key] = $service;
    //    }//end foreach services
    ));
    $this->user_login = '';
    $this->user_level = 0;
    $this->user_url = '';
    $this->user_pass = '';
    $this->display_name = $name;
  
  }
  
  function user_login() {
    
  }
  
  function has_cap($x) {
    return false;
  }
  
}

class dbfield {
  var $name;
  var $type;
  var $size;
  function dbfield() {
  }
}

class wpquery {
  var $in_the_loop = false;
  function get_queried_object() {
    return array();
  }
  function wpquery() {
  }
}

class wp_rewrite {
  function wp_rewrite() {
  }
}

class wptag {
  var $term_id = 0;
  var $count = 0;
  var $name = "";
  function wptag() {
  }
}

function auth_redirect() {
  
}

function nocache_headers() {
  
}

function register_activation_hook() {
  
}

function register_deactivation_hook() {
  
}

function add_filter() {
  
}

function get_currentuserinfo() {
  global $current_user;
  //  if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST )
  //    return false;
  if ( ! empty($current_user) )
    return;
  
  $uid = get_person_id();
  
  if (!$uid)
    authenticate_with_openid();
  
  $user = new WP_User($uid);
  //  if ( empty($_COOKIE[USER_COOKIE]) || empty($_COOKIE[PASS_COOKIE]) ||
  //    !wp_login($_COOKIE[USER_COOKIE], $_COOKIE[PASS_COOKIE], true) ) {
  //    wp_set_current_user(0);
  //    return false;
  //  }
  
  //$user_login = $_COOKIE[USER_COOKIE];
  
  wp_set_current_user($user->ID);
}


function bloginfo( $attr ) {
  global $blogdata;
  echo $blogdata[$attr];
}

function get_option( $opt ) {
  global $optiondata;
  return $optiondata[$opt];
}

function get_userdata( $user_id ) {
  global $userdata;
  return $userdata;
}

function get_usermeta( $user_id, $what ) {
  
  $user = wp_set_current_user($user_id);
  // not logged in, need to do a db search on this user_id and oauth it
  
  //$authed = $authed[$consumer->key];
  //if($authed && $authed['authorized']) {
  //$authed = get_usermeta($userid, 'oauth_consumers');
  return $user->data;
}

function wp_nonce_field( $var ) {
  return $var;
}

function wp_schedule_event( $when, $howoften, $event ) {
  
}

function wp_new_user_notification( $userlogin ) {
  
}

function wp_clearcookie() {
  
}

function wp_setcookie( $userlogin, $md5pass, $var1 = true, $var2 = '', $var3 = '', $var4 = true ) {
  
}

function wp_set_auth_cookie( $userid, $remember ) {
  
}

function wp_set_current_user($id, $name = '') {
  global $current_user;

  if ( isset($current_user) && ($id == $current_user->ID) )
    return $current_user;

  $current_user = new WP_User($id, $name);

  setup_userdata($current_user->ID);

  return $current_user;
}

function setup_userdata($user_id = '') {
  global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_pass_md5, $user_identity;

  if ( '' == $user_id )
    $user = wp_get_current_user();
  else
    $user = new WP_User($user_id);

  //if ( 0 == $user->ID )
  //  return;

  $userdata = $user->data;
  $user_login  = $user->user_login;
  $user_level  = (int) $user->user_level;
  $user_ID  = (int) $user->ID;
  $user_email  = $user->user_email;
  $user_url  = $user->user_url;
  $user_pass_md5  = md5($user->user_pass);
  $user_identity  = $user->display_name;
}

function wp_signon( $u, $p ) {
  //array('user_login'=>'openid', 'user_password'=>'openid')
}

function wp_login( $u, $p ) {
  return true;
}

function wp_nonce_url( $var, $var2 ) {
  return $var;
}

function wp_enqueue_script( $file ) {
  require_once $file;
}

function wp_title() {
  echo "Title:";
}

function wp_head() {
  echo "";
  // show additional page headers
}

function wp_register_sidebar_widget( $var1, $var2, $var3 ) {
  return false;
}

function wp_register_widget_control( $var1, $var2, $var3 ) {
  return false;
}

function trackback_url() {
  echo "#";
}

function update_usermeta() {
  
}

function wp_insert_user( $user_data ) {
  
}

function pings_open() {
  return false;
}

function wp_footer() {
  echo "";
}

function wp_redirect( $url ) {
  redirect_to( $url );
}

function wp_safe_redirect( $url ) {
  redirect_to( $url );
}

function wp_insert_post( $arr ) {
  return false;
}

function wp_list_cats() {
  echo "";
}

function wp_get_current_commenter() {
  return 1;
}

function wp_get_current_user() {
  return new WP_User(get_person_id());
}

function wp_get_archives($type) {
  echo "";
}

function get_header() {
  include('views/header.php');
}

function get_header_image() {
  return "there-is-no-image.jpg";
}

function get_footer() {
  include('views/footer.php');
}

function get_sidebar() {
  include('views/sidebar.php');
}

function get_avatar( $wpcom_user_id, $email, $size, $rating = '', $default = 'http://s.wordpress.com/i/mu.gif' ) {
  echo "";
}

function get_permalink( ) {
  return "#";
}

function get_tags( $arr ) {
  return array();
}

function get_tag_link( $category_id ) {
  return "#";
}

function get_tag_feed_link( $category_id ) {
  return "#";
}

function get_recent_post_ids( $return_as_string = true ) {
  return "";
}

function get_objects_in_term( $category_id, $post_tag ) {
  return array();
}

function get_term( $category_id, $post_tag ) {
  return new wptag();
}

function avatar_by_id( $wpcom_user_id, $size ) {
  return false;
}

function attribute_escape( $value ) {
  return $value;
}

function the_post( ) {
  return false;
  // load next
  // set have_posts to return false
}

function the_permalink() {
  echo "#";
}

function the_time( $format ) {
  return date( $format, strtotime( "now" ));
}

function the_tags( $var1="", $var2="", $var3="" ) {
  echo "";
}

function the_title() {
  return "";
}

function the_author() {
  return "";
}

function the_category() {
  return "";
}

function the_content( $linklabel ) {
  include('views/single.php');
}

function have_posts( ) {
  return false;;
}

function get_author_feed_link( $id ) {
  return "#";
}

function the_author_posts_link( ) {
  echo "#";
}

function get_the_author_ID() {
  return 0;
}

function posts_nav_link() {
  echo "";
}

function language_attributes() {
  echo "";
}

function prologue_recent_projects_widget( $args ) {
  return "";
}

function prologue_recent_projects( $num_to_show = 35, $before = '', $after = '' ) {
  return $before.$after;
}

function prologue_recent_projects_control() {
  return "";
}

function prologue_admin_header_style( ) {
  return "";
}

function _e($t) {
  echo $t;
}

function load_javascript() {
  return "";
}

function register_sidebar() {
  return false;
}

function add_action( $act, $func ) {
  return false;
}

function add_custom_image_header( $var, $name ) {
  return false;
}

function edit_post_link( $post ) {
  return "#";
}

function comments_rss_link() {
  echo "#";
}

function comments_popup_link( $var1, $var2, $var3 ) {
  return "";
}

function comments_number() {
  echo "";
}

function comments_template() {
  include('views/comments.php');
}

function comment_ID() {
  return 0;
}

function comment_author_link( ) {
  return "#";
}

function edit_comment_link( $label ) {
  echo "#";
}

function comment_time( $format ) {
  return the_time($format);
}

function comment_date() {
  return "";
}

function comment_text() {
  return "";
}

function check_admin_referer( $var ) {
  return false;
}

function apply_filters( $pre, $content ) {
  return false;
}

function current_user_can( $action ) {
  return false;
}

function setup_postdata( $post ) {
  return "";
}

function dynamic_sidebar() {
  return false;
}

function single_tag_title( ) {
  echo "";
}

function oauth_callback_url() {
  global $request;
  return $request->url_for( 'oauth_continue' );
}

function oauth_authorize_url($rtoken,$callback) {
  global $request;
  return $request->url_for( 'oauth_token' )."?oauth_token=$rtoken&oauth_callback=".urlencode($callback);
}

function oauth_access_token() {
  global $request;
  return $request->url_for( 'access_token' );
}

function oauth_request_token() {
  global $request;
  return $request->url_for( 'request_token' );
}

function oauth_post_url() {
  
}

function oauth_post_content_type() {
  return "application/x-www-form-urlencoded";
  //application/atom+xml;type=entry
}

function oauth_key() {
  global $request;
  return $request->base;
}

function oauth_secret() {
  return '';
}

$request->connect( 'token_authorize' );
$request->connect( 'oauth_login' );
$request->connect( 'oauth_continue' );
$request->connect( 'access_token' );
$request->connect( 'request_token' );

function access_token() {
  
  global $request;
  
  $store = new OAuthWordpressStore();
  $server = new OAuthServer($store);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
  $server->add_signature_method($sha1_method);
  $server->add_signature_method($plaintext_method);
  $params = array();
  foreach($_GET as $key=>$val) {
    if (!($key == 'access_token'))
      $params[$key] = $val;
  }
  try {
    $req = OAuthRequest::from_request("GET",$request->base,$params);
    $token = $server->fetch_access_token($req);
    print $token.'&xoauth_token_expires='.urlencode($store->token_expires($token));
  } catch (OAuthException $e) {
    header('Content-type: text/plain;', true, 400);
    print($e->getMessage() . "\n\n");
    var_dump($req);
    die;
  } 
  
}

function request_token() {
  
  global $request;
  
  $store = new OAuthWordpressStore();
  $server = new OAuthServer($store);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
  $server->add_signature_method($sha1_method);
  $server->add_signature_method($plaintext_method);
  $params = array();
  foreach($_GET as $key=>$val) {
    if (!($key == 'request_token'))
      $params[$key] = $val;
  }
  try {
    $req = OAuthRequest::from_request("GET",$request->base,$params);
    $token = $server->fetch_request_token($req);
    print $token.'&xoauth_token_expires='.urlencode($store->token_expires($token));
  } catch (OAuthException $e) {
    header('Content-type: text/plain;', true, 400);
    print($e->getMessage() . "\n\n");
    var_dump($req);
    die;
  }
  
}

function token_authorize() {
  global $wpdb;
  global $userdata;
  
  if(!$_REQUEST['oauth_token'] && !$_POST['authorize']) die('No token passed');
  
  $NO_oauth = true;
  //require_once dirname(__FILE__).'/common.inc.php';
  $store = new OAuthWordpressStore();
  
  if(!$_POST['authorize']) {
    $token = $wpdb->escape($_REQUEST['oauth_token']);
    $consumer_key = $store->lookup_token('','request',$token);//verify token
    if(!$consumer_key) die('Invalid token passed');
  }//end if ! POST authorize

  get_currentuserinfo();
  
  //=& get_profile(get_cookie_id());

  if(!$userdata->ID) {

    $redirect_to = oauth_authorize_url($_REQUEST['oauth_token'],$_REQUEST['oauth_callback']);
    header('Location: '.$redirect_to,true,303);
    exit;
  }//end if ! userdata->ID
  
  if($_POST['authorize']) {
    session_start();
    $_REQUEST['oauth_callback'] = $_SESSION['oauth_callback']; unset($_SESSION['oauth_callback']);
    $token = $_SESSION['oauth_token']; unset($_SESSION['oauth_token']);
    $consumer_key = $_SESSION['oauth_consumer_key']; unset($_SESSION['oauth_consumer_key']);
    if($_POST['authorize'] != 'Ok') {
      if($_GET['oauth_callback']) {
        header('Location: '.urldecode($_GET['oauth_callback']),true,303);
      } else {
        //get_header();
        echo '<h2 style="text-align:center;">You chose to cancel authorization.  You may now close this window.</h2>';
        //get_footer();
      }//end if-else callback
      exit;
    }//cancel authorize
    $consumers = $userdata->oauth_consumers ? $userdata->oauth_consumers : array();
    $services = get_option('oauth_services');
    $yeservices = array();
    foreach($services as $k => $v)
      if(in_array($k, array_keys($_REQUEST['services'])))
        $yeservices[$k] = $v;
    $consumers[$consumer_key] = array_merge(array('authorized' => true), $yeservices);//it's an array so that more granular data about permissions could go in here
    $userdata->oauth_consumers = $consumers;
    update_usermeta($userdata->ID, 'oauth_consumers', $consumers);
  }//end if authorize
  
  if($userdata->oauth_consumers && in_array($consumer_key,array_keys($userdata->oauth_consumers))) {
    $store->authorize_request_token($consumer_key, $token, $userdata->ID);
    if($_GET['oauth_callback']) {
      header('Location: '.urldecode($_GET['oauth_callback']),true,303);
    } else {
      //get_header();
      echo '<h2 style="text-align:center;">Authorized!  You may now close this window.</h2>';
      //get_footer();
    }//end if-else callback
    exit;
  } else {
    session_start();//use a session to prevent the consumer from tricking the user into posting the Yes answer
    $_SESSION['oauth_token'] = $token;
    $_SESSION['oauth_callback'] = $_REQUEST['oauth_callback'];
    $_SESSION['oauth_consumer_key'] = $consumer_key;
    //get_header();
    $description = $store->lookup_consumer_description($consumer_key);
    if($description) $description = 'Allow '.$description.' to access your Wordpress account and...';
      else $description = 'Allow the service you came from to access your Wordpress account and...';
    ?>
    <div style="text-align:center;">
      <h2><?php echo $description; ?></h2>
      <form method="post" action=""><div>
        <div style="text-align:left;width:15em;margin:0 auto;">
          <ul style="padding:0px;">
        <?php
          $services = get_option('oauth_services');
          foreach($services as $k => $v)
            echo '<li><input type="checkbox" checked="checked" name="services['.htmlentities($k).']" /> '.$k.'</li>';
        ?>
          </ul>
          <br />
          <input type="submit" name="authorize" value="Ok" />
          <input type="submit" name="authorize" value="No" />
        </div>
      </div></form>
    </div>
    <?php
    //get_footer();
    exit;
  }//end if user has authorized this consumer
  
}

function oauth_login() {
  
  $key = oauth_key();
  $secret = oauth_secret();
  
  global $request;
  
  //omb_version
  //'http://openmicroblogging.org/protocol/0.1'
  
  //omb_listener
  //The identifier URI for the listener.
  
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  
  $consumer = new OAuthConsumer($key, $secret, NULL);
  $rtoken = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', oauth_request_token(), array());
  
  $rtoken->sign_request($sha1_method, $consumer, NULL);
  
  $rtoken = str_replace("?oauth_version","?request_token&oauth_version",$rtoken);
  $curl = curl_init($rtoken);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,TRUE);
  
  $rtoken = curl_exec($curl);
  curl_close($curl);
  
  preg_match('/oauth_token=([^&]*)&oauth_token_secret=([^&]*)/', $rtoken, $rtoken);
  $rtoken_secret = $rtoken[2];
  $rtoken = $rtoken[1];
  if ( !$rtoken ) die ( 'This is a known bug, just go back and try again' );
  $_SESSION['rtoken'] = $rtoken;
  $_SESSION['rtoken_secret'] = $rtoken_secret;
  
  $callback_url = oauth_callback_url();
  
  $auth_url = oauth_authorize_url($rtoken,$callback_url);
  $auth_url = str_replace("?oauth_token","?token_authorize&oauth_token",$auth_url);
  
  header('Location: '.$request->base.$auth_url,true,303);
  exit;
  
}


function oauth_continue( &$vars ) {
  
  extract($vars);
  
  $model =& $db->get_table($_SESSION['resource']);
  $rec = $model->find( $_SESSION['id'] );
  $notice_url = $request->url_for(array( 'resource' => $_SESSION['resource'], 'id' => $_SESSION['id']) );
  
  $rtoken = new OAuthConsumer( $_SESSION['rtoken'], $_SESSION['rtoken_secret'] );
  $atoken = OAuthRequest::from_consumer_and_token($consumer, $rtoken, 'GET', $_SESSION['access_token'], array());
  $atoken->sign_request($sha1_method, $consumer, $rtoken);
  $curl = curl_init($atoken);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,TRUE);
  $atoken = curl_exec($curl);
  curl_close($curl);
  preg_match('/oauth_token=([^&]*)&oauth_token_secret=([^&]*)/', $atoken, $atoken);
  $atoken = new OAuthConsumer($atoken[1], $atoken[2]);
  
  $service = OAuthRequest::from_consumer_and_token($consumer, $atoken, 'POST', $request->url_for($_SESSION['post_notice']), array());
  $service->sign_request($sha1_method, $consumer, $atoken);
  
  // debug
  header('Content-Type: text/plain');
  
  $curl = curl_init($service);
  
  // debug
  echo $service."\n\n";
  
  $postvars = array(
    
    'omb_version' => 'http://openmicroblogging.org/protocol/0.1',
    //'http://openmicroblogging.org/protocol/0.1'.
    
    'omb_listenee' => $request->base,
    //The identifier URI for the listenee.
    
    'omb_notice' => $notice_url,
    //The notice URI.
    
    'omb_notice_content' => $rec->title,
    //The content of the notice. No maximum, but 140 chars is recommended.
    
    'omb_notice_url' => $notice_url,
    //The URL of the notice, if the notice is retrievable.
    
    'omb_notice_license' => '',
    //The URL of the license for the notice, if different from the listenee's default license.
    
    'omb_seealso' => '', // if has_post_body, put a linky in here?
    //URL of additional content for the notice; for example, an image, video, or audio file.
    
    'omb_seealso_disposition' => '',
    //One of 'link' or 'inline', to recommend how the extra data should be shown. Default 'link'.
    
    'omb_seealso_mediatype' => '',
    //Internet Media Type of the see-also data. Advisory, probably shouldn't be trusted.
    
    'omb_seealso_license' => ''
    //The URL of the license for the seealso, if different from the listenee's default license.
    
  );
  
  $notice = '';
  
  foreach( $postvars as $key => $val )
    $notice .= $key . "=" . $val;
  
  curl_setopt( $curl, CURLOPT_POST, TRUE );
  curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-Type: ".oauth_post_content_type() ));
  curl_setopt( $curl, CURLOPT_POSTFIELDS, urlencode($notice) );
  curl_setopt( $curl, CURLOPT_USERAGENT, 'dbscript OAuth' );
  curl_setopt( $curl, CURLOPT_HEADER, TRUE );
  curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
  $service = curl_exec($curl);
  curl_close($curl);
  
  // debug
  var_dump($service);
  echo "<BR><BR><BR>";
  
}



?>