<?php

show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$api = new PBS_Check_DMA();

$return = array();

if (empty($_POST['media_id'])) {
  $return['client_ip_address'] = $api->get_remote_ip_address();
} else {
  $allowed_counties_ary = $api->format_counties_setting_for_use();
  $allowed_counties_string = '';
  foreach ($allowed_counties_ary as $state => $counties) {
    if ($allowed_counties_string) {
      $allowed_counties_string .="; ";
    }
    $allowed_counties_string .= $state . ": " . implode(", ", $counties);
  }

  $apicheck = $api->visitor_is_in_dma();
  $location_string = "";
  foreach ($apicheck['location'] as $key => $var) {
    if ($location_string) {
      $location_string .= "; ";
    }
    $location_string .= "$key: $var";
  }
  $location_string = !empty($location_string) ? $location_string : "a location that we are unable to determine";
  if ($apicheck[0]) {
    $media_id = $_POST['media_id'];
    $return = "<div class='video-wrap'><!-- DUE TO CONTRACTUAL OBLIGATIONS TO THE PRODUCERS, CAST, AND CREW OF THE PERFORMANCE RECORDED IN THE VIDEO BELOW, IT IS PROHIBITED TO PLAY THE VIDEO BELOW ON DEVICES THAT ARE NOT PHYSICALLY LOCATED WITHIN THE WNET/THIRTEEN BROADCAST AREA. THIS AREA IS COMPRISED OF THE FOLLOWING COUNTIES: $allowed_counties_string. PLEASE RESPECT THESE RIGHTS. Your device appears to be located in $location_string --><iframe src='//player.pbs.org/widget/partnerplayer/$media_id/?chapterbar=false' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>";
  } else {
      
      
        if (function_exists('pbs_video_utils_get_video') && !empty($_POST['media_id'])) {
            $video = pbs_video_utils_get_video($_POST['media_id']); 
            if (!empty($video[0]['metadata'])) {
                $m = json_decode($video[0]['metadata']);
                if (!empty($m->mezzanine)) {$thumbnail = $m->mezzanine . '?crop=1200x675&format=jpg';}
                else {$thumbnail = '';}
            }
        }
      
      
    $return = "<div class='video-wrap dma-fail'><img src='$thumbnail'><div class='sorry'><div class='sorry-txt'><h3>Sorry, this content is only available to viewers within our broadcast area*.</h3><p>Check your <a href='https://www.pbs.org/tv_schedules/' target='_blank'>local PBS listings</a> to find out where you can watch.</p><p>*Our broadcast area contains the following counties:  $allowed_counties_string.</p><p>Your device appears to be located in $location_string. If this is not correct, it may be due to VPN software on your device, or mis-reporting of your location by your network or cable provider.</p></div></div></div>";
  }
}

// extra slash stripping in case we're using PHP 5.3
$json = stripslashes(json_encode($return));
header('content-type: application/json; charset=utf-8');
exit($json);

# JSONP if valid callback
//exit("{$_GET['callback']}($json)");


