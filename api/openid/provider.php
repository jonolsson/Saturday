<?php

// Diffie-Hellman Key Exchange Default Value.
define('OPENID_DH_DEFAULT_MOD', '155172898181473697471232257763715539915724801'.
       '966915404479707795314057629378541917580651227423698188993727816152646631'.
       '438561595825688188889951272158842675419950341258706556549803580104870537'.
       '681476726513255747040765857479291291572334510643245094715007229621094194'.
       '349783925984760375594985848253359305585439638443');

// Constants for Diffie-Hellman key exchange computations.
define('OPENID_DH_DEFAULT_GEN', '2');
define('OPENID_SHA1_BLOCKSIZE', 64);
define('OPENID_RAND_SOURCE', '/dev/urandom');

// OpenID namespace URLs
define('OPENID_NS_2_0', 'http://specs.openid.net/auth/2.0');
define('OPENID_NS_1_1', 'http://openid.net/signon/1.1');
define('OPENID_NS_1_0', 'http://openid.net/signon/1.0');


class api_openid_provider implements api_observable_interface {

    private $observers = array();
    function attach(api_observer_interface $observer) {
        $this->observers[] = $observer;
    }

    function detach(api_observer_interface $observer) {
        $this->observers = array_diff($this->observers, array($observer));
    }

    function notify() {
        foreach($this->observers as $obs) {
            $obs->update($this);
        }
    }

    function endpoint($request = array()) {
        if (count($request) == 0) {
            $request = $this->openid_response();
        }
        print_r($request);
        api_session::set('openid_request', $request);

        if (isset($request['openid.mode'])) {
            switch ($request['openid.mode']) {
                case 'associate':
                    echo "Associate";
                    $this->openid_provider_association_response($request);
                    return;
                case 'checkid_immediate':
                case 'checkid_setup':
                    return $this->openid_provider_authentication_response($request);
                case 'check_authentication':
                    $this->openid_provider_verification_response($request);
                    break;
            }
        }
    }

    function openid_response($str = NULL) {
        $data = array();
  
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $data = $this->openid_get_params($_SERVER['QUERY_STRING']);

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $str = file_get_contents('php://input');

                $post = array();
                if ($str !== false) {
                    $post =  $this->openid_get_params($str);
                }
                $data = array_merge($data, $post);
            }
        }
        return $data;
    }

function openid_get_params($str) {
  $chunks = explode("&", $str);

  $data = array();
  foreach ($chunks as $chunk) {
    $parts = explode("=", $chunk, 2);

    if (count($parts) == 2) {
      list($k, $v) = $parts;
      $data[$k] = urldecode($v);
    }
  }
  return $data;
}

    /**
 * Create an association with an RP
 *
 * @param array $request
 */
function openid_provider_association_response($request) {
  // module_load_include('inc', 'openid');
  
  $session_type = $request['openid.session_type'];
  $assoc_type = $request['openid.assoc_type'];
  $dh_modulus = (isset($request['openid.dh_modulus']) ? $request['openid.dh_modulus'] : null);
  $dh_gen = (isset($request['openid.dh_gen']) ? $request['openid.dh_gen'] : null);
  $dh_consumer_public = (isset($request['openid.dh_consumer_public']) ? $request['openid.dh_consumer_public'] : null);

  $assoc_handle = $this->openid_provider_nonce();
// mayby something for config? TODO  $expires_in = variable_get('openid_provider_assoc_expires_in', '3600');
  $expires_in = '3600';

  // CLEAR STALE ASSOCIATIONS
//TODO  db_query("DELETE FROM {openid_provider_association} WHERE created + expires_in < %d", time());
  
  $response = array(
    'ns' => OPENID_NS_2_0,
    'session_type' => $session_type,
    'assoc_handle' => $assoc_handle,
    'assoc_type' => $assoc_type,
    'expires_in' => $expires_in
  );
  
  if ($session_type == 'DH-SHA1'
        || (($session_type == '' || $session_type == 'no-encryption')
            && $assoc_type == 'HMAC-SHA1')) {
    $num_bytes = 20;
    $algo = 'sha1';
  }
  elseif ($session_type == 'DH-SHA256'
        || (($session_type == '' || $session_type == 'no-encryption')
            && $assoc_type == 'HMAC-SHA256')) {
    $num_bytes = 32;
    $algo = 'sha256';
  }
  $secret = $this->openid_get_bytes($num_bytes);
  if ($session_type == '' || $session_type == 'no-encryption') {
    $mac_key = base64_encode(hash_hmac($algo, $response['assoc_handle'], $secret, true));
    $response['mac_key'] = $mac_key;
  }
  else {
    $dh_assoc = $this->openid_provider_dh_assoc($request, $secret, $algo);
    $mac_key = base64_encode($secret);
    $response['dh_server_public'] = $dh_assoc['dh_server_public'];
    $response['enc_mac_key'] = $dh_assoc['enc_mac_key'];
  }
  // Save the association for reference when dealing
  // with future requests from the same RP.
  // TODO db_query("INSERT INTO {openid_provider_association} (assoc_handle, assoc_type, session_type, mac_key, created, expires_in) VALUES ('%s', '%s', '%s', '%s', %d, %d)",
          // $assoc_handle, $assoc_type, $session_type, $mac_key, time(), $expires_in);

  $message = $this->openid_create_message($response);

  //set_header('HTTP/1.1 200 OK');
  //set_header("Content-Type: text/plain");
 // TODO set header
  // print $message;
  return $message;
}

