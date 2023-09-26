<?php
/* Core functions an a basic interface for 
*  retrieving pbs localize in PHP or via an AJAX call
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class PBS_Check_DMA {
	private $dir;
	private $file;
	private $assets_dir;
	public $assets_url;
  public $token;
  public  $version;

	public function __construct() {
    $this->dir = realpath( __DIR__ . '/..' );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = trailingslashit(plugin_dir_url( __DIR__ ) ) . 'assets';
    $this->token = 'pbs_check_dma';
    $this->version = '0.94';

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    // make calls to pbs premium content use our custom AJAX templates
    add_action( 'init', array($this, 'setup_rewrite_rules') );
    add_filter( 'query_vars', array($this, 'register_query_vars') );
    add_filter( 'template_include', array( $this, 'use_custom_template' ) );

    add_shortcode( 'dma_restricted_player', array( $this, 'render_shortcode' ) );

	}


  public function enqueue_scripts() {
    // I generally only do this on a specific page that need it
    wp_register_script( 'pbs_check_dma_js' , $this->assets_url . '/js/pbs_check_dma.js', array('jquery'), $this->version, true );
    wp_enqueue_script( 'pbs_check_dma_js' );
    wp_enqueue_style('pbs_check_dma_css', $this->assets_url . '/css/pbs_check_dma.css', null, $this->version);
  }

  // these next functions setup the custom endpoints

  public function setup_rewrite_rules() {
    add_rewrite_rule( 'pbs_check_dma/?.*$', 'index.php?pbs_check_dma=1', 'top');
  }

  public function register_query_vars( $vars ) {
    $vars[] = 'pbs_check_dma';
    return $vars;
  }

  public function use_custom_template($template) {
    if ( get_query_var('pbs_check_dma')==true ) {
      $template = trailingslashit($this->dir) . 'templates/endpoint-template.php' ;
    }
    return $template;
  }

  public function get_location_from_ip($client_ip) {
    $zip_url = 'https://services.pbs.org/zipcodes/ip/';
    $combined_url = $zip_url . $client_ip . '.json';
    $response = wp_remote_get($combined_url, array());
    if ( is_array( $response ) ) {
      $header = $response['headers']; // array of http header lines
      $body = $response['body']; // use the content
    } else {
      return array('errors' => $response);
    }
    if ($body) {
      $parsed = json_decode($body, TRUE);
      $item = !empty($parsed['$items'][0]) ? $parsed['$items'][0] : false;
      if (!$item) {
        return array('errors' => $response);
      }
      $zipcode = !empty($item['zipcode']) ? (string) $item['zipcode'] : '';
      $state = '';
      $county = '';
      if (empty($item['$links']) || !is_array($item['$links'])) {
        return array('errors' => $response);
      }
      foreach ($item['$links'] as $link) {
        if ($link['$relationship'] == "related") {
          $state = !empty($link['$items'][0]['$links'][0]['state']) ? $link['$items'][0]['$links'][0]['state'] : '';
          $county = !empty($link['$items'][0]['county_name']) ? $link['$items'][0]['county_name'] : '';
          break;
        }
      }
      $country = !empty($zipcode) ? 'USA' : 'Outside of the US'; // the PBS endpoint returns a 404 for non-US IP addresses
      $return = array('zipcode' => $zipcode, 'state' => $state, 'county' => $county, 'country' => $country);
      return $return;
    }
  }

  public function get_remote_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      return $_SERVER['HTTP_CLIENT_IP'];

    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // this can return a list, we only want the first one
      $ip_array=explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      $last_ip = trim(array_shift($ip_array));
      return $last_ip;
    }
    return $_SERVER['REMOTE_ADDR'];
  }


  public function format_counties_setting_for_use() {
    $defaults = get_option($this->token);
    if (empty($defaults["state_counties_array"])) {
      return false;
    }
    $raw_counties = $defaults["state_counties_array"];
    $returnary = array();
    foreach ($raw_counties as $stateary) {
      $thisstate = $stateary['state'];
      $thesecounties = $stateary['counties'];
      $countylist = array_map('trim', explode(",", $thesecounties));
      $returnary[$thisstate] = $countylist; 
    }
    return $returnary; 
  }

  public function get_location_by_reverse_geocode($latitude, $longitude, $provider=false) {
    $defaults = get_option($this->token);
    if (!$provider) {
      $provider = !empty($defaults["reverse_geocoding_provider"]) ? $defaults["reverse_geocoding_provider"] : '';
      $provider = $provider == "no_provider" ? '' : $provider;
    }
    $authentication = !empty($defaults["reverse_geocoding_authentication"]) ? $defaults["reverse_geocoding_authentication"] : "";
      
    switch($provider) {
      case "here.com" :
        $requesturl = "https://revgeocode.search.hereapi.com/v1/revgeocode";
        $requesturl .= "?apiKey=" . $authentication;
        $requesturl .= "&at=$latitude,$longitude";
        break;
      case "fcc.gov" :
        $requesturl = "https://geo.fcc.gov/api/census/area?";
        $requesturl .= "&lat=$latitude&lon=$longitude";
        $requesturl .= "&format=json";
        break;
      case "google" :
        $requesturl = "https://maps.googleapis.com/maps/api/geocode/json";
        $requesturl .= "?key=" . $authentication;
        $requesturl .= "&latlng=$latitude,$longitude";
        break;
    }
    // other providers TK
    if (empty($requesturl)) {
      return array("errors" => "no reverse geolocation url can be constructed with provider $provider");
    }
 
    $response = wp_remote_get($requesturl);
    if ( is_array( $response ) ) {
      $headers = wp_remote_retrieve_headers($response); // array of http header lines
      $body = $response['body']; // use the content
    } else {
      return array('errors' => $response);
    }
    $response_code = wp_remote_retrieve_response_code( $response );
    if ($response_code && $response_code > 308) {
      return array('errors' => $response);
    }
    $parsed = json_decode($body, TRUE);
    if (!$parsed) {
      return array('errors' => 'json_decode error on response body', 'response' => $response);
    }

    // every provider has a different result format.  Format for our needs.
    switch($provider) {
      case "here.com" :
        if (empty($parsed["items"][0]["address"])) {
          return array('errors' => "No address", 'response' => $parsed["Response"]);
        }
        $address = $parsed["items"][0]["address"];
        $zipcode = $address["postalCode"];
        if (strlen($zipcode) > 5) {
          // zip+4 doesn't work with PBS
          $zipcode = substr($zipcode, 0, 5);
        }   
        $county = isset($address["county"]) ? $address["county"] : $address["district"]; 
        $return = array("zipcode" => $zipcode, "state" => $address["stateCode"], "county" => $county, "country" => $address["countryCode"]);
        break;
      case "fcc.gov" :
        if (empty($parsed["results"][0]["county_name"])) {
          // FCC.gov returns no results for anything outside of the US, which is fine
          return array('errors' => "No county", 'response' => $parsed);
        }
        $result = $parsed["results"][0];
        $return = array("state" => $result["state_code"], "county" => $result["county_name"], "country" => "USA");
        break;
      case "google" :
        if (empty($parsed["results"][0]["address_components"])) {
          return array('errors' => "No address", 'response' => $parsed);
        }
        // loop through the components to assign the pieces
        $return = array();
        foreach ($parsed["results"][0]["address_components"] as $address_component) {
          $component_type = $address_component["types"][0]; // the second element is usually 'political'
          switch ($component_type) {
            case "postal_code" :
              $return["zipcode"] = substr($address_component["short_name"], 0, 5);
              break;
            case "country" :
              $return["country"] = $address_component["short_name"];
              $return["country_code"] = $address_component["short_name"];
              $return["country_name"] = $address_component["long_name"];
              break;
            case "administrative_area_level_1" :
              $return["state"] = $address_component["short_name"];
              break;
            case "administrative_area_level_2" :
              $return["county"] = $address_component["short_name"];
              break;
          }
        }
        break;
    }
    return $return;

  }

  public function compare_county_to_allowed_list($location) {
    /* this assumes an array with elements 'state' and 'county'
     * which it compares to the corresponding values in the settings
     * theoretically there may be some non-US country that has a state abbreviation 
     * that matches our setting, and a corresponding county in that 'state'
     * but this is highly unlikely */
    $state = !empty($location['state']) ? $location['state'] : '';
    $county = !empty($location['county']) ? $location['county'] : '';
    if (empty($location['state']) || empty($location['county'])) {
      return false;
    }
    $defaults = get_option($this->token);
    $counties = $this->format_counties_setting_for_use();
    if (!isset($counties[$state])) {
      return false;
    }
    $these_counties = $counties[$state];
    return in_array(strtolower($county), array_map('strtolower', $these_counties));
  }

  public function callsign_available_in_zipcode($zipcode, $desired_callsign = '') {
    /* returns true if callsign is available and a primary in zipcode, otherwise false */
    if (empty($zipcode) || !(preg_match('/^(\d{5})?$/', $zipcode))) {
      // zipcode is either empty or isn't 5 digits
      return false;
    }
    if (empty($desired_callsign)) {
      $defaults = get_option($this->token);
      $desired_callsign = !empty($defaults['station_call_letters']) ? $defaults['station_call_letters'] : '';
      if (empty($desired_callsign)) {
        // configuration error
        error_log($this->token . " requires station_call_letters to be set, see settings page");
        return false;
      }
    }

    $available_callsigns = $this->list_available_callsigns_in_zipcode($zipcode);
    if (in_array($desired_callsign, $available_callsigns)) {
      return true;
    }
    // no luck finding the desired callsign
    return false; 
  }

  public function list_available_callsigns_in_zipcode($zipcode) {
    if (empty($zipcode) || !(preg_match('/^(\d{5})?$/', $zipcode))) {
      // zipcode is either empty or isn't 5 digits
      return false;
    }
    $callsign_url = "https://services.pbs.org/callsigns/zip/";
    $combined_url = $callsign_url . $zipcode . '.json';
    $call_sign = false;
    $response = wp_remote_get($combined_url, array());
    if (! is_array( $response ) || empty($response['body'])) {
      return array('errors' => $response);
    }
    $body = $response['body']; // use the content
    $parsed = json_decode($body, TRUE);
    $available_callsigns = [];
    foreach($parsed['$items'] as $key) {
      foreach($key['$links'][0]['$links'] as $link) {
        if (isset( $link['$links'] )) {
          foreach($link['$links'] as $i) {
            if($i['$relationship'] == "flagship"){
              if(  $key['confidence'] == 100  ){
                $call_sign = $key['$links'][0]['callsign'];
                array_push($available_callsigns, $call_sign);
              }
            }
          }
        }
      }
    }
    return $available_callsigns;
  }

  public function list_available_station_ids_in_zipcode($zipcode) {
    if (empty($zipcode) || !(preg_match('/^(\d{5})?$/', $zipcode))) {
      // zipcode is either empty or isn't 5 digits
      return false;
    }
    $callsign_url = "https://services.pbs.org/callsigns/zip/";
    $combined_url = $callsign_url . $zipcode . '.json';
    $call_sign = false;
    $response = wp_remote_get($combined_url, array());
    if (! is_array( $response ) || empty($response['body'])) {
      return array('errors' => $response);
    }
    $body = $response['body']; // use the content
    $parsed = json_decode($body, TRUE);
    $available_station_ids = [];
    foreach($parsed['$items'] as $key) {
      foreach($key['$links'][0]['$links'] as $link) {
        if (isset( $link['$links'] )) {
          foreach($link['$links'] as $i) {
            if($i['$relationship'] == "flagship"){
              if(  $key['confidence'] == 100  ){
                $station_id = $link['pbs_id'];
                array_push($available_station_ids, $station_id);
              }
            }
          }
        }
      }
    }
    return $available_station_ids;
  }


  public function visitor_ip_is_in_dma() {
    $ip = $this->get_remote_ip_address();
    $location = $this->get_location_from_ip($ip);
    $defaults = get_option($this->token);
    if (!empty($defaults['use_pbs_location_api'])) {
      $in_dma = $this->callsign_available_in_zipcode($location['zipcode'], $defaults['station_call_letters']); 
    } else {
      $in_dma = $this->compare_county_to_allowed_list($location);
    }
    return array($in_dma, "location" => $location);
  }

  public function build_dma_restricted_player($video, $mezzanine = '') {
    /* this function accepts two styles of args for historical reasons
     * one assumes that $video will be an object that 
     * contains at least two elements: tp_media_object_id and mezzanine
     * the other style is that $video will be a tp_media_object_id 
     * and $mezzanine will be its own var */
    if (is_object($video) && isset($video->tp_media_object_id)) {
      $tp_media_object_id = $video->tp_media_object_id;
      $metadata = json_decode($video->metadata);
      $mezzanine = !empty($metadata->mezzanine) ? $metadata->mezzanine : $mezzanine;
    } else {
      $tp_media_object_id = $video;
    }

    // video poster image.
    if (empty($mezzanine)) {
      $large_thumb = $this->assets_url . "/img/mezz-default.gif";
    } else {
      // make sure img url doesn't already contain a resize argument.
      $resized = strpos($mezzanine, '.resize.');
      $cropped = strpos($mezzanine, '.crop.');
      // newer style
      $altcropped = strpos($mezzanine, '?crop=');
      // is it even from pbs.org?
      $pbsimage = strpos($mezzanine, 'image.pbs.org');
      if ($pbsimage !== false) {
        if ($resized === false && $cropped === false && $altcropped === false) {
            $mezzanine = $mezzanine . '?crop=1200x675&format=jpg';
        }
      }
      $mezzanine = str_replace("http://", "//", $mezzanine);
      $large_thumb = $mezzanine;
    }
    $player = '<div class="dmarestrictedplayer" data-media="'.$tp_media_object_id.'"><img src="'.$large_thumb.'" /></div>';
    $this->enqueue_scripts();
    return $player;
  }

  public function render_shortcode( $atts = array() ) {
    /* this just renders a static div for AJAX to act on later */
    global $post;
    $args = shortcode_atts(array(
      'post_id' => $post->ID),
      $atts);
    $post_id = $args['post_id'];

    $return = "";
    $postmeta = get_post_custom($post_id);
    if (!empty($postmeta['dma_restricted_video_uri'][0])) {
      $defaults = get_option($this->token);
      $jwplayer_uri = !empty($defaults['jwplayer_uri']) ? trim($defaults['jwplayer_uri']) : '';
      $data_type = "custom_hls";
      if (empty($jwplayer_uri)) { 
        $data_type = "custom_mp4";
      }
      $mezz_image = $this->assets_url . "/img/mezz-default.gif";
      if (!empty($postmeta['dma_restricted_video_image'][0])) {
        $mezz_image = $postmeta['dma_restricted_video_image'][0];
      }
      $return = '<div class="dmarestrictedplayer program-player" data-media="' . $data_type . '" data-postid="' . $post_id . '"><img src="'.$mezz_image.'" /></div><link rel=stylesheet media="all" type="text/css" href="' . $this->assets_url . '/css/pbs_check_dma.css?version=' . $this->version . '" /><script src="' . $this->assets_url . '/js/pbs_check_dma.js?version=' . $this->version . '"></script>';
      if ($data_type ==  "custom_hls") {
        $return .= "<script src='$jwplayer_uri'></script>";
      } else {
        $return .="<link rel=stylesheet media='all' type='text/css' href='https://vjs.zencdn.net/8.3.0/video-js.css'/><style> .vjs-poster{ background-size: 100% !important; } </style><script src='https://vjs.zencdn.net/8.3.0/video.min.js'></script>";
      }
     }
    return $return;
  }

}
// END OF FILE
