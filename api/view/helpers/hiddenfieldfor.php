<?php
class api_view_helpers_hiddenfieldfor {
    function hiddenfieldfor($args) {
        $entity = $args[0];
        $field = $args[1];
        
        $html = '<input type="hidden" id="field-' . e($field) . '" name="' . e($field) . '" value="' . e($entity->$field) . '" />';
        $html .= "\n";

        return $html;
    }
}