function openid_provider_nonce() {
  // YYYY-MM-DDThh:mm:ssTZD UTC, plus some optional extra unique chars
  return gmstrftime('%Y-%m-%dT%H:%M:%SZ') .
    chr(mt_rand(0, 25) + 65) .
    chr(mt_rand(0, 25) + 65) .
    chr(mt_rand(0, 25) + 65) .
    chr(mt_rand(0, 25) + 65);
}

function openid_provider_dh_assoc($request, $secret, $algo = 'sha1') {
  if (empty($request['openid.dh_consumer_public'])) {
    return FALSE;
  }
  
  if (isset($request['openid.dh_modulus'])) {
    $mod = openid_dh_base64_to_long($request['openid.dh_modulus']);
  }
  else {
    $mod = OPENID_DH_DEFAULT_MOD;
  }

  if (isset($request['openid.dh_gen'])) {
    $gen = openid_dh_base64_to_long($request['openid.dh_gen']);
  }
  else {
    $gen = OPENID_DH_DEFAULT_GEN;
  }

  $r = _openid_dh_rand($mod);
  $private = _openid_provider_add($r, 1);
  $public = _openid_provider_powmod($gen, $private, $mod);
  
  $cpub = openid_dh_base64_to_long($request['openid.dh_consumer_public']);
  $shared = _openid_provider_powmod($cpub, $private, $mod);
  $mac_key = _openid_provider_dh_xorsecret($shared, $secret, $algo);
  $enc_mac_key = base64_encode($mac_key);  
  $spub64 = openid_dh_long_to_base64($public);
  return array(
    'dh_server_public' => $spub64,
    'enc_mac_key' => $enc_mac_key
    );
}

function _openid_get_bytes($num_bytes) {
  static $f = null;
  $bytes = '';
  if (!isset($f)) {
    $f = @fopen(OPENID_RAND_SOURCE, "r");
  }
  if (!$f) {
    // pseudorandom used
    $bytes = '';
    for ($i = 0; $i < $num_bytes; $i += 4) {
      $bytes .= pack('L', mt_rand());
    }
    $bytes = substr($bytes, 0, $num_bytes);
  }
  else {
    $bytes = fread($f, $num_bytes);
  }
  return $bytes;
}


/**
 * Create a serialized message packet as per spec: $key:$value\n .
 */
function openid_create_message($data) {
  $serialized = '';

  foreach ($data as $key => $value) {
    if ((strpos($key, ':') !== FALSE) || (strpos($key, "\n") !== FALSE) || (strpos($value, "\n") !== FALSE)) {
      return null;
    }
    $serialized .= "$key:$value\n";
  }
  return $serialized;
}

function openid_get_bytes($num_bytes) {
  static $f = null;
  $bytes = '';
  if (!isset($f)) {
    $f = @fopen(OPENID_RAND_SOURCE, "r");
  }
  if (!$f) {
    // pseudorandom used
    $bytes = '';
    for ($i = 0; $i < $num_bytes; $i += 4) {
      $bytes .= pack('L', mt_rand());
    }
    $bytes = substr($bytes, 0, $num_bytes);
  }
  else {
    $bytes = fread($f, $num_bytes);
  }
  return $bytes;
}
function _openid_dh_binary_to_long($str) {
  $bytes = array_merge(unpack('C*', $str));

  $n = 0;
  foreach ($bytes as $byte) {
    $n = bcmul($n, pow(2, 8));
    $n = bcadd($n, $byte);
  }

  return $n;
}

