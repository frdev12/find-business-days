<?php

const CALENDARS_PATH = "calendars/";

function downloadCalendar($year)
{
    if ( ! is_dir(CALENDARS_PATH)) {
        mkdir(CALENDARS_PATH);
    }

    if ( ! is_readable(CALENDARS_PATH.$year.".csv")
        || (time() - filemtime(CALENDARS_PATH.$year.".csv")) / 86400 > 30) {

        $remote_file = file_get_contents("http://xmlcalendar.ru/data/ru/".$year."/calendar.csv");

        if ($remote_file) {
            file_put_contents(CALENDARS_PATH.$year.".csv", $remote_file);
        } else {
            return false;
        }
    }
    return true;
}

function getBusinessDaysNumInMonth($year, $month)
{
    if ($month <= 0 || $month > 12) {
        return false;
    }

    if (downloadCalendar($year)) {
        $calendar_data = fopen(CALENDARS_PATH.$year.".csv", "r");
        $calendar_data_array = [];
        while ( ! feof($calendar_data)) {
            $calendar_data_array[] = fgetcsv($calendar_data);
        }
        fclose($calendar_data);

        $month_data = null;
        foreach ($calendar_data_array as $item) {
            if ($item[0] == $year) {
                $month_data = $item[$month];
                break;
            }
        }
        if ( ! $month_data) {
            return false;
        }

        $month_data_array = explode(",", $month_data);
        $number_of_weekends = count(array_filter($month_data_array, function ($item) {
                return ! strpbrk($item, "*"); // Фильтр предпраздничных дней, они помечены * в производственном календаре
            }));

        return cal_days_in_month(CAL_GREGORIAN, $month, $year) - $number_of_weekends;
    } else {
        return false;
    }
}
