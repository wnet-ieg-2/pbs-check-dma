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

    // handle meta boxes to save/update custom images on program pages
    add_action( 'add_meta_boxes', array( $this, 'meta_box_setup' ), 20 );
    add_action( 'save_post', array( $this, 'meta_box_save' ), 1 );
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
 
    add_settings_field( 'state_counties_array', 'Allowed Counties by State', array($this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'state_counties_array', 'type' => 'array', 'options' => array('count_source' => 'state_count', 'state' => array('label' => 'State', 'class' => 'small-text'), 'counties' => array('label' => 'Counties', 'class' => 'regular-text')),  'label' => 'Configure below which the list of counties, by state, that match our DMA.  Counties should be a comma-separated list of county names (eg Kings, Bronx, Queens), states should be two-char abrevs eg NY', 'class' => 'medium-text') );
    add_settings_field( 'state_count', 'State Count', array($this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'state_count', 'type' => 'text', 'default' => 4,  'label' => 'How many states appear above', 'class' => 'small-text') );
	
	}

	public function settings_section_callback() { echo ' '; }

	public function settings_field( $args ) {
    // This is the default processor that will handle most input fields.  Because it accepts a class, it can be styled or even have jQuery things (like a calendar picker) integrated in it.  Pass in a 'default' argument only if you want a non-empty default value.
    $settingname = esc_attr( $args['setting'] );
    $setting = get_option($settingname);
    $field = esc_attr( $args['field'] );
    $label = esc_attr( $args['label'] );
    $class = esc_attr( $args['class'] );
    $type = ($args['type'] ? esc_attr( $args['type'] ) : 'text' );
    $options = (is_array($args['options']) ? $args['options'] : array('true', 'false') );
    $default = ($args['default'] ? esc_attr( $args['default'] ) : '' );
    switch ($type) {
      case "checkbox":
        // dont set a default for checkboxes
        $value = $setting[$field];
        $values = ( is_array($value) ? $values = $value : array($value) );
        foreach($options as $option) {
          // each option can be an array but doesn't have to be
          if (! is_array($option)) {
            $option_label = $option;
            $option_value = $option;
          } else {
            $option_label = (isset($option[label]) ? esc_attr($option[label]) : $option[0]);
            $option_value = (isset($option[value]) ? esc_attr($option[value]) : $option[0]);
          }
          $checked = in_array($option_value, $values) ? 'checked="checked"' : '';
          echo '<span class="' . $class . '"><input type="checkbox" name="' . $settingname . '[' . $field . ']" id="' . $settingname . '[' . $field . ']" value="' . $option_value . '" ' . $checked . ' />&nbsp;' . $option_label . ' </span> &nbsp; ';
        }
        echo '<label for="' . $field . '"><p class="description">' . $label . '</p></label>';
        break;
      case "array":
        // array is a set of key/value pairs, all as text boxes
        $value = $setting[$field];
        $values = ( is_array($value) ? $values = $value : array($value) );
        $count = !empty($options['count']) ? $options['count'] : 1;
        $count_source = !empty($options['count_source']) ? $options['count_source'] : false;
        $limit = !empty($setting[$count_source]) ? $setting[$count_source] : $count;
        $output = '';
        $output .= '<label for="' . $field . '"><p class="description">' . $label . '</p></label>';
        for ($i = 0; $i < $limit; $i++) {
          $output .= "<div>";
          if ($limit > 1) { $output .= "Item $i : ";}
          foreach($options as $optionkey => $option) {
            if ($optionkey == 'count_source' || $optionkey == 'count') {
              continue;
            }
            $option_label = (isset($option['label']) ? esc_attr($option['label']) : $option[0]);
            $option_value = (isset($option['value']) ? esc_attr($option['value']) : $option[0]);
            $option_class = (isset($option['class']) ? esc_attr($option['class']) : $class);
            $output .= '<span>' . $option_label . ' <input type="text" class= "' . $option_class . '" name="' . $settingname . '[' . $field . "][$i][" . $optionkey . ']" id="' . $settingname . '[' . $field . "][$i][" . $optionkey . ']" value="' . $values[$i][$optionkey] . '" /></span>&nbsp;';
          }
          $output .= "</div>";
        }
        echo $output;
        break;

      default:
        // any case other than selects, radios, checkboxes, or textareas formats like a text input
        $value = (($setting[$field] && strlen(trim($setting[$field]))) ? $setting[$field] : $default);
        echo '<input type="' . $type . '" name="' . $settingname . '[' . $field . ']" id="' . $settingname . '[' . $field . ']" class="' . $class . '" value="' . $value . '" /><p class="description">' . $label . '</p>';
    }
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

  // This section is for meta boxes on the individual program pages

  public function meta_box_setup( $post_type ) {
    if ( $post_type == 'programs' ) {
      add_meta_box( 'pbs_check_dma', 'DMA Restriction', array( $this, 'metabox_content' ), 'programs', 'side' );
    }
  }

  public function metabox_content() {
    global $post_id;

    $dma_restrict = get_post_meta( $post_id, 'dma_restricted_video', TRUE );

    $restricted = !empty($dma_restrict) ? $dma_restrict : false;
    $checked = $restricted ? 'checked' : '';

    $html = '<input type="hidden" name="dma_restricted_nonce" id="dma_restricted_nonce" value="' . wp_create_nonce( 'dma_restricted_nonce' ) . '" />';
    $html .= '<input type="checkbox" name="dma_restricted_video" id="dma_restricted_video" value="true" ' . $checked . ' />';
    $html .= '<label for="dma_restricted_video">DMA-restrict videos</label><div class="description"><i>Checking this box will force all videos on this program page to go through the DMA restriction setup.</i></div>';
    echo $html;
  }

  public function meta_box_save() {
    global $post_id;
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST[ 'dma_restricted_nonce'], 'dma_restricted_nonce' ) ) {
      return $post_id;
    }

    // Verify user permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return $post_id;
    }
    $value = isset( $_POST['dma_restricted_video']) ? $_POST['dma_restricted_video'] : false;
    if ($value) {
      update_post_meta( $post_id , 'dma_restricted_video', $value );
    } else {
      delete_post_meta( $post_id , 'dma_restricted_video');
    }
  }



}
