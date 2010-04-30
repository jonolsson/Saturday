<?php

/**
 * A PuSHHub Subscriptions.
 */
class api_push_hub_subscriptions implements api_push_hub_subscriptions_interface {
  /**
   * Singleton.
   */
  public static function getInstance() {
    static $subscriptions;
    if (empty($subscriptions)) {
      $subscriptions = new api_push_hub_subscriptions();
    }
    return $subscriptions;
  }

  /**
   * Protect constructor.
   */
  protected function __construct() {
  }

  /**
   * Save a subscription.
   */
  public function save($topic, $subscriber, $secret) {
    $this->delete($topic, $subscriber);
    $subscription = array(
      'topic' => $topic,
      'subscriber' => $subscriber,
      'secret' => $secret,
      'timestamp' => time(),
    );
    echo "Save subscription";
    print_r($subscription);
    $db = Database::factory();
    $sth = $db->prepare("INSERT INTO subscriptions (topic, subscriber, secret, timestamp) VALUES (?, ?, ?, ?)");
    $sth->execute(array($topic, $subscriber, $secret, time()));
    print_r($db->errorInfo());
    //drupal_write_record('push_hub_subscriptions', $subscription);
  }

  /**
   * Delete a subscription.
   */
  public function delete($topic, $subscriber) {
      $sql = "DELETE FROM subscriptions WHERE topic = '$topic' AND subscriber = '$subscriber'";
      $db = Database::factory();
      $db->query($sql);
        echo "Delete Subscription";
      //    db_query("DELETE FROM {push_hub_subscriptions} WHERE topic = '%s' AND subscriber = '%s'", $topic, $subscriber);
  }

  /**
   * Find all subscriber URLs for a given topic URL.
   *
   * @return
   *   An array of subscriber URLs.
   */
  public function all($topic) {
    $subscribers = array();
    $result = db_query("SELECT subscriber FROM {push_hub_subscriptions} WHERE topic = '%s'", $topic);
    while ($row = db_fetch_object($result)) {
      $subscribers[] = $row->subscriber;
    }
    return $subscribers;
  }

  /**
   * Retrieve a shared secret.
   */
  public function secret($topic, $subscriber) {
    return db_result(db_query("SELECT secret FROM {push_hub_subscriptions} WHERE topic = '%s' AND subscriber = '%s'", $topic, $subscriber));
  }

  /**
   * Expire subscriptions.
   */
  public function expire($time) {
    db_query("DELETE FROM {push_hub_subscriptions} WHERE timestamp < %d", time() - $time);
  }
}
