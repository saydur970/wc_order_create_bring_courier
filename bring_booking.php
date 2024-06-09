<?php


/**
 * Plugin Name: Bring Booking API
 * Description: Create Bring Courier Booking
 * Version: 1.0.0
*/

//  prefix: BRNGBOKGPTDY_

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('BRNGBOKGPTDY_Index')) :

  class BRNGBOKGPTDY_Index
  {

    function __construct()
    {

      $this->define_constants();
      $this->import();
      $this->setup_func();

      add_filter('template_include', array($this, 'loadTemplate'));

      // add_action('admin_menu', array($this, 'adminPage'));
      add_action('admin_enqueue_scripts', array($this, 'admin_loadScripts'));
      add_action('wp_enqueue_scripts', array($this, 'user_loadScripts'));

      add_action('rest_api_init', array($this, 'define_rest_api'));

    }


    // ================== define constants ==================
    private function define_constants()
    {
      define('BRNGBOKGPTDY_ROOTPATH', plugin_dir_path(__FILE__));
      define('BRNGBOKGPTDY_ROOTURL', plugin_dir_url(__FILE__));
    }

    // ================== import ==================
    private function import()
    {

      // require_once(FLRAMASCAP_ROOTPATH . "includes/crud_file.php");
      // require_once(FLRAMASCAP_ROOTPATH . "includes/api_fetch.php");
      // require_once(FLRAMASCAP_ROOTPATH . "includes/woo_fetch.php");
      // require_once(FLRAMASCAP_ROOTPATH . "includes/upload_process.php");
      // require_once(FLRAMASCAP_ROOTPATH . "includes/sync_process.php");
      // require_once(FLRAMASCAP_ROOTPATH . "includes/utils_cls.php");
      // require_once(FLRAMASCAP_ROOTPATH . "includes/order_process.php");

      require_once(BRNGBOKGPTDY_ROOTPATH . "includes/api.php");
      require_once(BRNGBOKGPTDY_ROOTPATH . "includes/orders.php");
      require_once(BRNGBOKGPTDY_ROOTPATH . "includes/utils.php");

      new BRNGBOKGPTDY_Order();

    }


    // ================== handle script files ==================
    public function admin_loadScripts($hook) {

      // -------- add script for admin dashboard
      if (
        $hook === "woocommerce_page_wc-orders" || 
        $hook === "post.php"
      ) {


        // wp_enqueue_script(
        //   'pikadaylibrary-js',
        //   'https://cdn.jsdelivr.net/npm/pikaday/pikaday.js',
        //   [],
        //   '1.0',
        //   true // Load in footer
        // );

        // wp_enqueue_style(
        //   'pikadaylibrary-css', 
        //   'https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css'
        // );

        // npm - pick a day
        wp_enqueue_script(
          'admin-orderui-npm-pickaday-script',
          BRNGBOKGPTDY_ROOTURL . "assets/pickaday_npm.js",
          array(),
          '1.0',
          true
        );


        // npm - pick a day
        wp_enqueue_style(
          'admin-orderui-npm-pickaday-css',
          BRNGBOKGPTDY_ROOTURL . "assets/pickaday_npm.css",
        );
        


        // order ui script
        wp_enqueue_script(
          'admin-orderui-booking-create-script',
          BRNGBOKGPTDY_ROOTURL . "assets/order_admin.js",
          array(),
          '1.0',
          true
        );

        wp_localize_script(
          'admin-orderui-booking-create-script', 
          'php_var_list', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'site_url' => site_url()
          )
        );

        // order ui css
        wp_enqueue_style(
          'admin-orderui-booking-create-style',
          BRNGBOKGPTDY_ROOTURL . "assets/order_admin.css",
        );

      }

    }


    // ================== handle script files ==================
    public function user_loadScripts() {

      if (is_page('my-account')) {

        // order ui css
        wp_enqueue_style(
          'user-dashboard-bring-booking-style',
          BRNGBOKGPTDY_ROOTURL . "assets/order_user.css",
        );

      }



    }


    // ================== some function ==================
    private function setup_func() {

      $wordpressTimezone = get_option('timezone_string');

      if ($wordpressTimezone) {
        date_default_timezone_set($wordpressTimezone);
      }
    }

    // ================== handle templates ==================
    public function loadTemplate($template) {

      if (is_page('bring_test')) {
        return  plugin_dir_path(__FILE__) . 'templates/test.php';
      }

      return $template;
    }

    // ================== define rest api ==================
    public function define_rest_api() {

      require_once(BRNGBOKGPTDY_ROOTPATH.'rest_api/create_booking.php');

      // create booking
      register_rest_route('bringBooking', 'create', array(
        'methods' => 'POST',
        'callback' => 'BRNGBOKGPTDY_creating_booking',
        'permission_callback' => array('BRNGBOKGPTDY_Utils', 'restrict_to_admin')
      ));

    }


  }


  if (class_exists('BRNGBOKGPTDY_Index')) {

    $BRNGBOKGPTDY_index = new BRNGBOKGPTDY_Index();
  }

endif;
