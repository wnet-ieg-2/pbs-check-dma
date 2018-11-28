<?php
/* Core functions such as post and taxonomy definition and a basic interface for 
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
    $this->version = '0.4';

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    // make calls to pbs premium content use our custom AJAX templates
    add_action( 'init', array($this, 'setup_rewrite_rules') );
    add_filter( 'query_vars', array($this, 'register_query_vars') );
    add_filter( 'template_include', array( $this, 'use_custom_template' ) );

    // Setup the shortcode
    add_shortcode( $this->token, array($this, 'do_shortcode') );

	}


  public function enqueue_scripts() {
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
      $country = !empty($zipcode) ? 'USA' : 'Outside of the US'; // this endpoint returns a 404 for non-US IP addresses
      $return = array('zipcode' => $zipcode, 'state' => $state, 'county' => $county, 'country' => $country);
      return $return;
    }
  }

  public function get_remote_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];

    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
  }

  public function compare_zip_to_local_list($zip) {
    $filename = trailingslashit($this->assets_dir) . "zipary.php";
    require($filename);
    return in_array($zip, $zipary);
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
      if (empty($defaults["reverse_geocode_provider"])) {
        $provider = "here.com";
      }
    }
    $authentication = !empty($defaults["reverse_geocode_authentication"]) ? $defaults["reverse_geocode_authentication"] : "app_id=***REMOVED***&app_code=***REMOVED***";
      
    switch($provider) {
      case "here.com" :
        $requesturl = "https://reverse.geocoder.api.here.com/6.2/reversegeocode.json?gen=9&mode=retrieveAreas";
        $requesturl .= "&" . $authentication;
        $requesturl .= "&prox=$latitude,$longitude"; 
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
          return array('errors' => $response);
        }
        if (empty($parsed["Response"]["View"][0]["Result"][0]["Location"]["Address"])) {
          return array('errors' => "No address", 'response' => $parsed["Response"]);
        }
        $address = $parsed["Response"]["View"][0]["Result"][0]["Location"]["Address"];
        return array("zipcode" => $address["PostalCode"], "state" => $address["State"], "county" => $address["County"], "country" => $address["Country"]);
    }
    // other providers TK, probably will be Google
    return array("errors" => "invalid provider selected");
  }

  public function compare_county_to_allowed_list($location) {
    $state = !empty($location['state']) ? $location['state'] : '';
    $county = !empty($location['county']) ? $location['county'] : '';
    if (empty($location['state']) || empty($location['state'])) {
      return false;
    }
    $defaults = get_option($this->token);
    $counties = $this->format_counties_setting_for_use();
    if (!$counties) {
      $filename = trailingslashit($this->assets_dir) . "counties.php";
      require($filename);
      // $counties are an array in the above file
    }
    if (!isset($counties[$state])) {
      return false;
    }
    $these_counties = $counties[$state];
    return in_array(strtolower($county), array_map('strtolower', $these_counties));
  }

  public function visitor_ip_is_in_dma() {
    $ip = $this->get_remote_ip_address();
    $location = $this->get_location_from_ip($ip);
    $in_dma = $this->compare_county_to_allowed_list($location);
    //$in_dma = $this->compare_zip_to_local_list($zipcode);
    return array($in_dma, "location" => $location);
  }

  public function build_dma_restricted_player($video) {
    $imgDir = get_bloginfo('template_directory');
    $m = json_decode($video->metadata);

    // video poster image.
    if (empty($m->mezzanine)) {
      $large_thumb = $imgDir . "/libs/images/default.png";
    } else {
      if (function_exists( 'wnet_video_cove_thumb')) {
        $large_thumb = wnet_video_cove_thumb($m->mezzanine, 1200, 675);
      } else {
        $large_thumb = $m->mezzanine;
      }
    }
    $player = '<div class="dmarestrictedplayer" data-media="'.$video->tp_media_object_id.'"><img src="'.$large_thumb.'" /></div>';
    $this->enqueue_scripts();
    return $player;
  }

  public function do_shortcode( $atts ) {
    // pull some things from sitewide settings if not set by the shortcode
    $defaults = get_option($this->token);
    $allowed_args = array(
      'station_call_letters' => $defaults['station_call_letters'],
      'station_common_name' => $defaults['station_common_name'],
      'ip_endpoint' => home_url('pbs_check_dma'),
      'mismatch_dma_showdiv' => '#mismatch_dma_showdiv',
      'match_dma_showdiv' => '#match_dma_showdiv',
      'render_template' => 'false'
    );

    foreach ($allowed_args as $alrg=>$key) {
      if ( !empty($defaults[$alrg])) {
        $allowed_args[$alrg] = $defaults[$alrg];
      }
    }
    $args = array();
    if (is_array($atts)) {
      $args = shortcode_atts($allowed_args, $atts, $this->token);
    } else {
      $args = $allowed_args;
    }

    /* enqueue the supporting javascript, it should all be in the footer so its fine in a shortcode */
    $this->conditionally_enqueue_scripts();

    $json_args = stripslashes(json_encode($args));
    $jsonblock = '<script language="javascript">var pbs_check_dma_args = ' . $json_args . ' </script>';
    $style = '<style>' . file_get_contents($this->assets_dir . '/css/pbs_check_dma.css') . '</style>';
    $layout = '';
    if ($args['render_template'] === 'true') {
      $layout = '<div id="mismatch_dma_showdiv"></div><div id="match_dma_showdiv"></div>';
    }
    $return = $jsonblock . $style . $layout;
    return $return;
  }
}
