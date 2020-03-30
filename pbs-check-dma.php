<?php
/*
 * Plugin Name: PBS Check DMA 
 * Version: 0.73
 * Plugin URI: http://ieg.wnet.org/
 * Description: Use geolocation to restrict content based on a PBS stations' DMA
 * Author: William Tam
 * Author URI: http://ieg.wnet.org/
 * Requires at least: 4.0 
 * Tested up to: 4.2.2
 * 
 * @package WordPress
 * @author William Tam 
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include plugin class files
require_once( 'classes/class-pbs-check-dma.php' );
require_once( 'classes/class-pbs-check-dma-settings.php' );

global $plugin_obj;
$plugin_obj = new PBS_Check_DMA( __FILE__ );

if ( is_admin() ) {
  $plugin_settings_obj = new PBS_Check_DMA_Settings( __FILE__ );
}

// always cleanup after yourself
register_deactivation_hook(__FILE__, 'pbs_check_dma_deactivation');

function pbs_check_dma_deactivation() {
  //tk
}

register_activation_hook(__FILE__, 'pbs_check_dma_activation');

function pbs_check_dma_activation() {
  // init the object, which will setup the object
  $plugin_obj = new PBS_Check_DMA( __FILE__ );
  $plugin_obj->setup_rewrite_rules();
  flush_rewrite_rules();    
}




