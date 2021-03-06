<?php
class api_session {
    private static $instance;
    
    private function __construct() {
        session_start();
    }

    static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    static function get($key) {
        return (isset($_SESSION[__CLASS__][$key]) ? $_SESSION[__CLASS__][$key] : null);
    }

    static function set($key, $value=null) {
        $_SESSION[__CLASS__][$key] = $value;
    }

    static function destroy($key) {
        unset($_SESSION[__CLASS__][$key]);
    }

    // Feedback string
    static function add_flash($lvl, $message) {
        if (!isset($_SESSION['flash'][$lvl])) {
            $_SESSION['flash'][$lvl] = array();
        }
        array_push($_SESSION['flash'][$lvl], $message);
    }

    static function get_flash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            $_SESSION['flash'] = '';
            return $flash;
        }
        return array();
    }
}