function openid_dh_long_to_base64($str) {
  return base64_encode(_openid_dh_long_to_binary($str));
}


function openid_dh_base64_to_long($str) {
  $b64 = base64_decode($str);

  return _openid_dh_binary_to_long($b64);
}


function _openid_dh_rand($stop) {
  static $duplicate_cache = array();

  // Used as the key for the duplicate cache
  $rbytes = _openid_dh_long_to_binary($stop);

  if (array_key_exists($rbytes, $duplicate_cache)) {
    list($duplicate, $nbytes) = $duplicate_cache[$rbytes];
  }
  else {
    if ($rbytes[0] == "\x00") {
      $nbytes = strlen($rbytes) - 1;
    }
    else {
      $nbytes = strlen($rbytes);
    }

    $mxrand = bcpow(256, $nbytes);

    // If we get a number less than this, then it is in the
    // duplicated range.
    $duplicate = bcmod($mxrand, $stop);

    if (count($duplicate_cache) > 10) {
      $duplicate_cache = array();
    }

    $duplicate_cache[$rbytes] = array($duplicate, $nbytes);
  }

  do {
    $bytes = "\x00". _openid_get_bytes($nbytes);
    $n = _openid_dh_binary_to_long($bytes);
    // Keep looping if this value is in the low duplicated range.
  } while (bccomp($n, $duplicate) < 0);

  return bcmod($n, $stop);
}

function _openid_dh_long_to_binary($long) {
  $cmp = bccomp($long, 0);
  if ($cmp < 0) {
    return FALSE;
  }

  if ($cmp == 0) {
    return "\x00";
  }

  $bytes = array();

  while (bccomp($long, 0) > 0) {
    array_unshift($bytes, bcmod($long, 256));
    $long = bcdiv($long, pow(2, 8));
  }

  if ($bytes && ($bytes[0] > 127)) {
    array_unshift($bytes, 0);
  }

  $string = '';
  foreach ($bytes as $byte) {
    $string .= pack('C', $byte);
  }

  return $string;
}

function _openid_provider_add($a, $b) {
  if (function_exists('gmp_add')) {
    return gmp_add($a, $b);
  }
  else if (function_exists('bcadd')) {
    return bcadd($a, $b);
  }
}


function _openid_provider_powmod($base, $exp, $mod) {
  if (function_exists('gmp_powm')) {
    return gmp_powm($base, $exp, $mod);
  }
  else if (function_exists('bcpowmod')) {
    return bcpowmod($base, $exp, $mod);
  }
}


/**
 * Is copy of _opend_dh_xorsecret() but uses PHP5 hash() function. Should be merged back into openid client
 * for D7.
 *
 * @param long $shared
 * @param string $secret
 * @param string $algo
 * @return binary string
 */
function _openid_provider_dh_xorsecret($shared, $secret, $algo = 'sha1') {
  $dh_shared_str = _openid_dh_long_to_binary($shared);
  $sha1_dh_shared = hash($algo, $dh_shared_str, true);
  $xsecret = "";
  for ($i = 0; $i < strlen($secret); $i++) {
    $xsecret .= chr(ord($secret[$i]) ^ ord($sha1_dh_shared[$i]));
  }
  return $xsecret;
}


function set_header($header = NULL) {
  // We use an array to guarantee there are no leading or trailing delimiters.
  // Otherwise, header('') could get called when serving the page later, which
  // ends HTTP headers prematurely on some PHP versions.
  static $stored_headers = array();

  if (strlen($header)) {
    header($header);
    $stored_headers[] = $header;
  }
  return implode("\n", $stored_headers);
}

/**
 * Generate an authentication response
 *
 * @param 
 */
