<?php
class api_view_helpers_textfieldfor {
    function textfieldfor($args) {
        $entity = $args[0];
        $field = $args[1];
        $label = (isset($args[2]) ? $args[2] : ucfirst($field));
        
        $html = '<label for="field-' . e($field) . '">' . e($label) . '</label>';
        $html .= '<input type="text" id="field-' . e($field) . '" name="' . e($field) . '" value="' . e($entity->$field) . '" />';

        if (isset($entity->errors[$field])) {
            $html .= '    <span style="display:block;color:red">' . escape($entity->errors[$field]) . ' </span>';
        }
        $html .= "\n";
        return $html;
    }
}
