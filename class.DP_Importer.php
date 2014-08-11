<?php


if( !class_exists('DP_Importer') ) {
  class DP_Importer {

    const USERNAME_OPTION = 'dpi_username';
    const DPI_TRANSIENT   = 'dpi_check_feed';
    const PORTFOLIO_CPT   = 'jetpack-portfolio';


    /**
     * Holds the singleton instance of this class
     * @since   1.0.0
     * @var     Jetpack
     */
    static $instance = false;

    /**
     * Singleton
     * @since   1.0.0
     * @static
     */
    public static function init() {
      if ( ! self::$instance ) {

        if ( did_action( 'plugins_loaded' ) ) {
          self::plugin_textdomain();
        } else {
          add_action( 'plugins_loaded', array( __CLASS__, 'plugin_textdomain' ), 99 );
        }

        self::$instance = new DP_Importer;

      }

      return self::$instance;
    }



    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since   1.0.0
     */
    private function __construct() {

      // Silently polyfill the jetpack-portfolio cpt & taxonomy stuff - plays nice with upgrading to jetpack too
      if( !$this->detect_jetpack_portfolio() ) {
        $this->jetpack_portfolio_polyfill();
      }

      add_action( 'admin_init', array($this, 'add_admin_settings'), 14 );

      // Add Notice if they haven't entered a username
      if( !get_option(self::USERNAME_OPTION) ) { // @TODO: Change this an actual check for validity
        add_action( 'admin_notices', array($this, 'need_username_notice') );
      } else {
        $this->process_dribbble_feed();
      }

    }


    /**
     * Do the actual checking/work of processing dribbble feed
     *
     * @since   1.0.0
     */
    private static function process_dribbble_feed() {

      // Check if transient exists, if so exit 
      if( get_transient( self::DPI_TRANSIENT ) ) {
        return;
      }

      // Process feed, create transient

      $user = get_option( self::USERNAME_OPTION );

      require_once( ABSPATH . WPINC . '/feed.php' );

      $feed = fetch_feed('http://dribbble.com/' . $user . '/shots.rss');
      $feed = $feed->get_items(0); 

      $shots = array();
      foreach ( $feed as $item ) {
        $shots[$item->get_date('Ymd')] = array(
          'id'  => $item->get_date('Ymd'),
          'url'   => esc_url( $item->get_permalink() ),
          'date'  => $item->get_date('Y-m-d H:i:s'),
          'title' => esc_html( $item->get_title() ),
          'image' => self::get_image($item->get_description())
        );
      }

      foreach ($shots as $shot) {
        self::import_dribbble_item($shot);
      }

    }


    /**
     * Do the work of importing the dribbble item as a post
     *
     * @since   1.0.0
     */
    private static function import_dribbble_item($item) {

      $shot_post = array(
        'post_type'   => self::PORTFOLIO_CPT,
        'post_status'   => 'publish',
        'post_author'   => 1,
        'post_title'  => $item['title'],
        'post_date'   => $item['date']
      );

      $shot_post_meta = array(
        'link_url'  =>  $item['url'],
        'image'   =>    $item['image']
      );

      $posts = get_posts( 
        array(
          'post_type' => self::PORTFOLIO_CPT,
          'meta_key'  => 'link_url',
          'meta_value'=> $shot_post_meta['link_url']
        )
      );

      if (count($posts) == 0) {   
        $post_id = wp_insert_post($shot_post);
        add_post_meta($post_id, 'dribbble_link_url', $shot_post_meta['link_url'], true);
        add_post_meta($post_id, 'dribbble_image_url', $shot_post_meta['image'], true); 

        // @TODO: Add sideload & post thumbnail addition of the image at the dribbble_image_url meta value
      }

    }


    /**
     * Get the Image from the description content.
     *
     * @since   1.0.0
     */
    private static function get_image($string) {

      preg_match_all('/<img[^>]+>/i',$string, $result);
      $img = array();
      foreach( $result[0] as $img_tag) {
        preg_match_all('/(src)=("[^"]*")/i',$img_tag, $img[$img_tag]);
      }
      return trim($img[$img_tag][2][0], '"');

    } 




    /**
     * Detect existance of Jetpack Portfolio.
     * Retruns TRUE if Jetpack Portfolio exists, and FALSE if not.
     *
     * @since   1.0.0
     * @return  BOOL
     */
    private static function detect_jetpack_portfolio() {

      if( post_type_exists( 'jetpack-portfolio' ) && taxonomy_exists( 'jetpack-portfolio-type' ) && taxonomy_exists( 'jetpack-portfolio-tag' ) ) {
        return true;
      }

      return false;

    }


    /**
     * Load Jetpack Portfolio Polyfill.
     *
     * @since   1.0.0
     */
    private static function jetpack_portfolio_polyfill() {
        require_once( DPI__PLUGIN_DIR . 'class.Jetpack_Portfolio_Polyfill.php' );
    }


    /**
     * Load language files
     *
     * @since   1.0.0
     */
    public static function plugin_textdomain() {
      load_plugin_textdomain( DPI__DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }


    /**
     * Fired from the register_activation_hook
     *
     * @static
     * @since   1.0.0
     */
    public static function on_plugin_activation( $network_wide ) {

    }

    /**
     * Fired from the register_deactivation_hook
     *
     * @static
     * @since   1.0.0
     */
    public static function on_plugin_deactivation( $network_wide ) {

    }



    /**
     * Fired from the plugins_loaded action
     *
     * @static
     * @since   1.0.0
     */
    public static function on_plugins_loaded() {


    }


    public function add_admin_settings() {

      add_settings_field(
        self::USERNAME_OPTION,
        '<span class="cpt-options dribbble-username-label">' . __( 'Dribbble Username', DPI__DOMAIN ) . '</span>',
        array( $this, 'render_dribbble_username_field' ),
        'writing',
        'jetpack_cpt_section'
      );

      register_setting(
        'writing',
        self::USERNAME_OPTION
      );

    }

    public function render_dribbble_username_field() {

      printf( '<span>http://dribbble.com/<input name="%1$s" id="%1$s" type="text" value="%2$s"/></span>',
        esc_attr( self::USERNAME_OPTION ),
        get_option( self::USERNAME_OPTION, '')
      );

    }


    public static function need_username_notice() {
      $url = admin_url( 'options-writing.php#' . esc_attr( self::USERNAME_OPTION ) );
      echo '<div class="update-nag"><p><strong>Dribbble Portfolio Importer</strong> needs a username to work. <a href="' . $url . '">Add it now</a>.</p></div>';
    }


  }
}
