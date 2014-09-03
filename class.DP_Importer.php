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
      if( ! $this->detect_jetpack_portfolio() ) {
        $this->jetpack_portfolio_polyfill();
      }

      add_action( 'admin_init', array( $this, 'add_admin_settings' ), 14 );

      // Add Notice if they haven't entered a username
      if( ! get_option( self::USERNAME_OPTION ) ) { // @TODO: Change this an actual check for validity
        add_action( 'admin_notices', array( $this, 'need_username_notice' ) );
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

      $feed = fetch_feed( 'http://dribbble.com/' . $user . '/shots.rss' );
      $feed = $feed->get_items(0);

      set_transient( self::DPI_TRANSIENT, TRUE, HOUR_IN_SECONDS );

      $shots = array();
      foreach ( $feed as $item ) {
        $shots[ $item->get_date( 'Ymd' ) ] = array(
          'id'          => $item->get_date( 'Ymd' ),
          'url'         => esc_url( $item->get_permalink() ),
          'date'        => $item->get_date( 'Y-m-d H:i:s' ),
          'title'       => esc_html( $item->get_title() ),
          'image'       => self::get_image( $item->get_description() ),
          'description' => $item->get_description(),
        );
      }

      foreach ($shots as $shot) {
        self::import_dribbble_item( $shot );
      }

    }


    /**
     * Do the work of importing the dribbble item as a post
     *
     * @since   1.0.0
     */
    private static function import_dribbble_item($item) {

      if( ! $item[ 'title' ] || ! $item[ 'date' ] || ! $item[ 'image' ] )
        return;

      $shot_post = array(
        'post_type'   => self::PORTFOLIO_CPT,
        'post_date'   => $item[ 'date' ],
        'post_status' => 'publish',
        'post_content'=> $item[ 'description' ],
        'post_author' => 1,
        'post_title'  => $item[ 'title' ],
      );

      $shot_post_meta = array(
        'link_url'    => $item[ 'url' ],
        'image'       => $item[ 'image' ],
      );

      $posts = get_posts(
        array(
          'post_type' => 'jetpack-portfolio',
          'meta_key'  => 'dribbble_link_url',
          'meta_value'=> $shot_post_meta[ 'link_url' ],
        )
      );

      if ( ! $posts ) {

        $post_id = @wp_insert_post( $shot_post, true );

        if ( is_wp_error( $post_id ) ) {

          $error_string = $post_id->get_error_message();
          echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';

        } else {

          update_post_meta( $post_id, 'dribbble_link_url', $shot_post_meta[ 'link_url' ] );

          if( $shot_post_meta[ 'image' ] ){

            $parent_post_id = $post_id;

            // gives us access to the download_url() and wp_handle_sideload() functions
            require_once(ABSPATH . 'wp-admin/includes/file.php');

            // external image path
            $url = $shot_post_meta[ 'image' ];
            $timeout_seconds = 5;

            // download file to temp dir
            $temp_file = download_url( $url, $timeout_seconds );

            if ( is_wp_error( $temp_file ) ) {

              $error_string = $temp_file->get_error_message();
              echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';

            } else {

              // array based on $_FILE as seen in PHP file uploads
              $file = array(
                'name'     => basename( $url ), // ex: wp-header-logo.png
                'type'     => 'image/png',
                'tmp_name' => $temp_file,
                'error'    => 0,
                'size'     => filesize($temp_file),
              );

              $overrides = array(
                // tells WordPress to not look for the POST form
                // fields that would normally be present, default is true,
                // we downloaded the file from a remote server, so there
                // will be no form fields
                'test_form' => false,

                // setting this to false lets WordPress allow empty files, not recommended
                'test_size' => true,

                // A properly uploaded file will pass this test.
                // There should be no reason to override this one.
                'test_upload' => true,
              );

              // move the temporary file into the uploads directory
              $results = wp_handle_sideload( $file, $overrides );

              if ( ! empty( $results[ 'error' ] ) ) {
                // insert any error handling here
              } else {

                $filename  = $results[ 'file' ]; // full path to the file
                $local_url = $results[ 'url' ]; // URL to the file in the uploads dir
                $type      = $results[ 'type' ]; // MIME type of the file

                // Get the path to the upload directory.
                $wp_upload_dir = wp_upload_dir();

                // Prepare an array of post data for the attachment.
                $attachment = array(
                  'guid'           => $wp_upload_dir[ 'url' ] . '/' . basename( $filename ),
                  'post_mime_type' => $type,
                  'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                  'post_content'   => '',
                  'post_status'    => 'inherit'
                );

                $attachment_id = wp_insert_attachment( $attachment, $filename, $parent_post_id, true );

                // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
                require_once( ABSPATH . 'wp-admin/includes/image.php' );

                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
                wp_update_attachment_metadata( $attachment_id, $attach_data );

                if( $attachment_id ){
                  set_post_thumbnail( $parent_post_id, $attachment_id );
                }

              }

            }

          }

        }

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
        require_once( trailingslashit( DPI__PLUGIN_DIR ) . 'class.Freefolio.php' );
    }


    /**
     * Load language files
     *
     * @since   1.0.0
     */
    public static function plugin_textdomain() {
      load_plugin_textdomain( 'freefolio', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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
        '<span class="cpt-options dribbble-username-label">' . __( 'Dribbble Username', 'freefolio' ) . '</span>',
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
      echo '<div class="update-nag"><p>' . sprintf( __( 'The Dribbble portfolio importer requires a valid Dribbble username to work. <a href="%s">Add it now</a>', 'freefolio' ) , $url ) . '</p></div>';
    }


  }
}
