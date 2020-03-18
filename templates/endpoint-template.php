<?php

show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$api = new PBS_Check_DMA();
$defaults = get_option($api->token);
$station_common_name = !empty($defaults['station_common_name']) ? $defaults['station_common_name'] : ""; 
$return = array();

if (empty($_POST['media_id'])) {
  $return['client_ip_address'] = $api->get_remote_ip_address();
} else {
  $in_dma = false;
  // check a cookie for the location; will include zip, county, state, country
  if (!empty($_COOKIE['dmalocation'])) {
    $raw_location = $_COOKIE['dmalocation'];
    $location = json_decode(stripslashes($raw_location), TRUE);
    $in_dma = $api->compare_county_to_allowed_list($location);
  } else {
    // no cookie? check the ip
    $ipcheck = $api->visitor_ip_is_in_dma();
    $in_dma = $ipcheck[0];
    $location = $ipcheck['location'];
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : '';
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : '';
    if ($in_dma) {
      // visitor is in the dma
      // but make sure to strip out zipcode, that's a little personal
      unset($location['zipcode']);
      setcookie('dmalocation', json_encode($location, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, '/');
    } else {
      if ($defaults['reverse_geocoding_provider'] == 'no_provider') {
        // don't bother trying to get the lat/lng because there's no way to look up the location
        // set location cookie
        // but make sure to strip out zipcode, that's a little personal
        unset($location['zipcode']);
        setcookie('dmalocation', json_encode($location, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, '/');
      } else {
        if (empty($latitude) && empty($longitude)) {
        // no lat/lng passed? 
          if (!empty($_POST['declined_location'])) {
          $location["declined_location"] = TRUE;
          // have we requested a lat/lng already? refusal will be a browser-set cookie
          // but make sure to strip out zipcode, that's a little personal
            unset($location['zipcode']);
            setcookie('dmalocation', json_encode($location, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, '/');
            // set location cookie and display the sorry page
          } else {
          // else add an element to the output to prompt a lan/lng request
            $return["request_browser_location"] = true;
          }
        } else {
        // else lookup the location using lat/lng
          $location_request = $api->get_location_by_reverse_geocode($latitude, $longitude);
          if (empty($location_request['errors']) && !empty($location_request['county'])) {
            $location = $location_request;
          }
          $in_dma = $api->compare_county_to_allowed_list($location);
          // set location cookie
          // but make sure to strip out zipcode, that's a little personal
          unset($location['zipcode']);
          setcookie('dmalocation', json_encode($location, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, '/');
        }
      }
    }
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
  if ($location) {
    foreach ($location as $key => $var) {
      if ($key == "errors" || $key == "declined_location") {
        continue;
      }
      if ($location_string) {
        $location_string .= "; ";
      }
      $location_string .= "$key: $var";
    }
  }
  $location_string = !empty($location_string) ? $location_string : "someplace where we cannot determine your US state and county";
  if ($in_dma) {
    $media_id = $_POST['media_id'];
    // Partner Player
    $playerstring = "<div class='video-wrap'><iframe src='//player.pbs.org/widget/partnerplayer/$media_id/?chapterbar=false&endscreen=false' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>";
    if (!empty($_POST['postid'] && $media_id == 'custom_hls')) {
      $postmeta = get_post_meta($_POST['postid']);
      if (!empty($postmeta['dma_restricted_video_uri'][0])) {
        // JW Player if there's a custom HLS
        $playerstring = "<div id = 'custom_hls_player' data-hls='" . $postmeta['dma_restricted_video_uri'][0] . "'></div>";
      }
    }
    $return["output"] = "<!-- DUE TO CONTRACTUAL OBLIGATIONS, IT IS PROHIBITED TO PLAY THE VIDEO BELOW ON DEVICES THAT ARE NOT PHYSICALLY LOCATED WITHIN OUR BROADCAST AREA. THIS AREA IS COMPRISED OF THE FOLLOWING COUNTIES: $allowed_counties_string. PLEASE RESPECT THESE RIGHTS. Your device appears to be located in $location_string -->$playerstring";
  } else {
    // it is almost impossible for thumbnail to be empty
    $thumbnail = !empty($_POST['thumbnail']) ? $_POST['thumbnail'] : $api->assets_url . 'img/mezz-default.gif';

    if (isset($location['declined_location'])) {
      $location_string .= ". If that is not correct, we can get more accurate data if you allow us to access your location when prompted.";
    } else {
      $location_string .= ". If this is not correct, it may be due to VPN software that may be installed on your device, or mis-reporting of your location by your internet or cable provider."; 
    }
    $return["output"] = "<div class='video-wrap dma-fail'><img src='$thumbnail'><div class='sorry'><div class='sorry-txt'><h3>Sorry, this content is only available to viewers within our broadcast area*.</h3><p>Check your <a href='https://www.pbs.org/tv_schedules/' target='_blank'>local PBS listings</a> to find out where you can watch.</p><p>*Our broadcast area contains the following counties:  $allowed_counties_string.</p><p>Your device appears to be located in $location_string</p></div></div></div>";
  }
}

// extra slash stripping in case we're using PHP 5.3
$json = stripslashes(json_encode($return));
header('content-type: application/json; charset=utf-8');
exit($json);

