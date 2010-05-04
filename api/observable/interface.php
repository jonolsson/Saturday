<?php
interface api_observable_interface {

    function attach(api_observer_interface $observer);
    function detach(api_observer_interface $observer);
    function notify();
}
