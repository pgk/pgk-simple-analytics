<?php
/*
Plugin Name:  Pgk Simple Analytics
Plugin URI:   https://kountanis.com
Description:  Simple Analytics plugin.
Version:      1.1.0
Author:       pgk
Author URI:   https://kountanis.com
License:      GPL2
Text Domain:  pgk-simple-analytics
*/


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * easily add analytics pixels to your wp site.
 */
class PgkSimpleAnalytics {
    const SIMPLE_ANALYTICS            = 'pgk_simple_analytics';
    const SIMPLE_ANALYTICS_GROUP      = 'pgk_simple_analytics_group';
    const SIMPLE_ANALYTICS_PAGE       = 'simple-analytics-admin';
    const SIMPLE_ANALYTICS_PAGE_TITLE = 'Simple Analytics Settings';
    const GA_ANALYTICS_ID             = 'ga_analytics_id';
    const FB_ANALYTICS_ID             = 'fb_analytics_id';

    private $options;

    public function __construct() {
      if ( is_admin() ) {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
      }

      add_action( 'wp_footer', array( $this, 'render_analytics' ) );
    }

    public function render_analytics() {
      if ( is_admin() ) {
        return;
      }

      $options = get_option( self::SIMPLE_ANALYTICS );

      if ( empty( $options ) ) {
	      return;
      }

      $this->maybe_render_fb_pixel( $options );
      $this->maybe_render_ga_pixel( $options );

    }

    private function maybe_render_ga_pixel( $options ) {
        if ( ! isset( $options[self::GA_ANALYTICS_ID] ) || 
             empty( $options[self::GA_ANALYTICS_ID] ) ) {
            return;
        }

        $tracking_code = esc_js( $options[self::GA_ANALYTICS_ID] );

        ?>
      <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
      ga('create', '<?php echo $tracking_code ?>', 'auto');
      ga('send', 'pageview');
    </script>
<?php
    }

    private function maybe_render_fb_pixel( $options ) {
	    $pixel_id = isset( $options[self::FB_ANALYTICS_ID] ) ? $options[self::FB_ANALYTICS_ID] : null;
	    if ( empty( $pixel_id ) ) {
		    return;
	    }
?>
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '<?php echo esc_js( $pixel_id ) ?>');
  fbq('track', 'PageView');
</script>
<noscript>
  <img height="1" width="1" style="display:none" 
       src="https://www.facebook.com/tr?id=<?php echo esc_url( $pixel_id ) ?>&ev=PageView&noscript=1"/>
</noscript>
<?php
    }

    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            self::SIMPLE_ANALYTICS_PAGE_TITLE, 
            'manage_options', 
            self::SIMPLE_ANALYTICS_PAGE, 
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        $this->options = get_option( self::SIMPLE_ANALYTICS );
        ?>
        <div class="wrap">
            <h1><?php echo self::SIMPLE_ANALYTICS_PAGE_TITLE; ?></h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( self::SIMPLE_ANALYTICS_GROUP );
                do_settings_sections( self::SIMPLE_ANALYTICS_PAGE );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            self::SIMPLE_ANALYTICS_GROUP, // Option group
            self::SIMPLE_ANALYTICS, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id_ga', // ID
            'Google Analytics', // Title
            array( $this, 'print_section_info' ), // Callback
            self::SIMPLE_ANALYTICS_PAGE // Page
        );

        add_settings_field(
            'ga_analytics_id', // ID
            'Google Analytics ID', // Title.
            array( $this, 'ga_analytics_id_callback' ), // Callback.
            self::SIMPLE_ANALYTICS_PAGE, // Page.
            'setting_section_id_ga' // Section.
        );

        add_settings_section(
            'setting_section_id_fb', // ID
            'Facebook Pixel', // Title
            array( $this, 'print_fb_section_info' ), // Callback
            self::SIMPLE_ANALYTICS_PAGE // Page
        );

        add_settings_field(
            'fb_analytics_id', // ID
            'Facebook Pixel', // Title.
            array( $this, 'fb_analytics_id_callback' ), // Callback.
            self::SIMPLE_ANALYTICS_PAGE, // Page.
            'setting_section_id_fb' // Section.
        );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if ( isset( $input['ga_analytics_id'] ) ) {
            $new_input['ga_analytics_id'] = sanitize_text_field( $input['ga_analytics_id'] );
	}

	if ( isset( $input['fb_analytics_id'] ) ) {
            $new_input['fb_analytics_id'] = sanitize_text_field( $input['fb_analytics_id'] );
        }

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        print 'Enter your GA UA below:';
    }

    /** 
     * Print the Section text
     */
    public function print_fb_section_info() {
        print 'Enter your FB pixel\'s base code or its ID below:';
    }

    public function ga_analytics_id_callback() {
        printf(
            '<input type="text" id="ga_analytics_id" name="%s[ga_analytics_id]" value="%s" />',
            self::SIMPLE_ANALYTICS, isset( $this->options['ga_analytics_id'] ) ? esc_attr( $this->options['ga_analytics_id']) : ''
        );
    }

    public function fb_analytics_id_callback() {
        printf(
            '<input type="text" id="ga_analytics_id" name="%s[fb_analytics_id]" value="%s" />',
            self::SIMPLE_ANALYTICS, isset( $this->options['fb_analytics_id'] ) ? esc_attr( $this->options['fb_analytics_id']) : ''
        );
    }

}

$simple_analytics = new PgkSimpleAnalytics();

register_activation_hook( __FILE__, 'flush_rewrite_rules' );

