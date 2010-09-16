<?php
class api_view_helpers_dateform {
    function dateform($args=array()) {
        $year_delta = (isset($args[0]) ? $args[0] : 100);
        $fields = ((isset($args[1]) and is_array($args[1])) ? $args[1] : array('year', 'month', 'day'));
        $options = (isset($args[2]) ? $args[2] : array());

        $current_year = date("Y");

        $html = '<label for="year">'. t("year").'</label>
                <select name="year">';

        if (in_array('year', $fields)) {
            $y = $current_year; // + $year_delta;
            $start_year = $current_year;
            $lenght = 4;
            $lenght  = (isset($options['year_length']) and (($options['year_length'] == 2) or ($options['year_length'] == 4)) ? $options['year_length'] : 4);

            if ($year_delta < 0) {
                $year_delta = abs($year_delta);
                for($y; $y > ($current_year - $year_delta); $y--) {
                    $year = $y;
                    if ($lenght == 2) { $year = substr($year, 2); }
                    $html .= "<option value='$year'>$year</option>";
                }
            } else {
                for($y; $y < ($current_year + $year_delta); $y++) {
                    $year = $y;
                    if ($lenght == 2) { $year = substr($year, 2); }
                    $html .= "<option value='$year'>$year</option>";
                }
            }
            $html .= '</select>';
        }

        if (in_array('month', $fields)) {        
            $html .= '<label for="month">'. t("month") .'</label>
                <select name="month">';
            for($m = 1; $m <= 12; $m++) {
                $m = str_pad($m, 2, '0', STR_PAD_LEFT);
                $html .= "<option value='$m'>$m</option>";
            }
            $html .= '</select>';
        }

        if (in_array('day', $fields)) {
            $html .= '<label for="day">'. t("day") .'</label>
            <select name="day">';
            for($d = 1; $d <= 31; $d++) {
                $html .= "<option value='$d'>$d</option>";
            }
            $html .= '</select>';
        }
        return $html;
    }
}
