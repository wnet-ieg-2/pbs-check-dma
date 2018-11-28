<?php

show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$api = new PBS_Check_DMA();

$return = array();

if (empty($_POST['media_id'])) {
  $return['client_ip_address'] = $api->get_remote_ip_address();
} else {
  $in_dma = false;
  // TK check a cookie for the location; will include zip, county, state, country, optional lat and lng

    // no cookie? check the ip
    $ipcheck = $api->visitor_ip_is_in_dma();
    $in_dma = $ipcheck[0];
    $location = $ipcheck['location'];
    if (!$in_dma) {
      // code TK, just structure
      // no lat/lng passed?  
        // have we requested a lat/lng already? refusal will be a browser-set cookie
          // set location cookie and display the sorry page
        // else die() to prompt a lan/lng request
      // else lookup the location using lat/lng
        // $location = $api->get_location_by_reverse_geocode($lat, $lng);
        // $in_dma = $api->compare_county_to_allowed_list($location);
        // set location cookie
    }
 


  $allowed_counties_ary = $api->format_counties_setting_for_use();
  $allowed_counties_string = '';
  foreach ($allowed_counties_ary as $state => $counties) {
    if ($allowed_counties_string) {
      $allowed_counties_string .="; ";
    }
    $allowed_counties_string .= $state . ": " . implode(", ", $counties);
  }


  $location_string = "";
  foreach ($location as $key => $var) {
    if ($key == "errors") {
      continue;
    }
    if ($location_string) {
      $location_string .= "; ";
    }
    $location_string .= "$key: $var";
  }
  $location_string = !empty($location_string) ? $location_string : "someplace where we cannot determine your US state and county";
  if ($in_dma) {
    $media_id = $_POST['media_id'];
    $return = "<div class='video-wrap'><!-- DUE TO CONTRACTUAL OBLIGATIONS TO THE PRODUCERS, CAST, AND CREW OF THE PERFORMANCE RECORDED IN THE VIDEO BELOW, IT IS PROHIBITED TO PLAY THE VIDEO BELOW ON DEVICES THAT ARE NOT PHYSICALLY LOCATED WITHIN THE WNET/THIRTEEN BROADCAST AREA. THIS AREA IS COMPRISED OF THE FOLLOWING COUNTIES: $allowed_counties_string. PLEASE RESPECT THESE RIGHTS. Your device appears to be located in $location_string --><iframe src='//player.pbs.org/widget/partnerplayer/$media_id/?chapterbar=false' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>";
  } else {
    $thumbnail = !empty($_POST['thumbnail']) ? $_POST['thumbnail'] : $api->assets_url . 'img/mezz-default.gif';
    $return = "<div class='video-wrap dma-fail'><img src='$thumbnail'><div class='sorry'><div class='sorry-txt'><h3>Sorry, this content is only available to viewers within our broadcast area*.</h3><p>Check your <a href='https://www.pbs.org/tv_schedules/' target='_blank'>local PBS listings</a> to find out where you can watch.</p><p>*Our broadcast area contains the following counties:  $allowed_counties_string.</p><p>Your device appears to be located in $location_string. If this is not correct, it may be due to VPN software on your device, or mis-reporting of your location by your network or cable provider.</p></div></div></div>";
  }
}

// extra slash stripping in case we're using PHP 5.3
$json = stripslashes(json_encode($return));
header('content-type: application/json; charset=utf-8');
exit($json);

# JSONP if valid callback
//exit("{$_GET['callback']}($json)");
