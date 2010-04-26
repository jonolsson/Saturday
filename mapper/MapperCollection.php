<?php
class MapperCollection implements Iterator {
	private $mapper;
	private $result;
	private $total = 0;
	private $pointer = 0;
	private $objects = array();
	private $raw = array();

	function __construct( $result=null, $mapper=null ) {
		if ( $result && $mapper ) {
			$this->init_db( $result, $mapper );
		}
	}

	protected function init_db( $result,
	$mapper ) {
		$this->result = $result;
		$this->mapper = $mapper;
		$this->total  += $result->numrows();
		while ( $row = $this->result->fetchRow() ) {
			$this->raw[] = $row;
		}
	}

	protected function doAdd( $object ) {
		$this->notifyAccess();
		$this->objects[$this->total] = $object;
		$this->total++;

	}

	protected function notifyAccess() {
		// deliberately left blank!
	}

	private function getRow( $num ) {
		$this->notifyAccess();
		if ( $num >= $this->total || $num < 0 ) {
			return null;
		}
		if ( array_key_exists( $num, $this->objects ) ) {
			return $this->objects[$num];
		}

		if ( $this->raw[$num] ) {
			$this->objects[$num]=$this->mapper->loadArray( $this->raw[$num] );
			return $this->objects[$num];
		}
	}

	public function rewind() {
		$this->pointer = 0;
	}

	public function current() {
		return $this->getRow( $this->pointer );
	}

	public function key() {
		return $this->pointer;
	}

	public function next() {
		$row = $this->getRow( $this->pointer );
		if ( $row ) { $this->pointer++; }
		return $row;
	}

	public function valid() {
		return ( ! is_null( $this->current() ) );
	}
	 
	public function getSize() {
		return $this->total;
	}
}
