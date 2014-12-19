<?php


if( !class_exists('DP_Importer') ):
  class DP_Importer {

    const USERNAME_OPTION = 'dpi_username';
    const USERINFO_OPTION = 'dpi_userinfo';
    const DPI_TRANSIENT   = 'dpi_import';
    const PORTFOLIO_CPT   = 'jetpack-portfolio';
    const TOKEN   = 'b33fe7ed73340d8953ae1eb0d84c3bd5df14c178bb80a337be401f4467ac65aa';


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
    public function __construct() {

      // Silently polyfill the jetpack-portfolio cpt & taxonomy stuff - plays nice with upgrading to jetpack too
      if( ! $this->detect_jetpack_portfolio() ) {
        $this->jetpack_portfolio_polyfill();
      }

      // Add Notice if they haven't entered a username
      if( ! get_option( self::USERNAME_OPTION ) ) {
        //add_action( 'admin_notices', array(__CLASS__, 'need_username_notice' ) );
      }

      //register admin menu
      add_action('admin_menu', array(__CLASS__, 'dpi_create_top_level_menu') );

      //add import cron
      add_action( 'dpi_import',array( __CLASS__, 'import_shots_to_posts' ) );

      //add cron init
      add_action('admin_init', array( __CLASS__, 'import_init' ) );

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

    /**
     * Bug the user is no Dribbble username
     *
     * @static
     * @since   1.0.0
     */
    public static function need_username_notice() {
      $url = admin_url( 'admin.php?page=dpi-settings' );
      echo '<div class="update-nag"><p>' . sprintf( __( 'The Dribbble portfolio importer requires a valid Dribbble username to work. <a href="%s">Add it now</a>', 'freefolio' ) , $url ) . '</p></div>';
    }

    /**
     * Takes Dribbble username and page and gets shots from API
     *
     * @param string $username    Dribbble username
     * @param int $page    API pagination number
     *
     * @return array $results    array of json decoded results from Dribbble API
     *
     * @static
     * @since   1.1.0
     */
    private static function dribbble_get_shots($username=false,$page=1){
      //return false if no username given
      if( $username === false || empty( $username ) ){
        return false;
      }

      $api_url = 'https://api.dribbble.com/v1/users/' . $username . '/shots?sort=recent&list=attachments&per_page=30&page=' . $page;
	
      // authenticate
      $opts = array(
        'http' => array(
          'method' => 'GET',
          'header' => 'Authorization: Bearer ' . self::TOKEN,
        )
      );
      
      $context = stream_context_create($opts);
      
      $results = file_get_contents($api_url, false, $context);

      if( $results === false ){
        return false;
      } else{
        $results = json_decode($results, true);
      }

      return $results;

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
        'post_type' => self::PORTFOLIO_CPT,
        'post_date' => $item[ 'date' ],
        'post_status' => 'publish',
        'post_content'=> $item[ 'description' ],
        'post_author' => 1,
        'post_title' => $item[ 'title' ],
      );

      $shot_post_meta = array(
        'dribbble_shot_id' => $item[ 'id' ],
        'dribbble_url' => $item[ 'url' ],
        'image' => $item[ 'image' ],
      );

      $posts = get_posts(
        array(
          'post_type' => 'jetpack-portfolio',
          'meta_key' => 'dribbble_shot_id',
          'meta_value'=> $shot_post_meta[ 'dribbble_shot_id' ],
        )
      );

      if ( ! $posts ) {

        $post_id = @wp_insert_post( $shot_post, true );

        if ( is_wp_error( $post_id ) ) {

          $error_string = $post_id->get_error_message();
          echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';

        } else {

          update_post_meta( $post_id, 'dribbble_link_url', $shot_post_meta[ 'url' ] );

          update_post_meta( $post_id, 'dribbble_shot_id', $shot_post_meta[ 'dribbble_shot_id' ] );

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
                'name' => basename( $url ), // ex: wp-header-logo.png
                'type' => 'image/png',
                'tmp_name' => $temp_file,
                'error' => 0,
                'size' => filesize($temp_file),
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
                  'guid' => $wp_upload_dir[ 'url' ] . '/' . basename( $filename ),
                  'post_mime_type' => $type,
                  'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                  'post_content' => '',
                  'post_status' => 'inherit'
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
     * Initialize import
     *
     * @static
     * @since   1.1.0
     */
    public static function import_init(){

      $import_needed = get_transient( self::DPI_TRANSIENT );

      //if import isn't needed, return
      if( $import_needed != 1 ){
        return;
      }

      wp_schedule_event( time(), 'hourly', 'dpi_import' , array() );

    }

    /**
     * Gets Dribbble shots frum username and runs the import
     *
     * @static
     * @since   1.1.0
     */
    public static function import_shots_to_posts(){
      $user_name = get_option( self::USERNAME_OPTION );

      $user_info = get_option( self::USERINFO_OPTION );

      $page_count = ceil( $user_info['shots_count'] / 30 );

      for( $page=1; $page<= $page_count; $page++ ):

        //get dribble shots for page X
        $results = self::dribbble_get_shots($user_name,$page);


        if( $results === false || empty( $results ) ){
          echo '<div class="error"><p><strong>' . __('Oops - somethng went wrong with the import', 'freefolio' ) . '</strong></p></div>';
        } else{

          foreach ( $results as $item ) {

            //setup data
            $shot_import = array(
              'id' => $item['id'],
              'url' => esc_url( $item['html_url'] ),
              'date' => date('Y-m-d H:i:s', strtotime($item['created_at'])),
              'title' => esc_html( $item['title'] ),
              'description' => $item['description'],
            );
            
            // get the best image
            $images = $item['images'];
            $shot_import['image'] = ( isset( $images['hidpi'] ) ? $images['hidpi'] : $images['normal']  );

            //import the shot
            self::import_dribbble_item( $shot_import );

          }

        }

      endfor;
      $timestamp = wp_next_scheduled( 'dpi_import' , array() );

      wp_unschedule_event( $timestamp, 'dpi_import', array() );

      delete_transient( self::DPI_TRANSIENT );
    }

  /**
    * registers Dribbble importer options page with WordPress admin
    *
    * @static
    * @since   1.1.0
    */
  public static function dpi_create_top_level_menu() {

      add_management_page(
        __( 'Dribbble Importer', 'freefolio' ),
        __( 'Dribbble Importer', 'freefolio' ),
        'manage_options',
        'dpi-settings',
        array(__CLASS__, 'dpi_settings_page')
      );

  }


  /**
    * Draws the Dribbble importer options page in the WordPress admin
    *
    * @static
    * @since   1.1.0
    */
  public static function dpi_settings_page() {
    //permissions check
    if( !current_user_can( 'manage_options' ) ){
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    echo '<div class="wrap">';

    // Read in existing option value from database
    $user_name = get_option( self::USERNAME_OPTION );

    //only process data if our nonce is checks out
    if ( !empty( $_POST ) && check_admin_referer( 'dpi_settings_page', 'dpi_nonce' ) ) {

        //stash user name
        $user_name = $_POST[self::USERNAME_OPTION];
        
        $api_url = 'https://api.dribbble.com/v1/users/' . $user_name;
        
        $opts = array(
          'http' => array(
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . self::TOKEN,
          )
        );
        
        $context = stream_context_create($opts);
	
        //get user info from Dribbble API
        $user_info = @file_get_contents($api_url, false, $context);
        
        //if no content from Dribble API it's a bad username
        if( $user_info === false ):

          //display notice to user
          echo '<div class="updated error"><p><strong>' . __('Invalid Dribbble username. Please check your spelling.', 'freefolio' ) . '</strong></p></div>';

          //set user name to false
          update_option( self::USERNAME_OPTION, false );

          //set user info to false
          update_option( self::USERINFO_OPTION, false );

          //update local variables
          $user_name =  $user_info = false;
        else:

          //decode user info
          $user_info = json_decode($user_info, true);

          //update user name
          update_option( self::USERNAME_OPTION, $user_name );

          //update user info
          update_option( self::USERINFO_OPTION, $user_info );

          //if the import button was clicked
          if( isset( $_POST['import'] ) ){
              //set transient
              set_transient( self::DPI_TRANSIENT, true, HOUR_IN_SECONDS );
              //display message
              echo '<div class="updated"><p><strong>' . __('Your Dribbble shots are now being imported in the background. It may take up to 15 minutes for all shots to be imported.', 'freefolio' ) . '</strong></p></div>';
          } else{
            echo '<div class="updated"><p><strong>' . __('Settings Saved', 'freefolio' ) . '</strong></p></div>';
          }
        endif;

    }

    ?>

    <h2><?php echo __( 'Dribbble Importer', 'freefolio' ); ?> </h2>

    <p><?php echo __( 'Imports all shots from your Dribbble portfolio. Once the import is complete, your shots can be edited, deleted, and curated at will.', 'freefolio' ); ?> </p>

    <form name="dribbble-importer" method="post" action="">

      <?php wp_nonce_field('dpi_settings_page','dpi_nonce'); ?>

      <table class="form-table">
        <tbody>
        <tr>
          <th scope="row">
            <label for="<?php echo esc_attr( self::USERNAME_OPTION ); ?>">
              <?php echo __( 'Dribbble Username: ', 'freefolio' ); ?>
            </label>
          </th>
          <td>
              <?php
              printf( '<span>http://dribbble.com/<input name="%1$s" id="%1$s" size="20" type="text" value="%2$s"/></span>',
                esc_attr( self::USERNAME_OPTION ),
                get_option( self::USERNAME_OPTION, '')
              );
              ?>
              <?php
              if( $user_name != false ){
                echo ' <input type="submit" name="import" class="button-secondary" style="margin-left:25px;" value="' . __( 'Import Shots From Dribbble', 'freefolio' ) . '" />';
              } else {
              ?>
                <p class="description"><?php _e( 'Enter the Dribbble username whose shots should be imported.', 'freefolio' ); ?></p>
              <?php } ?>
          </td>
        </tr>
        </tbody>
      </table>

      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
      </p>

    </form>

    </div><!--//wrap-->

    <?php
    }

  }
endif;