<?php

// Diffie-Hellman Key Exchange Default Value.
define('OPENID_DH_DEFAULT_MOD', '155172898181473697471232257763715539915724801'.
       '966915404479707795314057629378541917580651227423698188993727816152646631'.
       '438561595825688188889951272158842675419950341258706556549803580104870537'.
       '681476726513255747040765857479291291572334510643245094715007229621094194'.
       '349783925984760375594985848253359305585439638443');


define('OPENID_DH_DEFAULT_GEN', '2');
define('OPENID_SHA1_BLOCKSIZE', 64);
define('OPENID_RAND_SOURCE', '/dev/urandom');

// OpenID namespace URLs
define('OPENID_NS_2_0', 'http://specs.openid.net/auth/2.0');
define('OPENID_NS_1_1', 'http://openid.net/signon/1.1');
define('OPENID_NS_1_0', 'http://openid.net/signon/1.0');

$xrds_open_elements = array();
$xrds_services = array();
$xrds_current_service = array();

class api_openid_rely {

    function __construct() {

    }

    function begin($claimed_id, $return_to, $values) {
        // one is enough $claimed_id = $this->normalize($claimed_id);

        $claimed_id = $this->normalize($claimed_id);

        $services = $this->descovery($claimed_id);
        if (count($services) == 0) {
            echo 'Not a valid openid identifier. Examin your spelling and try again';
            return;
        }

        echo "Store in session\r\n";
        // Store discovered information in Session
        $_SESSION['openid']['service'] = $services[0];
        $_SESSION['openid']['claimed_id'] = $claimed_id;
        $_SESSION['openid']['user_login_values'] = $values;

        $openid_endpoint = $services[0]['uri'];
        $assoc_handle = $this->association($openid_endpoint);

        // Time to acctualy request authentication
        // First LocalID, Delegate othervise fallback on $claimed_id.
        if (!empty($services[0]['localid'])) {
            $identity = $services[0]['localid'];
        } else if (!empty($services[0]['delegate'])) {
            $identity = $services[0]['delegate'];
        } else {
            $identity = $claimed_id;
        }

        if (isset($services[0]['types']) && is_array($services[0]['types']) && in_array(OPENID_NS_2_0 .'/server', $services[0]['types'])) {
            $claimed_id = $identity = 'http://specs.openid.net/auth/2.0/identifier_select';
        }
        $authn_request = $this->authentication_request($claimed_id, $identity, $return_to, $assoc_handle, $services[0]['version']);

        if ($services[0]['version'] == 2) {
            //     openid_redirect($openid_endpoint, $authn_request);
            echo "Redirect: $openid_endpoint";
            $this->redirect_http($openid_endpoint, $authn_request);
        } else {
        //     openid_redirect_http($openid_endpoint, $authn_request);
            echo "Redirect: $openid_endpoint";
        }
        $services = openid_descovery($claimed_id);
        if (count($services) == 0) {
            echo 'Not a valid openid identifier. Examin your spelling and try again';
            return;
        }

        echo "Store in session\r\n";
        // Store discovered information in Session
        $_SESSION['openid']['service'] = $services[0];
        $_SESSION['openid']['claimed_id'] = $claimed_id;
        $_SESSION['openid']['user_login_values'] = $form_values;

        $openid_endpoint = $services[0]['uri'];
        $assoc_handle = openid_association($openid_endpoint);

        // Time to acctualy request authentication
        // First LocalID, Delegate othervise fallback on $claimed_id.
        if (!empty($services[0]['localid'])) {
            $identity = $services[0]['localid'];
        } else if (!empty($services[0]['delegate'])) {
            $identity = $services[0]['delegate'];
        } else {
            $identity = $claimed_id;
        }

        if (isset($services[0]['types']) && is_array($services[0]['types']) && in_array(OPENID_NS_2_0 .'/server', $services[0]['types'])) {
            $claimed_id = $identity = 'http://specs.openid.net/auth/2.0/identifier_select';
        }
        $authn_request = $this->authentication_request($claimed_id, $identity, $return_to, $assoc_handle, $services[0]['version']);

        if ($services[0]['version'] == 2) {
       //     openid_redirect($openid_endpoint, $authn_request);
            echo "Redirect: $openid_endpoint";
            $this->redirect_http($openid_endpoint, $authn_request);
        } else {
       //     openid_redirect_http($openid_endpoint, $authn_request);
            echo "Redirect: $openid_endpoint";
        }
    }


function descovery($claimed_id) {
    $services = array();
    $xrds_url = $claimed_id;
    $url = parse_url($xrds_url);
    print_r($url);
    if ($url['scheme'] == 'http' || $url['scheme'] == 'https') {
        //$headers = array('Accept' => 'Application/xrds+xml');
        $headers = array();
        $result = $this->http_request($xrds_url, $headers);
        echo "\r\n\r\nResult: \r\n";
        print_r($result);
        echo "\r\n\r\nEnd\r\n";
        if (!isset($result->error)) {
            if (isset($result->headers['Content-Type']) && preg_match("/application\/xrds\+xml/", $result->headers['Content-Type'])) {
                // Parse XML document to find URL
                // $service = xrds_parse($result->data);
            } else {
                $xrds_url = null;
                if (isset($result->headers['X-XRDS-Location'])) {
                    $xrds_url = $result->headers['X-XRDS-Location'];
                } else { 
                    // Look for meta http-equiv link in HTML head
                    $xrds_url = openid_meta_httpequiv('X-XRDS-Location', $result->data);
                }
                if (!empty($xrds_url)) {
                    //$headers = array('Accept' => 'application/xrds+xml');
                    $xrds_result = $this->http_request($xrds_url, $headers);
                    if (!isset($xrds_result->error)) {
                        $services = $this->xrds_parse($xrds_result->data);
                    }
                }
            }
            if (count($services) == 0) {
                // Look for 2.0 links
                $uri = openid_link_href('openid2.provider', $result->data);
                $delegate = openid_link_href('openid2.local_id', $result->data);
                $version = 2;

                // 1.0 Links
                if (empty($url)) {
                    $uri = _openid_link_href('openid.provider', $result->data);
                    $delegate = openid_link_href('openid.local_id', $result->data);
                    $version = 1;
                }
                if (!empty($uri)) {
                    $services[] = array('uri' => $uri, 'delegate' => $delegate, 'version' => $version);
                }
            }
        }
    }
    return $services;
}


/**
 * Perform an HTTP request.
 *
 * This is a flexible and powerful HTTP client implementation. Correctly handles
 * GET, POST, PUT or any other HTTP requests. Handles redirects.
 *
 * @param $url
 *   A string containing a fully qualified URI.
 * @param $headers
 *   An array containing an HTTP header => value pair.
 * @param $method
 *   A string defining the HTTP request to use.
 * @param $data
 *   A string containing data to include in the request.
 * @param $retry
 *   An integer representing how many times to retry the request in case of a
 *   redirect.
 * @return
 *   An object containing the HTTP request headers, response code, headers,
 *   data and redirect status.
 */
function http_request($url, $headers = array(), $method = 'GET', $data = null, $retry = 3) {
    $result = new stdClass();
    print_r($result);
    echo $url;
    $uri = parse_url($url);

    if ($uri == false) {
        $result->error = "Unable to parse URL";
        $result->code = -1001;
        return $result;
    }
    if (!isset($uri['scheme'])) {
        $result->error = "Missing schema";
        $result->code = -1002;
        return $result;
    }

    switch ($uri['scheme']) {
    case 'http':
        $port = isset($uri['port']) ? $uri['port'] : 80;
        $host = $uri['host'] . ($port != 80 ? ':'. $port : '');
        $fp = fsockopen($uri['host'], $port, $errno, $errstr, 15);
        break;
    case 'https':
        $port = isset($uri['port']) ? $uri['port'] : 443;
        $host = $uri['host'] . ($port != 443 ? ':'. $port : '');
        $fp = fsockopen($uri['host'], $port, $errno, $errstr, 20);
        break;
    default:
        $result->error = 'invalid schema '. $url['scheme'];
        $result->code = -1003;
        return $result;
    }

    // Make sure the socket opened properly.
    if (!$fp) {
        $result->error = trim($errstr);
        $result->code = -1003;
        return $result;
    }

    // Construct the path to act on.
    $path = isset($uri['path']) ? $uri['path'] : '/';
    if (isset($uri['query'])) {
        $path .= '?'. $uri['query'];
    }

    //Create http request.
    $defaults = array(
        // RFC 2616: "non-standard ports MUST, default ports May be included".
        // we don't add the port to prevent from breaking rewrite rules checking the
        // host that do not take in account the port number.
        'Host' => "Host: $host",
        'User-Agent' => 'User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; sv-SE; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3'
    );
    
    // Only add Content-Length if we actually have any content or if it is a POST
    // or PUT request. Some non-standard servers get confused by Content-Length in
    // at least HEAD/GET requests, and Squid always requires Content-Length in POST/PUT request.
    $content_length = strlen($data);
    if ($content_length > 0 || $method == 'POST' || $method == 'PUT') {
        $defaults['Content-Length'] = 'Content-Length: '. $content_length;
    }

    // If the server url has a user then try to use basic authentication.
    if (isset($uri['user'])) {
        $defaults['Authorization'] = 'Authorization: Basic '.base64_encode($uri['user'] . (!emåty($uri['pass']) ? ":". $uri['pass'] : ''));
    }

    foreach ($headers as $header=>$value) {
        $defaults[$header] = $header .': '. $value;
    }

    $request = $method .' '. $path ." HTTP/1.0\r\n";
    $request .= implode("\r\n", $defaults);
    $request .= "\r\n\r\n";
    $request .= $data;

    $result->request = $request;
    fwrite($fp, $request);
    
    // Fetch response
    $response = '';
    while (!feof($fp) && $chunk = fread($fp, 1024)) {
        $response .= $chunk;
    }
    fclose($fp);

    // Parse response.
    list($split, $result->data) = explode("\r\n\r\n", $response, 2);
    $split = preg_split("/\r\n|\n|\r/", $split);

    list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
    $result->headers = array();

    // Parse headers.
    while ($line = trim(array_shift($split))) {
        list($header, $value) = explode(':', $line, 2);
        if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
            // RFC 2109: the Set-Cookie response header comprises the token Set-Cookie:,
            // followed by a comma-separated list of one or more cookies.
            $result->headers[$header] .= ','. trim($value);
        }
        else {
            $result->headers[$header] = trim($value);
        }
    }
    $responses = array(
        100 => 'Continue'    ,
        101 => "Switching Protocols",
        200 => "OK",
	    201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        300 => "Multiple Choices",
        301 => "Moved Permanently'",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified'",
        305 => "Use Proxy",
        306 => "",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Time-out",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Large",
        415 => "Unsupported Media Type",
        416 => "Requested range not satisfiable",
        417 => "Expectation Failed",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Time-out",
        505 => "HTTP Version not supported",
    );
    
