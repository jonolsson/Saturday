<?php
class api_view_helpers_selectfor {
    function selectfor($args=array()) {
        $entity = $args[0];
        $field = $args[1];
        list($id, $value) = $args[2];
        $label = (isset($args[3]) ? $args[3] : ucfirst($field));

        $html = '<label for="field-' . e($field) . '">' . e(__($label)) . '</label>';
        $html .= '<select id="field-' . e($field) . '" name="' . e($field) . '">';
        foreach($entity as $e) {
            $html .= '<option value="' . e($e->$id) . '">'.e($e->$value).'</option>';
        }
        $html .= '</select>';
        if (isset($entity->errors[$field])) {
            $html .= '    <span style="display:block;color:red">' . e($entity->errors[$field]) . ' </span>';
        }
        $html .= "\n";
        return $html;
    }
}
