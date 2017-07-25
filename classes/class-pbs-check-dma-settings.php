<?php
/* This class handles the settings page for the plugin
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class PBS_Check_DMA_Settings { 
  private $dir;
 	private $file;
	private $assets_dir;
	private $assets_url;
  private $token;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->token = 'pbs_check_dma';

		// Register plugin settings
		add_action( 'admin_init' , array( $this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this , 'add_settings_link' ) );

	}
	
	public function add_menu_item() {
		$hook_suffix = add_options_page( 'PBS Check DMA Settings' , 'PBS Check DMA Settings' , 'manage_options' , 'pbs_check_dma_settings' ,  array( $this , 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=pbs_check_dma_settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}


	public function register_settings() {
    register_setting( 'pbs_check_dma_group', $this->token );
    add_settings_section('generalsettings', 'General Settings', array( $this, 'settings_section_callback'), $this->token );

    // you can define EVERYTHING to create, display, and process each settings field as one line per setting below.  And all settings defined in this function are stored as a single serialized object.
    add_settings_field( 'station_call_letters', 'Station Call Letters', array( $this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'station_call_letters', 'class' => 'small-text', 'label' => 'Broadcast call letters, as used by PBS and Nielsen, for the "flagship" station you want to check the DMA for.', 'default' => 'WNET') );

    add_settings_field( 'station_common_name', 'Station Common Name', array( $this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'station_common_name', 'class' => 'regular-text', 'label' => 'Common name for the station, appropriate to show up in buttons and other text replacement situations, for the "flagship" station you want to check the DMA for.', 'default' => 'THIRTEEN') );
	
	}

	public function settings_section_callback() { echo ' '; }

	public function settings_field( $args ) {
    // This is the default processor that will handle standard text input fields.  Because it accepts a class, it can be styled or even have jQuery things (like a calendar picker) integrated in it.  Pass in a 'default' argument only if you want a non-empty default value.
    $settingname = esc_attr( $args['setting'] );
    $setting = get_option($settingname);
    $field = esc_attr( $args['field'] );
    $label = esc_attr( $args['label'] );
    $class = esc_attr( $args['class'] );
    $default = ($args['default'] ? esc_attr( $args['default'] ) : '' );
    $value = (($setting[$field] && strlen(trim($setting[$field]))) ? $setting[$field] : $default);
    echo '<input type="text" name="' . $settingname . '[' . $field . ']" id="' . $settingname . '[' . $field . ']" class="' . $class . '" value="' . $value . '" /><p class="description">' . $label . '</p>';
	}

	public function settings_page() {
    if (!current_user_can('manage_options')) {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    ?>
    <div class="wrap">
      <h2>PBS Check DMA Settings</h2>
      <form action="options.php" method="POST">
        <?php settings_fields( 'pbs_check_dma_group' ); ?>
        <?php do_settings_sections( $this->token ); ?>
        <?php submit_button(); ?>
      </form>
    </div>
<h3>Usage</h3>
  TK
    <?php
  }
}