    // RFC 2616 states that all unknown HTTP codes MUST betreated the same as the
    // base code in their class.
    if (!isset($responses[$code])) {
        $code = floor($code / 100) * 100;
    }

    switch ($code) {
        case 200: //OK
        case 304: // Not Modified
            break;
        case 301: // Moved Permanently
        case 302: // Moved temporarely
        case 307: // Moved temorarily
            $location = $result->headers['Location'];
            if ($retry) {
                $result = http_request($result->headers['location'], $headers, $method, $data, --$retry);
                $result->redirect_code = $result->code;
            }
            $result->redirect_url = $location;
            break;
        default:
            $result->error = $text;
    }
    $result->code = $code;
    return $result;
    
}
 
/**
 * Pull the href attribute out of an html link element.
 */
function openid_link_href($rel, $html) {
  $rel = preg_quote($rel);
  preg_match('|<link\s+rel=["\'](.*)'. $rel .'(.*)["\'](.*)/?>|iUs', $html, $matches);
  if (isset($matches[3])) {
    preg_match('|href=["\']([^"]+)["\']|iU', $matches[3], $href);
    return trim($href[1]);
  }
  return FALSE;
}

/**
 * Pull the http-equiv attribute out of an html meta element
 */
function openid_meta_httpequiv($equiv, $html) {
  preg_match('|<meta\s+http-equiv=["\']'. $equiv .'["\'](.*)/?>|iUs', $html, $matches);
  if (isset($matches[1])) {
    preg_match('|content=["\']([^"]+)["\']|iUs', $matches[1], $content);
    if (isset($content[1])) {
      return $content[1];
    }
  }
  return FALSE;
}

