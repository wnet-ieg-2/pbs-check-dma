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

    add_settings_field( 'use_pbs_location_api', 'Use PBS Location API', array( $this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'use_pbs_location_api', 'type' => 'checkbox', 'options' =>  array('TRUE'), 'class' => 'regular-text', 'label' => 'Check this box to use the zipcode-based PBS Location API to determine DMA eligibility instead of manually listing states/counties below') );
 
    add_settings_field( 'state_counties_array', 'Allowed Counties by State', array($this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'state_counties_array', 'type' => 'array', 'options' => array('count_source' => 'state_count', 'state' => array('label' => 'State', 'class' => 'small-text'), 'counties' => array('label' => 'Counties', 'class' => 'regular-text')),  'label' => 'IGNORED IF "Use PBS Location API" above is set to TRUE.  Configure below which the list of counties, by state, that match our DMA.  Counties should be a comma-separated list of county names (eg Kings, Bronx, Queens), states should be two-char abrevs eg NY', 'class' => 'medium-text') );
    add_settings_field( 'state_count', 'State Count', array($this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'state_count', 'type' => 'text', 'default' => 4,  'label' => 'How many states appear above', 'class' => 'small-text') );

    add_settings_field( 'reverse_geocoding_provider', 'Reverse GeoCoding Provider', array($this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'reverse_geocoding_provider', 'type' => 'select', 'options' => array('no_provider' => array('label' => 'none'), 'here.com' => array('label' => 'here.com: 250k/mo free tier'), 'fcc.gov' => array('label' => 'FCC Area and Census Block API: only returns US counties' ) ),  'label' => 'Service that will handle translating browser locations (lat/long) into state/county info. Every provider has potential API costs if many requests are made.', 'class' => 'medium-text', 'default' => 'here.com') );

    add_settings_field( 'reverse_geocoding_authentication', 'Reverse GeoCoding Authentication', array( $this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'reverse_geocoding_authentication', 'class' => 'regular-text', 'label' => 'access token, with variable names, required when making a request to the provider.  If authentication is two arguments include both separated with an &amp;, like so: app_id=somestring&amp;app_code=someotherstring') );

    add_settings_field( 'jwplayer_uri', 'JW Player URI', array( $this, 'settings_field'), $this->token, 'generalsettings', array('setting' => $this->token, 'field' => 'jwplayer_uri', 'class' => 'regular-text', 'label' => 'Full URI to the JW Player javascript, unique to your JWPlayer.com account. <b>Leave blank to use video.js</b>', 'default' => '') );

	
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
          echo '<span class="' . $class . '"><input type="checkbox" name="' . $settingname . '[' . $field . '][]" id="' . $settingname . '[' . $field . ']" value="' . $option_value . '" ' . $checked . ' />&nbsp;' . $option_label . ' </span> &nbsp; ';
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
      case "select":
        $value = (($setting[$field] && strlen(trim($setting[$field]))) ? $setting[$field] : $default);
        $optionlist = '';
        foreach($options as $optionkey => $option) {
          if (! is_array($option)) {
            $option_label = $option;
            $option_value = $option;
          } else {
            $option_label = (isset($option['label']) ? esc_attr($option['label']) : $option[0]);
            $option_value = (isset($optionkey) ? esc_attr($optionkey) : $option[0]);
          }
          $selected = ($option_value == $value) ? ' selected' : '';
          $optionlist .= "<option value='" . $option_value . "' $selected />$option_label</option>\n";
        }
        echo '<select name="' . $settingname . '[' . $field . ']" id="' . $settingname . '[' . $field . ']" class="' . $class . '">' . $optionlist . '</select>';
        echo '<label for="' . $field . '"><p class="description">' . $label . '</p></label>';
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
  <p>In order to do any DMA restriction, you must fill out the state/county fields above with the complete list of counties in your DMA/broadcast area so that the site has something to compare visitor location to.</p>
  <p>By default the plugin only checks against the PBS Localization API, which uses IP-based geolocation and is free, but can be wrong up to 10% of the time -- 1 in 10 viewers may be incorrectly blocked.  This is particularly an issue for mobile users; the reported locations of their IP addresses can be off by hundreds of miles.</p>
  <p>Selecting a Reverse GeoCoding Provider allows the use of the much more accurate 'device location API' that takes advantage of things like GPS etc. This is much better for mobile devices particularly, but all devices with a web browser will have improved results.  The device returns a latitude/longitude, which then is sent to a reverse geocoding service to be converted into a info like 'county' and 'zip'.  That's not exactly free: all of the commercial providers require signup; most have a 'free' tier (here.com is 250k requests/month, googlemaps is 40k requests/month).</p>
  <p>The FCC Area and Census Block API is a free Reverse GeoCoding Provider that does not require authentication.  It does not return zipcodes, just state and county.  It also does NOT have any implied guaranteed uptime, see the <a href="http://www.fcc.gov/developer/api-terms-of-service" target=_new>FCC API usage terms and conditions</a> for details.  Mandated disclosure: <b>This product uses the FCC Data API but is not endorsed or certified by the FCC.</b></p>
  <h4>Theme Integraton</h4>
  <p>After activating the plugin, wherever you want to render a DMA restricted video create an instance of the PBS_Check_DMA class -- eg 