function openid_provider_authentication_response($request) {
  //global $user;

  // If the user is not yet logged in, redirect to the login page before continuing.
  $user = api_session::get('user');
  if (!$user) {
        //$_SESSION['openid_provider']['request'] = $request;
      // Set in endpoint method
      // api_session::set('openid_request', $request);
        $this->openid_redirect_http('/login');
  }

  // Determine the realm (openid.trust_root in 1.x)
  $realm = (empty($request['openid.realm'])) ? $request['openid.trust_root'] : $request['openid.realm'];
  // Check if realm is OK?
  if (!$this->check_realm($realm)) {
      $this->openid_redirect_http('/error');
  }

  // Check for a directed identity request.
  if ($request['openid.identity'] == 'http://specs.openid.net/auth/2.0/identifier_select') {
    //$identity = url(openid_provider_user_url($user->uid), array('absolute' => TRUE));
      $identity = 'http://local.openid_provider/user/'.$user['id'].'/identity';
  }
  else {
    $identity = $request['openid.identity'];
    if ($identity != url(openid_provider_user_url($user['id']), array('absolute' => TRUE))) {
      $response = openid_provider_authentication_error($request['openid.mode']);
      openid_redirect($request['openid.return_to'], $response);
    }
  }

  $response = array(
    'openid.ns' => OPENID_NS_2_0,
    'openid.mode' => 'id_res',
    'openid.op_endpoint' => 'http://local.openid_provider/openid/provider', //url('openid/provider', array('absolute' => TRUE)),
    'openid.identity' => $identity,
    'openid.claimed_id' => $identity,
    'openid.return_to' => $request['openid.return_to'],
    'openid.response_nonce' => $this->openid_provider_nonce(),
    'openid.assoc_handle' => $request['openid.assoc_handle'],
    'openid.sreg.nickname' => $user['username'], //name,
    'openid.sreg.email' => $user['email'] //"jon@studioett.com", //  TODO $user->mail
  );

  // Is the RP requesting Immediate or Indirect mode?
  if ($request['openid.mode'] == 'checkid_immediate') {
    // TODO
  }
  
  $parts = parse_url($request['openid.return_to']);
  if (isset($parts['query'])) {
    $query = $parts['query'];
    $q = $this->openid_get_params($query);
    foreach ($q as $key => $val) {
      $response[$key] = $val;
    }
  }

  // calling hook_openid so we can do response parsing and send any pertinent data back to the user
  // TODO ???? //$response = array_merge($response, module_invoke_all('openid_provider', 'response', $response, $request));

  // Skipping trust step, if the realm is ok then its trusted. 
  $rp = $this->openid_provider_rp_load($user['id'], $realm);
  if (empty($rp)) {
      echo "Create rp";
    $this->openid_provider_rp_save($user['id'], $realm, TRUE);
  }
  $rp = $this->openid_provider_rp_load($user['id'], $realm);
  echo "\nrp: "; 
  print_r($rp);
  echo "\n";
  if ($rp) { //$rp->auto_release) {
    $response = $this->openid_provider_sign($response);
    //$this->openid_provider_rp_save($user['id'], $realm, TRUE);
    return $this->openid_redirect_http($response['openid.return_to'], $response); 
  }
  else {
    // Unset global post variable, otherwise FAPI will assume it has been 
    // submitted against openid_provider_form.
    unset($_POST);
    //return drupal_get_form('openid_provider_form', $response, $realm);
    //$this->openid_redirect_http('/trust');
    throw new Exception ("Association error");
  }
}

/**
 * Check if realm is approved to authenticate for
 */
function check_realm($realm) {
      $db = api_database::factory();
      $stmt = $db->prepare("SELECT * FROM realms WHERE realm = ?");
      $stmt->execute(array($realm));
      $result = $stmt->fetch(PDO::FETCH_OBJ);
      return $result;
}    

function openid_provider_rp_load($uuid, $realm = NULL) {
  if ($realm) {
    //return db_fetch_object(db_query("SELECT * FROM {openid_provider_relying_party} WHERE uid=%d AND realm='%s'", $uid, $realm));
      $db = api_database::factory();
      $stmt = $db->prepare("SELECT * FROM relying_party WHERE uuid = ? AND realm = ?");
      $stmt->execute(array($uuid, $realm));
      echo $realm;
      $result = $stmt->fetch(PDO::FETCH_OBJ);
      print_r($result);
      //$mock = new stdClass();
      //$mock->auto_release = 1;
    return $result;
  }
  else {
    $rps = array();
    $result = db_query("SELECT * FROM {openid_provider_relying_party} WHERE uid=%d ORDER BY last_time DESC", $uid);
    while ($rp = db_fetch_object($result)){
      $rps[] = $rp;
    }
    return $rps;
  }
}
  
