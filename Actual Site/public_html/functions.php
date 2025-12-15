<?php

    function get_month_of_timestamp($stamp)
    {
        $months = array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        $month_num = intval(substr($stamp, 5, 2));
        return $months[$month_num];
    }

    function get_year_of_timestamp($stamp)
    {
        return substr($stamp, 0, 4);
    }

    function get_day_of_timestamp($stamp)
    {
        $day = substr($stamp, 8, 2);
        $day_num = intval($day);
        if ($day_num == 1 || $day_num == 21 || $day_num == 31)
        {
            return $day . "st";
        }
        elseif($day_num == 2 || $day_num == 22)
        {
            return $day . "nd";
        }
        elseif($day_num == 3 || $day_num == 23)
        {
            return $day . "rd";
        }
        else
        {
            return $day . "th";
        }
    }

    function get_12_hr_time_of_timestamp($stamp)
    {
        $hour = intval(substr($stamp, 11, 2));
        $minute = intval(substr($stamp, 14, 2));
        if ($minute < 10)
        {
            $minute_string = "0" . $minute;
        }
        else
        {
            $minute_string = "" . $minute;
        }
        $am_pm = "";
        if ($hour == 0)
        {
            $hour = 12;
            $am_pm = "a.m.";
        }
        elseif ($hour > 11)
        {
            $am_pm = "p.m.";
            $hour = $hour - 12;
        }
        else
        {
            $am_pm = "a.m.";
        }
        
        return $hour . ":" . $minute_string . " " . $am_pm;
    }

    function get_timestamp_in_english($stamp)
    {
        $month = get_month_of_timestamp($stamp);
        $day = get_day_of_timestamp($stamp);
        $year = get_year_of_timestamp($stamp);
        $time = get_12_hr_time_of_timestamp($stamp);

        return $month . " " . $day . ", " . $year . " at " . $time . " UTC";
    }
?>