<pre>
$checkdma = new PBS_Check_DMA();
$player = $checkdma->build_dma_restricted_player(sometpmediaid, url_for_a_mezz_image);
echo $player;
</pre>
("sometpmediaid" would be something like '92424242410', you'd get that from the Media Manager; and "url_for_a_mezz_image" would look something like 'https://image.pbs.org/video-assets/AIdcUYK-asset-mezzanine-16x9-ccRViYN.jpg') <br /><br />
That will write out a DIV with the class 'dmarestrictedplayer' that will enclose a thumbnail image sized to 1200x675.  That will also automatically enqueue appropriate javascript and CSS on the page to act on any div with that class.  The javascript will then act on that class to look up the location of your visitor, compare it to the list of allowed counties, and if a match is found write out a PBS 'Partner Player' using the supplied TP Media Object ID.  
</p>
<p>
For more manual control, you could also enqueue those files with 
<pre>
$checkdma = new PBS_Check_DMA();
$checkdma->enqueue_scripts();
</pre>
and manually write out that DIV with a 'data-media' property with the value being the desired TP Media Object ID, enclosing the appropriate thumb image of your choice.
</p>
    <?php
  }

  // This section is for meta boxes on the individual program pages

  public function meta_box_setup( $post_type ) {
    if ( $post_type == 'programs' || $post_type == 'page' ) {
      add_meta_box( 'pbs_check_dma', 'DMA Restriction', array( $this, 'metabox_content' ), $post_type);
    }
  }

  public function metabox_content() {
    global $post;
    $post_id = $post->ID;
    $post_data = get_post($post_id, ARRAY_A); // standardizing on an array for everything here
    $postmeta_data = get_post_custom($post_id);

    $restricted = !empty($postmeta_data['dma_restricted_video'][0]) ? $postmeta_data['dma_restricted_video'][0] : false;
    $checked = $restricted ? 'checked' : '';

    $html = '<input type="hidden" name="dma_restricted_nonce" id="dma_restricted_nonce" value="' . wp_create_nonce( 'dma_restricted_nonce' ) . '" />';
    $html .= '<input type="checkbox" name="dma_restricted_video" id="dma_restricted_video" value="true" ' . $checked . ' />';
    $html .= '<label for="dma_restricted_video">DMA-restrict videos</label><div class="description"><i>Checking this box will force any videos on this page to go through the DMA restriction setup.</i></div>';
    if ($post_data['post_type'] !== 'programs') {
      $vid_uri = !empty($postmeta_data['dma_restricted_video_uri'][0]) ? $postmeta_data['dma_restricted_video_uri'][0] : '';
      $html .= '<label for="dma_restricted_video_uri">Stream URI</label><div class="description"><i>URI for the HLS stream that will be played.</i></div>';
      $html .= '<input type="text" name="dma_restricted_video_uri" id="dma_restricted_video_uri" class="regular-text" value = ' . $vid_uri . '><br />';
      $vid_image = !empty($postmeta_data['dma_restricted_video_image'][0]) ? $postmeta_data['dma_restricted_video_image'][0] : '';
      $html .= '<label for="dma_restricted_video_image">Preview image</label><div class="description"><i>URI for 16x9 mezz image for vid preview</i></div>';
      $html .= '<input type="text" name="dma_restricted_video_image" id="dma_restricted_video_image" class="regular-text" value = ' . $vid_image . '><br />';
      $html .= "<p class='description'>You can make the DMA restricted player with the video and image above display by adding the shortcode <code>[dma_restricted_player]</code> to the content if on this page, or on other posts or pages use <br /><code>[dma_restricted_player post_id=$post_id]</code></p>";
     


    }

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
    $fields = array('dma_restricted_video', 'dma_restricted_video_uri', 'dma_restricted_video_image');
    foreach ($fields as $field) {
      $value = isset( $_POST[$field]) ? $_POST[$field] : false;
      if ($value) {
        update_post_meta( $post_id , $field, $value );
      } else {
        delete_post_meta( $post_id , $field);
      }
    }
  }



}
