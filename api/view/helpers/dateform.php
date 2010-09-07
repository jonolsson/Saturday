<?php
class api_view_helpers_dateform {
    function dateform($args=array()) {
        $year_delta = (isset($args[0]) ? $args[0] : 100);
        $current_year = date("Y");

        $html = '<label for="year">'. t("year").'</label>
                <select name="year">';

        $y = $current_year + $year_delta;
        for($y; $y < ($current_year - $year_delta); $y++) {
            $html .= "<option value='$y'>$y</option>";
        }
                    
        $html .= '</select>
                <label for="month">'. t("month") .'</label>
                <select name="month">';
        for($m = 1; $m <= 12; $m++) {
            $html .= "<option value='$m'>$m</option>";
        }
        $html .= '</select>
                <label for="day">'. t("day") .'</label>
                <select name="day">';
        for($d = 1; $d <= 31; $d++) {
            $html .= "<option value='$d'>$d</option>";
        }
        $html .= '</select>';
        return $html;
    }
}