/**
 * Normalize the given identifier as per spec.
 */
function normalize($identifier) {
  if ($this->is_xri($identifier)) {
    return $this->normalize_xri($identifier);
  }
  else {
    return $this->normalize_url($identifier);
  }
}

function normalize_xri($xri) {
  $normalized_xri = $xri;
  if (stristr($xri, 'xri://') !== FALSE) {
    $normalized_xri = substr($xri, 6);
  }
  return $normalized_xri;
}

function normalize_url($url) {
  $normalized_url = $url;

  if (stristr($url, '://') === FALSE) {
    $normalized_url = 'http://'. $url;
  }

  if (substr_count($normalized_url, '/') < 3) {
    $normalized_url .= '/';
  }

  return $normalized_url;
}

/**
 * Determine if the given identifier is an XRI ID.
 */
function is_xri($identifier) {
  // Strip the xri:// scheme from the identifier if present.
  if (strpos(strtolower($identifier), 'xri://') !== FALSE) {
    $identifier = substr($identifier, 6);
  }

  // Test whether the identifier starts with an XRI global context symbol or (.
  $firstchar = substr($identifier, 0, 1);
  if (strpos("=@+$!(", $firstchar) !== FALSE) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Attempt to create a shared secret with the OpenID Provider.
 *
 * @param $op_endpoint URL of the OpenID Provider endpoint.
 *
 * @return $assoc_handle The association handle.
 */
function association($op_endpoint) {
  //module_load_include('inc', 'openid');

  // Remove Old Associations:
  //db_query("DELETE FROM {openid_association} WHERE created + expires_in < %d", time());

  // Check to see if we have an association for this IdP already
  //$assoc_handle = db_result(db_query("SELECT assoc_handle FROM {openid_association} WHERE idp_endpoint_uri = '%s'", $op_endpoint));
  if (empty($assoc_handle)) {
    $mod = OPENID_DH_DEFAULT_MOD;
    $gen = OPENID_DH_DEFAULT_GEN;
    $r = $this->dh_rand($mod);
    $private = bcadd($r, 1);
    $public = bcpowmod($gen, $private, $mod);


    // If there is no existing association, then request one
    $assoc_request = $this->association_request($public);
    print_r($assoc_request);
    $assoc_message = $this->encode_message($this->create_message($assoc_request));
    $assoc_headers = array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8');
    $assoc_result = $this->http_request($op_endpoint, $assoc_headers, 'POST', $assoc_message);
    if (isset($assoc_result->error)) {
      return FALSE;
    }

    $assoc_response = $this->parse_message($assoc_result->data);
    print_r($assoc_response);
    if (isset($assoc_response['mode']) && $assoc_response['mode'] == 'error') {
      return FALSE;
    }

    if ($assoc_response['session_type'] == 'DH-SHA1') {
      $spub = $this->dh_base64_to_long($assoc_response['dh_server_public']);
      $enc_mac_key = base64_decode($assoc_response['enc_mac_key']);
      $shared = bcpowmod($spub, $private, $mod);
      $assoc_response['mac_key'] = base64_encode($this->dh_xorsecret($shared, $enc_mac_key));
    }
    // TODO
    //db_query("INSERT INTO {openid_association} (idp_endpoint_uri, session_type, assoc_handle, assoc_type, expires_in, mac_key, created) VALUES('%s', '%s', '%s', '%s', %d, '%s', %d)",
    //         $op_endpoint, $assoc_response['session_type'], $assoc_response['assoc_handle'], $assoc_response['assoc_type'], $assoc_response['expires_in'], $assoc_response['mac_key'], time());

    $assoc_handle = $assoc_response['assoc_handle'];
  }

  return $assoc_handle;
}

function association_request($public) {
    $request = array(
        'openid.ns' => OPENID_NS_2_0,
        'openid.mode' => 'associate',
        'openid.session_type' => 'DH-SHA1',
        'openid.assoc_type' => 'HMAC-SHA1'
    );

    return $request;
}

function xx_openid_association_request($public) {
//  module_load_include('inc', 'openid');

  $request = array(
    'openid.ns' => OPENID_NS_2_0,
    'openid.mode' => 'associate',
    'openid.session_type' => 'DH-SHA1',
    'openid.assoc_type' => 'HMAC-SHA1'
  );

  if ($request['openid.session_type'] == 'DH-SHA1' || $request['openid.session_type'] == 'DH-SHA256') {
    $cpub = _openid_dh_long_to_base64($public);
    $request['openid.dh_consumer_public'] = $cpub;
  }

  return $request;
}

function dh_rand($stop) {
  static $duplicate_cache = array();

  // Used as the key for the duplicate cache
  $rbytes = $this->dh_long_to_binary($stop);

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
    $bytes = "\x00". $this->get_bytes($nbytes);
    $n = $this->dh_binary_to_long($bytes);
    // Keep looping if this value is in the low duplicated range.
  } while (bccomp($n, $duplicate) < 0);

  return bcmod($n, $stop);
}
function dh_long_to_binary($long) {
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
function get_bytes($num_bytes) {
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
function dh_binary_to_long($str) {
  $bytes = array_merge(unpack('C*', $str));

  $n = 0;
  foreach ($bytes as $byte) {
    $n = bcmul($n, pow(2, 8));
    $n = bcadd($n, $byte);
  }

  return $n;
}



function dh_long_to_base64($str) {
  return base64_encode($this->dh_long_to_binary($str));
}

/**
 * Encode a message from _openid_create_message for HTTP Post
 */
function encode_message($message) {
  $encoded_message = '';

  $items = explode("\n", $message);
  foreach ($items as $item) {
    $parts = explode(':', $item, 2);

    if (count($parts) == 2) {
      if ($encoded_message != '') {
        $encoded_message .= '&';
      }
      $encoded_message .= rawurlencode(trim($parts[0])) .'='. rawurlencode(trim($parts[1]));
    }
  }

  return $encoded_message;
}


/**
 * Create a serialized message packet as per spec: $key:$value\n .
 */
function create_message($data) {
  $serialized = '';

  foreach ($data as $key => $value) {
    if ((strpos($key, ':') !== FALSE) || (strpos($key, "\n") !== FALSE) || (strpos($value, "\n") !== FALSE)) {
      return null;
    }
    $serialized .= "$key:$value\n";
  }
  return $serialized;
}

function authentication_request($claimed_id, $identity, $return_to = '', $assoc_handle = '', $version = 2) {
//  module_load_include('inc', 'openid');

  $ns = ($version == 2) ? OPENID_NS_2_0 : OPENID_NS_1_0;
  $request =  array(
    'openid.ns' => $ns,
    'openid.mode' => 'checkid_setup',
    'openid.identity' => $identity,
    'openid.claimed_id' => $claimed_id,
    'openid.assoc_handle' => $assoc_handle,
    'openid.return_to' => $return_to,
  );

  if ($version == 2) {
      // TODO config
    $request['openid.realm'] = "http://local.trendycasino"; //url('', array('absolute' => TRUE));
  }
  else {
      // TODO Config
    $request['openid.trust_root'] = "http://local.trendycasino"; // url('', array('absolute' => TRUE));
  }

  // Simple Registration
  $request['openid.sreg.required'] = 'nickname,email';
  $request['openid.ns.sreg'] = "http://openid.net/extensions/sreg/1.1";

  // TODO
  // whats this?????? $request = array_merge($request, module_invoke_all('openid', 'request', $request));

  return $request;
}

/**
 * Creates a js auto-submit redirect for (for the 2.x protocol)
 */
function openid_redirect($url, $message) {
  $output = '<html><head><title>'. t('OpenID redirect') ."</title></head>\n<body>";
  $output .= drupal_get_form('openid_redirect_form', $url, $message);
  $output .= '<script type="text/javascript">document.getElementById("openid-redirect-form").submit();</script>';
  $output .= "</body></html>\n";
  print $output;
  exit;
}

/**
 * Performs an HTTP 302 redirect (for the 1.x protocol).
 */
function redirect_http($url, $message) {
  $query = array();
  foreach ($message as $key => $val) {
    $query[] = $key .'='. urlencode($val);
  }

  $sep = (strpos($url, '?') === FALSE) ? '?' : '&';
  header('Location: '. $url . $sep . implode('&', $query), TRUE, 302);
  exit;
}


/**
 * Main entry point for parsing XRDS documents
 */
function xrds_parse($xml) {
  global $xrds_services;

  $parser = xml_parser_create_ns();
  //xml_set_element_handler($parser, '_xrds_element_start', '_xrds_element_end');
  xml_set_element_handler($parser, '_xrds_element_start', '_xrds_element_end');
  xml_set_character_data_handler($parser, '_xrds_cdata');

  xml_parse($parser, $xml);
  xml_parser_free($parser);

  return $xrds_services;
}

/**
 * Parser callback functions
 */
function _xrds_element_start(&$parser, $name, $attribs) {
  global $xrds_open_elements;

  $xrds_open_elements[] = _xrds_strip_namespace($name);
}

function _xrds_element_end(&$parser, $name) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;

  $name = _xrds_strip_namespace($name);
  if ($name == 'SERVICE') {
    if (in_array(OPENID_NS_2_0 .'/signon', $xrds_current_service['types']) ||
        in_array(OPENID_NS_2_0 .'/server', $xrds_current_service['types'])) {
      $xrds_current_service['version'] = 2;
    }
    elseif (in_array(OPENID_NS_1_1, $xrds_current_service['types']) ||
            in_array(OPENID_NS_1_0, $xrds_current_service['types'])) {
      $xrds_current_service['version'] = 1;
    }
    if (!empty($xrds_current_service['version'])) {
      $xrds_services[] = $xrds_current_service;
    }
    $xrds_current_service = array();
  }
  array_pop($xrds_open_elements);
}

function _xrds_cdata(&$parser, $data) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;
  $path = strtoupper(implode('/', $xrds_open_elements));
  switch ($path) {
    case 'XRDS/XRD/SERVICE/TYPE':
      $xrds_current_service['types'][] = $data;
      break;
    case 'XRDS/XRD/SERVICE/URI':
      $xrds_current_service['uri'] = $data;
      break;
    case 'XRDS/XRD/SERVICE/DELEGATE':
      $xrds_current_service['delegate'] = $data;
      break;
    case 'XRDS/XRD/SERVICE/LOCALID':
      $xrds_current_service['localid'] = $data;
      break;
  }
}

