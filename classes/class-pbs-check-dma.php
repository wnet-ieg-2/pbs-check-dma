<?php
/* Core functions such as post and taxonomy definition and a basic interface for 
*  retrieving pbs localize in PHP or via an AJAX call
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class PBS_Check_DMA {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
  private $token;
  public  $version;

	public function __construct($file) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->token = 'pbs_check_dma';
    $this->version = '0.1';

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    // make calls to pbs premium content use our custom AJAX templates
    add_action( 'init', array($this, 'setup_rewrite_rules') );
    add_filter( 'query_vars', array($this, 'register_query_vars') );
    add_filter( 'template_include', array( $this, 'use_custom_template' ) );

    // Setup the shortcode
    add_shortcode( $this->token, array($this, 'do_shortcode') );

	}

  public function enqueue_scripts() {
    /* only stuff we have to enqueue in header */
    // colorbox is a common script so lets avoid conflicts and use whatever version is registered if one is already
    if (! wp_script_is( 'colorbox', 'registered' ) ) {
      wp_register_script( 'colorbox', $this->assets_url . 'js/jquery.colorbox-min.js', array('jquery'), '1.6.3', true );
      // base colorbox styling 
      wp_enqueue_style( 'colorbox_css', $this->assets_url . 'css/colorbox.css' );
    }
    wp_enqueue_script( 'colorbox' );
  }

  public function conditionally_enqueue_scripts() {
    wp_register_script( 'pbs_check_dma_js' , $this->assets_url . 'js/pbs_check_dma.js', array('colorbox'), $this->version, true );
    wp_enqueue_script( 'pbs_check_dma_js' );
  }

  // these next functions setup the custom endpoints

  public function setup_rewrite_rules() {
    add_rewrite_rule( 'pbs_check_dma/?.*$', 'index.php?pbs_check_dma=true', 'top');
  }

  public function register_query_vars( $vars ) {
    $vars[] = 'pbs_check_dma';
    return $vars;
  }

  public function use_custom_template($template) {
    if ( get_query_var('pbs_check_dma')==true) {
      $template = trailingslashit($this->dir) . 'templates/endpoint-template.php' ;
    }
    return $template;
  }



  public function do_shortcode( $atts ) {
    // pull some things from sitewide settings if not set by the shortcode
    $defaults = get_option($this->token);
    $allowed_args = array(
      'station_call_letters' => $defaults['station_call_letters'],
      'ip_endpoint' => home_url('pbs_check_dma'),
      'mismatch_dma_showdiv' => '#mismatch_dma_showdiv',
      'match_dma_showdiv' => '#match_dma_showdiv',
      'render_template' => false 
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
    if ($args['render_template']) {
      //$layout = '<div id="mismatch_dma_showdiv"></div><div id="match_dma_showdiv"></div>';
    }
    $return = $jsonblock . $style . $layout;
    return $return;
  }
}