function openid_provider_rp_save($uuid, $realm, $auto_release = FALSE) {
  // TODO
    /*$rpid = db_result(db_query("SELECT rpid FROM {openid_provider_relying_party} WHERE uid=%d AND realm='%s'", $uid, $realm));
  if ($rpid) {
    db_query("UPDATE {openid_provider_relying_party} SET auto_release=%d, last_time=%d WHERE rpid=%d", $auto_release, time(), $rpid);  
     */
      $db = api_database::factory();
      $stmt = $db->prepare("SELECT * FROM relying_party WHERE uuid = ? AND realm = ?");
      $stmt->execute(array($uuid, $realm));
      $result = $stmt->fetch(PDO::FETCH_OBJ);
    if (!empty($result)) {
        echo "Update";
        $db->exec("UPDATE relying_party SET auto_release=$auto_release, lasttime=".time()." where id = ".$result->id);
    } else {
        echo "insert";
        $auto_release = true;
        $db->exec("insert into relying_party (uuid, realm, firsttime, lasttime, auto_release) VALUES ($uuid, '$realm', '".time()."', '".time()."', $auto_release)");
        print_r( $db->errorInfo());
        //db_query("INSERT INTO {openid_provider_relying_party} (uid, realm, first_time, last_time, auto_release) VALUES (%d, '%s', %d, %d, %d)", $uid, $realm, time(), time(), $auto_release);
    }
}

function openid_provider_sign($response) {
  //module_load_include('inc', 'openid');
  
  $also_sign = array();
  $parts = parse_url($response['openid.return_to']);
  if (isset($parts['query'])) {
    $query = $parts['query'];
    $q = $this->openid_get_params($query);
    foreach ($q as $key => $val) {
      $also_sign[] = $key;
      $response[$key] = $val;
    }
  }

  $signed_keys = array('op_endpoint', 'return_to', 'response_nonce', 'assoc_handle', 'identity', 'claimed_id');
// TODO ??  $signed_keys = array_merge($signed_keys, module_invoke_all('openid_provider', 'signed', $response));
  $response['openid.signed'] = implode(',', $signed_keys);
  
  // Use the request openid.assoc_handle to look up
  // how this message should be signed, based on
  // a previously-created association.
  //$assoc = db_fetch_object(db_query("SELECT * FROM {openid_provider_association} WHERE assoc_handle = '%s'", 
  //                                  $response['openid.assoc_handle']));
  $mock_assoc = new stdClass();
  $mock_assoc->mac_key = 'yK0vZ++XNXp3KIaoDw3FFvCsN1g=';
  $mock_assoc->assoc_type = 'HMAC-SHA1';
//  $mock_assoc->
  

  // Generate signature for this message
  $response['openid.sig'] = $this->openid_provider_signature($mock_assoc, $response, $signed_keys);
  return $response;
}

/**
 * Is copy from openid client but uses PHP5 only hash_hmac() function.
 *
 * @param object $association
 * @param array $message_array
 * @param array $keys_to_sign
 * @return string
 */
function openid_provider_signature($association, $message_array, $keys_to_sign) {
  $signature = '';
  $sign_data = array();
  foreach ($keys_to_sign as $key) {
    if (isset($message_array['openid.'. $key])) {
      $sign_data[$key] = $message_array['openid.'. $key];
    }
  }
  $message = $this->openid_create_message($sign_data);
  $secret = base64_decode($association->mac_key);
  $signature = hash_hmac($association->assoc_type == 'HMAC-SHA256' ? 'sha256' : 'sha1', $message, $secret, true);
  return base64_encode($signature);
}
function _openid_response($str = NULL) {
  $data = array();
  
  if (isset($_SERVER['REQUEST_METHOD'])) {
    $data = _openid_get_params($_SERVER['QUERY_STRING']);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $str = file_get_contents('php://input');

      $post = array();
      if ($str !== false) {
        $post = _openid_get_params($str);
      }

      $data = array_merge($data, $post);
    }
  }

  return $data;
}


function _openid_get_params($str) {
  $chunks = explode("&", $str);

  $data = array();
  foreach ($chunks as $chunk) {
    $parts = explode("=", $chunk, 2);

    if (count($parts) == 2) {
      list($k, $v) = $parts;
      $data[$k] = urldecode($v);
    }
  }
  return $data;
}

/**
 * Performs an HTTP 302 redirect (for the 1.x protocol).
 */
function openid_redirect_http($url, $message) {
  $query = array();
  foreach ($message as $key => $val) {
    $query[] = $key .'='. urlencode($val);
  }

  $sep = (strpos($url, '?') === FALSE) ? '?' : '&';
  header('Location: '. $url . $sep . implode('&', $query), TRUE, 302);
  exit;
}



}

