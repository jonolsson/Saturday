<?php
class api_mapper_collection implements Iterator {
    private $mapper = null;
    private $result = null;
    private $total = 0;
    private $pointer = 0;
    private $objects = array();
    private $raw = array();

    function __construct($result=null, $mapper=null) {
        if ($result && $mapper) {
            $this->init_db($result, $mapper);
        }
    }

    protected function init_db($result, $mapper) {
        $this->result = $result;
        $this->mapper = $mapper;
        $this->total += $result->rowCount();
        while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
            $this->raw[] = $row;
        }
    }

    protected function add($object) {
        $this->notifyAccess();
        $this->objects[$this->total] = $object;
        $this->total++;
    }

    protected function notifyAccess() {

    }

    protected function getAt($num) {
        $this->notifyAccess();
        if ($num > $this->total || $num < 0) {
            return null;
        }
        if (isset($this->objects[$num])) {
            return $this->objects[$num];
        }
        if (isset($this->raw[$num])) {
            $this->objects[$num] = $this->mapper->loadArray($this->raw[$num]);
            return $this->objects[$num];
        }
    }

    public function rewind() {
        $this->pointer = 0;
        return $this;
    }

    public function current() {
        return $this->getAt($this->pointer);
    }

    public function key() {
        return $this->pointer;
    }

    public function next() {
        $row = $this->getAt($this->pointer);
        if ($row)
            $this->pointer++;
        return $row;
    }

    public function valid() {
        return (!is_null($this->current()));
    }
}
