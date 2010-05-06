<?php
class api_view_helpers_submit {
    function submit($args=array()) {
        $name = (isset($args[0]) ? $args[0] : '');
        $value = (isset($args[1]) ? $args[1] : '');
        $options = (isset($args[2]) ? $args[2] : array());

        $html = '<input type="submit"';
        $options['name'] = $name;
        
        $options['value'] = $value;
        foreach ($options as $k => $v) {
            if ($v != '') {
                $html .= ' ' . e($k) . '="' . e($v) . '"';
            }
        }
        return $html . " />\n";
    }
}
