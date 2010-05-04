<?php
/**
 * Describes the storage for PuSHHub subscriptions. Implement to provide a
 * storage class.
 */

interface api_push_hub_subscriptions_interface {

  /**
   * Get the secret for a given topic/subscriber pair.
   *
   * @return
   *   Shared secret to sign a notification with.
   */
  public function secret($topic, $subscriber);

  /**
   * Save a subscription.
   *
   * @param $topic
   *   A topic URL.
   * @param $subscriber
   *   The subscriber URL that is the callback to be invoked when the topic
   *   changes.
   * @param $secret
   *   Secret for message authentication.
   */
  public function save($topic, $subscriber, $secret);

  /**
   * Delete a subscription.
   *
   * @param $topic
   *   A topic URL.
   * @param $subscriber
   *   The subscriber URL that is the callback to be invoked when the topic
   *   changes.
   */
  public function delete($topic, $callback);

  /**
   * Find all subscriber URLs for a given topic URL.
   *
   * @param $topic
   *   A topic URL.
   *
   * @return
   *   An array of subscriber URLs.
   */
  public function all($topic);

  /**
   * Expire subscriptions older than $time.
   *
   * @param $time
   *   Subscriptions older than time() - $time will be deleted.
   */
  public function expire($time);
}
