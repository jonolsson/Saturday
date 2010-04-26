<?php
class api_acl {
    private static $instance = null;
    private static $roles = array();
    private static $resources = array();
    private static $allowed = array();
    private static $denyed = array();

    private static __construct() {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return $instance;
    }

    public function addRole($role, $parent=null) {
        if ($parent) {
            self::$roles[$parent] = array();
            self::$roles[$parent][$role];
        } else {
            self::$roles[$role];
        }
    }

    public function addResource($res) {
        if (!isset(self::$resource[$res])) {
            self::$resource[$res];
        } else {
            throw new Exception("Resource allready added");
        }
    }

    public function allow($role, $res) {
        
    }

    public function deny($role, $res) {

    }

    public function isAllowed($role, $resource) {

    }
}
