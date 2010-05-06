<?php
class api_view_helpers_form {
    function form($args=array(), $method = 'post', $action = null, $options = array()) {
        $method = (isset($args[0]) ? $args[0] : "post");
        $action = (isset($args[1]) ? $args[1] : "default");
        $options = (isset($args[2]) ? $args[2] : array());

        $method = strtolower($method);
        $html = '<form';
        $options['action'] = $action ? $action : 'default';
        $options['method'] = $method === 'get' ? 'get' : 'post';
        foreach ($options as $k => $v) {
            if ($v !== null) {
                $html .= ' ' . e($k) . '="' . e($v) . '"';
            }
        }
        $html .= ">\n";
        if ($method !== 'get' && $method !== 'post') {
            $html .= '<input type="hidden" name="_method" value="' . e($method) . '" />
        ';
        }
        return $html;
    }
}

