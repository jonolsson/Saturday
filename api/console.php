<?php
class api_console {
    
    protected $script_file_name = null;
    protected $options = array();
    protected $arguments = array();

    function __construct($argv=null) {
        $argv = (!is_null($argv) ? $argv : $_SERVER['argv']);
        $this->parseInput($argv);
    }

    protected function parseInput($argv) {
        $this->script_file_name = $argv[0];
        $this->options = array();
        $this->arguments = array();
        foreach (array_slice($argv, 1) as $arg) {
            if (preg_match('~^--([^=]+)=(.*)~', $arg, $reg)) {
                $this->options[$reg[1]] = $reg[2];
            } else if (preg_match('~^--([a-zA-Z0-9]+)$~', $arg, $reg)) {
                $this->options[$reg[1]] = true;
            } else if (preg_match('~^-([a-zA-Z]+)$~', $arg, $reg)) {
                foreach (str_split($reg[1]) as $option) {
                    $this->options[$option] = true;
                }
            } else {
                $this->arguments[] = $arg;
            }
        }
    }
    
    function argument($num = 0, $default = null) {
        if (isset($this->arguments[$num])) {
            return $this->arguments[$num];
        }
        return $default;
    }

    function countArguments() {
        return count($this->arguments);
    }

    function argumentsAsArray() {
        return $this->arguments;
    }

    function argumentsAsString() {
        return implode(" ", $this->argumentsAsArray());
    }
    
    function option($names, $default = null) {
        if (is_string($names)) {
            $names = array($names);
        }
        foreach ($names as $name) {
            if (isset($this->options[$name])) {
                return $this->options[$name];
            }
        }
        return $default;
    }

}