function _xrds_strip_namespace($name) {
  // Strip namespacing.
  $pos = strrpos($name, ':');
  if ($pos !== FALSE) {
    $name = substr($name, $pos + 1, strlen($name));
  }

  return $name;
}

/**
 * Convert a direct communication message
 * into an associative array.
 */
function parse_message($message) {
  $parsed_message = array();

  $items = explode("\n", $message);
  foreach ($items as $item) {
    $parts = explode(':', $item, 2);

    if (count($parts) == 2) {
      $parsed_message[$parts[0]] = $parts[1];
    }
  }

  return $parsed_message;
}

function dh_base64_to_long($str) {
  $b64 = base64_decode($str);

  return $this->dh_binary_to_long($b64);
}

function dh_xorsecret($shared, $secret) {
  $dh_shared_str = $this->dh_long_to_binary($shared);
  $sha1_dh_shared = $this->sha1($dh_shared_str);
  $xsecret = "";
  for ($i = 0; $i < strlen($secret); $i++) {
    $xsecret .= chr(ord($secret[$i]) ^ ord($sha1_dh_shared[$i]));
  }

  return $xsecret;
}

function sha1($text) {
  $hex = sha1($text);
  $raw = '';
  for ($i = 0; $i < 40; $i += 2) {
    $hexcode = substr($hex, $i, 2);
    $charcode = (int)base_convert($hexcode, 16, 10);
    $raw .= chr($charcode);
  }
  return $raw;
    }

}

