<?php

// TTL of a subscription.
define('PUSH_HUB_LEASE_SECONDS', 3600*48);


class api_push_hub {
    protected $subscriptions;
    protected $logger = null;

    public static function getInstance(api_push_hub_subscriptions_interface $subscriptions) {
        static $hub;
        if (empty($hub)) {
            $hub = new api_push_hub($subscriptions);
        }
        return $hub;
    }

    /**
   * Constructor.
   *
   * @param $subscriptions
   *   Subsriptions object that handles subscription storage. Must implement
   *   PuSHHubSubscriptionsInterface.
   */
  protected function __construct(api_push_hub_subscriptions_interface $subscriptions) {

        $cfg = api_config::getInstance();
        $this->logger = Zend_Log::factory(array($cfg->log));
        $this->subscriptions = $subscriptions;
  }

  /**
   * Get all subscribers for a given topic.
   */
  public function allSubscribers($topic) {
    return $this->subscriptions->all($topic);
  }

  /**
   * Notify a specific subscriber of a change in a topic. API user is
   * responsible for handling failed requests.
   *
   * @param $topic
   *   The topic that changed.
   * @param $subscriber
   *   A subscriber callback.
   * @param $changed
   *   The full or partial feed that contains the changed elements. If NULL,
   *   a light ping (without any content) will be issued to subscriber and
   *   subscriber is expected to fetch content from publisher. Light pings are
   *   not pubsubhubbub spec conform.
   *
   * @return
   *   TRUE if subscriber was successfully notified, FALSE otherwise.
   */
  public function notify($topic, $subscriber, $changed = NULL) {
      $this->logger->info("Notify");
    if ($changed === NULL) {
      return $this->lightPing($subscriber);
    }
    return $this->fatPing($subscriber, $changed, $this->subscriptions->secret($topic, $subscriber));
  }

  /**
   * Verify subscription request.
   */
  public function verify($post) {
    // Send verification request to subscriber.
    $challenge = md5(session_id() . rand());
    $query = array(
      'hub.mode='. $post['hub_mode'],
      'hub.topic='. urlencode($post['hub_topic']),
      'hub.challenge='. $challenge,
      'hub.lease_seconds='. PUSH_HUB_LEASE_SECONDS, // disregard what's been requested ($post['hub_lease_seconds'])
      'hub.verify_token='. $post['hub_verify_token'],
    );
    $parsed = parse_url($post['hub_callback']);
    $request = curl_init($post['hub_callback'] . (empty($parsed['query']) ? '?' : '&') . implode('&', $query));
    curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    $data = curl_exec($request);
    $code = curl_getinfo($request, CURLINFO_HTTP_CODE);
    curl_close($request);
    if ($code > 199 && $code < 300 && $data == $challenge) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Handle a subscription request.
   *
   * @param $post
   *   A valid PubSubHubbub subscription request.
   */
  public function subscribe($post) {
      //error_log(
      //print_r($post);
      // Authenticate
      $received_secret = $post['secret'];
      $cfg = api_config::getInstance()->hub;
      $secret = md5($cfg['secret'].$post['hub_callback']);
      if (($secret == $received_secret) and (isset($post['hub_topic']) && isset($post['hub_callback']) && $this->verify($post))) {
      $this->subscriptions->save($post['hub_topic'], $post['hub_callback'], isset($post['secret']) ? $post['secret'] : '');
    //  header('HTTP/1.1 204 "No Content"', null, 204);
    //    exit(); */
      echo "Good";
      return true;
    }
    echo "not found";
    return false;
    //header('HTTP/1.1 404 "Not Found"', null, 404);
    //exit(); */
  }


  /**
   * Handle a publish request
   */
  public function publish($post) {
    if (isset($post['topic'])) {
        $this->publisher->save($post['topic']);
    }
  }


  /**
   * Expire old subscriptions.
   */
  public function expire() {
    $this->subscriptions->expire(PUSH_HUB_LEASE_SECONDS);
  }

  /**
   * Helper for posting a fat ping. Uses stream wrappers instead of cURL for
   * posting a message body that is not "application/x-www-form-urlencoded".
   */
  protected function fatPing($url, $content, $secret) {
    $result = FALSE;
    $params = array('http' =>
                array(
                  'method' => 'POST',
                  'content' => $content,
                  'header' => "Content-Type: application/atom+xml\n".
                              "Content-Length: ". strlen($content) ."\n\n",
                ),
              );
    if (!empty($secret)) {
      $params['http']['header']['X-Hub-Signature'] = hash_hmac('sha1', $content, $secret);
    }
    $ctx = stream_context_create($params);
    if ($fp = @fopen($url, 'rb', false, $ctx)) {
      $response = @stream_get_contents($fp);
      $meta = stream_get_meta_data($fp);
      preg_match('/HTTP.*?\s(\d*?)\s.*/i', $meta['wrapper_data'][0], $matches);
      $code = (integer) $matches[1];
      if ($code >= 200 && $code < 300) {
        $result = TRUE;
      }
      fclose($fp);
    }
    return $result;
  }

    function push($url, $content, $secret) {
        $result = FALSE;
       // $content = urlencode($content);
        $content = "data=".$content;
        $params = array('http' =>
                array(
                  'method' => 'POST',
                  'content' => $content,
                  'header' => "Content-Type: application/atom+xml\n".
                              "Content-Length: ". strlen($content) ."\n\n",
                ),
              );
    if (!empty($secret)) {
      $params['http']['header']['X-Hub-Signature'] = hash_hmac('sha1', $content, $secret);
    }
    $ctx = stream_context_create($params);
    if ($fp = @fopen($url, 'rb', false, $ctx)) {
      $response = @stream_get_contents($fp);
      $meta = stream_get_meta_data($fp);
      preg_match('/HTTP.*?\s(\d*?)\s.*/i', $meta['wrapper_data'][0], $matches);
      $code = (integer) $matches[1];
      if ($code >= 200 && $code < 300) {
        $result = TRUE;
      }
      fclose($fp);
    }
    return $result;
  }


  /**
   * Issue a light ping, not spec conform.
   */
  function lightPing($url, $challenge='') {
      $this->logger->info("Light ping");
    $request = curl_init($url."?hub_challenge=$challenge");
    curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
    $data = curl_exec($request);
    $code = curl_getinfo($request, CURLINFO_HTTP_CODE);
    curl_close($request);
    if ($code >= 200 && $code < 300) {
      return TRUE;
    }
    return FALSE;
  }
}



