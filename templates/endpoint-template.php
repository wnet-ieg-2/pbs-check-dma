<?php

show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$api = new PBS_Check_DMA();

$return = array();

if (empty($_POST['media_id'])) {
  $return['client_ip_address'] = $api->get_remote_ip_address();
} else {
  $apicheck = $api->visitor_is_in_dma();
  if ($apicheck) {
    $media_id = $_POST['media_id'];
    $return = "<div class='video-wrap'><!-- DUE TO CONTRACTUAL OBLIGATIONS TO THE PRODUCERS, CAST, AND CREW OF THE PERFORMANCE RECORDED IN THE VIDEO BELOW, IT IS PROHIBITED TO PLAY THE VIDEO BELOW ON DEVICES THAT ARE NOT PHYSICALLY LOCATED WITHIN THE WNET/THIRTEEN BROADCAST AREA. THIS AREA IS COMPRISED OF THE FOLLOWING COUNTIES: NY: Bronx, Dutchess, Kings, Nassau, New York, Orange, Putnam, Queens, Richmond, Rockland, Suffolk, Sullivan, Ulster, Westchester;  NJ: Bergen, Essex, Hudson, Hunterdon, Middlesex, Monmouth, Morris, Ocean, Passaic, Somerset, Sussex, Union, Warren; CT: Fairfield; PA: Pike. PLEASE RESPECT THESE RIGHTS. --><iframe src='//player.pbs.org/widget/partnerplayer/$media_id/?chapterbar=false' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>";
  } else {
    $return = "<div class='video-wrap'><div class='sorry'><h3>Sorry, this content is only available to viewers within our broadcast area*.</h3><p>Check your local PBS listings to find out where you can watch.</p><p>*Our broadcast area contains the following counties:  NY: Bronx, Dutchess, Kings, Nassau, New York, Orange, Putnam, Queens, Richmond, Rockland, Suffolk, Sullivan, Ulster, Westchester;  NJ: Bergen, Essex, Hudson, Hunterdon, Middlesex, Monmouth, Morris, Ocean, Passaic, Somerset, Sussex, Union, Warren; CT: Fairfield; PA: Pike.</p></div></div>";
  }
}

// extra slash stripping in case we're using PHP 5.3
$json = stripslashes(json_encode($return));
header('content-type: application/json; charset=utf-8');
exit($json);

# JSONP if valid callback
//exit("{$_GET['callback']}($json)");