function _xrds_element_start(&$parser, $name, $attribs) {
  global $xrds_open_elements;

  $xrds_open_elements[] = _xrds_strip_namespace($name);
}
function _xrds_element_end(&$parser, $name) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;

  $name = _xrds_strip_namespace($name);
  if ($name == 'SERVICE') {
    if (in_array(OPENID_NS_2_0 .'/signon', $xrds_current_service['types']) ||
        in_array(OPENID_NS_2_0 .'/server', $xrds_current_service['types'])) {
      $xrds_current_service['version'] = 2;
    }
    elseif (in_array(OPENID_NS_1_1, $xrds_current_service['types']) ||
            in_array(OPENID_NS_1_0, $xrds_current_service['types'])) {
      $xrds_current_service['version'] = 1;
    }
    if (!empty($xrds_current_service['version'])) {
      $xrds_services[] = $xrds_current_service;
    }
    $xrds_current_service = array();
  }
  array_pop($xrds_open_elements);
}

function _xrds_cdata(&$parser, $data) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;
  $path = strtoupper(implode('/', $xrds_open_elements));
  switch ($path) {
    case 'XRDS/XRD/SERVICE/TYPE':
      $xrds_current_service['types'][] = $data;
      break;
    case 'XRDS/XRD/SERVICE/URI':
      $xrds_current_service['uri'] = $data;
      break;
    case 'XRDS/XRD/SERVICE/DELEGATE':
      $xrds_current_service['delegate'] = $data;
      break;
    case 'XRDS/XRD/SERVICE/LOCALID':
      $xrds_current_service['localid'] = $data;
      break;
  }
}

function _xrds_strip_namespace($name) {
  // Strip namespacing.
  $pos = strrpos($name, ':');
  if ($pos !== FALSE) {
    $name = substr($name, $pos + 1, strlen($name));
  }

  return $name;
}

