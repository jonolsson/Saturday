<?php
class api_push_hub_publisher { 
    protected $publisher;

    function __construct(api_push_hub_publisher_interface $publisher) {
        $this->publisher = $publisher;
    }

    function publish($topic) {
        $this->publisher->save($topic);
    }

    function notify($topic) {
        $this->publisher->notify($topic);
    }
}
