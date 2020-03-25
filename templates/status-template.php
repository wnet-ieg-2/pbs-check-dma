<?php
/* this endpoint is just to return the current status of the feed */
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$api = new PBS_Check_DMA();
$defaults = get_option($api->token);
$station_common_name = !empty($defaults['station_common_name']) ? $defaults['station_common_name'] : "";


$blackout_schedule = array(
  "weekly" => array(
    "monday" => array(
      array(
        "start" => "0730", "end" => "0800"
      )
    ),
    "tuesday" => array(
      array(
        "start" => "0730", "end" => "0800"
      )
    ),
    "wednesday" => array(
      array(
        "start" => "0730", "end" => "0800"
      )
    ),
    "thursday" => array(
      array(
        "start" => "0730", "end" => "0800"
      )
    ),
    "friday" => array(
      array(
        "start" => "0730", "end" => "0800"
      )
    )
  ),
  "dates" => array(
    "2020-03-25" => array(
      array(
        "start" => 1700, "end" => 1800
      )
    )
  )
);

$tz = !empty(get_option('timezone_string')) ? get_option('timezone_string') : 'America/New York';
$date = new DateTime('now', new DateTimeZone($tz));

$dayname = $date->format('D');
$ymd = $date->format('Y-m-d');
$miltime = $date->format('Hi');

$blackout = false;

foreach ($blackout_schedule as $type => $data) {
  foreach($data as $schedule => $times) {
    error_log($type . " " . $schedule);
    if ($type == 'weekly') {
      if (strtolower($schedule) != strtolower($dayname)) {
        continue;
      }
    } else {
      if ($schedule != $ymd) {
        error_log("schedule is $schedule which is not eq to $ymd");
        continue;
      }
    }
    error_log("times is not empty:" . json_encode($times));
    if (!empty($times)) {
      error_log("for reals");
      foreach ($times as $time => $pair) {
        error_log(json_encode($pair));
        error_log($miltime);
        if ($miltime > $pair['start'] && $miltime < $pair['end']) {
          $blackout = true;
          error_log("blakcing out");
          break 2;
        }
      }
    }
  }
}

$blackout_status = array(
  "blackout_status" => $blackout
);
 
$return = $blackout_status;


// extra slash stripping in case we're using PHP 5.3
$json = stripslashes(json_encode($return));
header('content-type: application/json; charset=utf-8');
exit($json);

