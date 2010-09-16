<?php
class api_view_helpers_textfieldfor {
    function textfieldfor($args) {
        $entity = $args[0];
        $field = $args[1];
        $label = (isset($args[2]) ? $args[2] : $field);
        
        $html = '<label for="field-' . e($field) . '">' . e(t($label)) . '</label>';
        $html .= '<input type="text" id="field-' . e($field) . '" name="' . e($field) . '"';
       
        if ($entity->field) {
           $html .= ' value="' . e($entity->$field) . '"';
        }
        $html .=  ' />';

        if (isset($entity->errors[$field])) {
            $html .= '    <span style="display:block;color:red">' . e($entity->errors[$field]) . ' </span>';
        }
        $html .= "\n";
        return $html;
    }
}
