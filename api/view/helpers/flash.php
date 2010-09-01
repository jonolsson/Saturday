<?php
class api_view_helpers_flash {
    function flash() {
        $html = '<div id="flash-message">';
        $flash = api_session::get_flash();

        if (isset($flash['error'])) {
            $html .= "<div id='error'><ul>";
            foreach($flash['error'] as $msg) {
                $html .= "<li>$msg</li>";
            }
            $html .= "</ul></div>";
        }
        if (isset($flash['warning'])) {
            $html .= "<div id='warning'><ul>";
            foreach($flash['warning'] as $msg) {
                $html .= "<li>$msg</li>";
            }
            $html .= "</ul></div>";
        }
        if (isset($flash['info'])) {
            $html .= "<div id='info'><ul>";
            foreach($flash['info'] as $msg) {
                $html .= "<li>$msg</li>";
            }
            $html .= "</ul></div>";
        }
        return $html.'</div>';
    }
}
