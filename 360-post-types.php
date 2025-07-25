<?php

/**
 * Plugin Name: 360 Post Types & Settings
 * Plugin URI:   https://github.com/KazimirAlvis/360-post-types
 * Description: Registers Clinics & Doctors CPTs, adds a State dropdown on Clinics, doctor->clinic relationships, and a global settings admin page for colors & fonts.
 * Version:     1.0.21
 * Author:      Kaz Alvis
 * Text Domain:  360-post-types
 * GitHub Plugin URI: your-org/360-post-types
 * GitHub Branch:     main
 */

 


// ─────────────────────────────────────────────────────────────────
// 1) Include the Update Checker library (make sure that folder and file exist)
// ─────────────────────────────────────────────────────────────────
// 1) include the v5 autoloader
require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

// 2) import the factory
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// 3) build the checker
$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/KazimirAlvis/360-post-types/',
    __FILE__,
    '360-post-types'
);

// 4) override branch if you must (only works on the checker itself)
$updateChecker->setBranch('main');



if (! defined('ABSPATH')) exit;
require_once __DIR__ . '/includes/register-cpt.php';
require_once __DIR__ . '/includes/clinic-doctors.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/doctor-meta.php';
require_once __DIR__ . '/includes/clinic-meta.php';
require_once __DIR__ . '/includes/yoast-integration.php';
require_once __DIR__ . '/includes/export.php';


if (! defined('CPT360_PLUGIN_URL')) {
  define('CPT360_PLUGIN_URL', plugin_dir_url(__FILE__));
}




/*--------------------------------------------------------------
 Disable Gutenberg for clinic & doctor CPTs
--------------------------------------------------------------*/
define('CPT_CLINIC',  'clinic');
define('CPT_DOCTOR',  'doctor');

add_filter('use_block_editor_for_post_type', function ($use, $post_type) {
  if (in_array($post_type, [CPT_CLINIC, CPT_DOCTOR], true)) {
    return false;
  }
  return $use;
}, 10, 2);




/**
 * Remove the main content editor for the 'clinic' CPT
 */
add_action('init', function () {
  // Make sure this runs *after* your CPT is registered
  remove_post_type_support('clinic', 'editor');
  remove_post_type_support('doctor', 'editor');
}, 20);



/**
 * Enqueue our admin CSS for the Clinic CPT metabox
 */
function my_clinic_plugin_enqueue_admin_styles($hook)
{
  // Optional: only load on edit-screen for your CPT
  $screen = get_current_screen();
  if (! $screen || $screen->post_type !== 'clinic') {
    return;
  }

  // Register & enqueue
  wp_enqueue_style(
    'clinic-meta-box-css',                             // handle
    plugin_dir_url(__FILE__) . 'css/admin-meta.css', // URL to your CSS
    [],                                                // deps
    '1.0.0'                                            // version
  );
}
add_action('admin_enqueue_scripts', 'my_clinic_plugin_enqueue_admin_styles');



/**
 * Public styling
 */
function my_clinic_plugin_enqueue_styles()
{
  wp_enqueue_style(
    'clinic-public-css',
    plugin_dir_url(__FILE__) . 'css/public.css',
    [],
    '1.0.0'
  );
}
add_action('wp_enqueue_scripts', 'my_clinic_plugin_enqueue_styles');


/**
 * Load our plugin’s single-clinic template in place of the theme’s.
 */
function mp_load_clinic_single_template($single_template)
{
  if (is_singular('clinic')) {
    $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-clinic.php';
    if (file_exists($plugin_template)) {
      return $plugin_template;
    }
  }
  return $single_template;
}
add_filter('single_template', 'mp_load_clinic_single_template');



/*--------------------------------------------------------------
 adds clinic class to the article tag
--------------------------------------------------------------*/
add_filter('post_class', function ($classes, $class, $post_id) {
  // only on your CPT (or wherever you need it)
  if (get_post_type($post_id) === 'clinic') {
    $classes[] = sanitize_html_class(get_the_title($post_id), 'untitled');
  }

  return $classes;
}, 10, 3);





/*--------------------------------------------------------------
 google fonts
--------------------------------------------------------------*/
add_action('wp_enqueue_scripts', 'myclinic_enqueue_google_fonts');
function myclinic_enqueue_google_fonts()
{
  $font_url = 'https://fonts.googleapis.com/css2'
    . '?family=Roboto:ital,wght@0,400;0,700'
    . '&family=Marcellus'
    . '&family=Inter:ital,wght@0,400;0,700'
    . '&display=swap';

  wp_enqueue_style('myclinic-google-fonts', esc_url($font_url), [], null);
}


/*--------------------------------------------------------------
 Font Awesome
--------------------------------------------------------------*/
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style(
    'my-plugin-fa',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
    [],
    '6.5.1'
  );
});



/*--------------------------------------------------------------
 Slick CDN
--------------------------------------------------------------*/

add_action('wp_enqueue_scripts', function () {
  if (! is_singular('clinic')) {
    return;
  }

  // 1) Slick CSS from CDN
  wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');
  wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', ['slick-css'], '1.8.1');

  // 2) Slick JS from CDN — depend on WP’s built-in jQuery
  wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);

  // 3) Our init script
  wp_add_inline_script('slick-js', "
    jQuery(function($){
      $('.clinic-reviews-slider').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        dots: true,
        infinite:       true,      // loop back to start
        autoplay:       true,      // turn on auto‐sliding
        autoplaySpeed:  3000,

        responsive: [
          { breakpoint: 768, settings: { slidesToShow: 1 } }
        ]
      });
    });
  ");
});


/*--------------------------------------------------------------
 Astra Reset css
--------------------------------------------------------------*/
add_action( 'wp_enqueue_scripts', 'cpt360_enqueue_astra_reset', 100 );
function cpt360_enqueue_astra_reset() {
  // (1) make sure Astra’s style has already been enqueued
  //     Astra uses the handle 'astra-theme-css'
  // (2) enqueue your reset CSS with that as a dependency
  wp_enqueue_style(
    'cpt360-astra-reset',
    plugin_dir_url( __FILE__ ) . 'css/astra-reset.css',
    [ 'astra-theme-css' ], 
    filemtime( plugin_dir_path( __FILE__ ) . 'css/astra-reset.css' )
  );
}

/*--------------------------------------------------------------
 JS file
--------------------------------------------------------------*/

add_action( 'admin_enqueue_scripts', function( $hook ) {
    $screen = get_current_screen();
    if ( $screen && in_array( $screen->post_type, [ 'clinic', 'doctor' ], true ) ) {
        wp_enqueue_media();
        wp_enqueue_script(
            'cpt360-media-meta-boxes',
            plugin_dir_url( __FILE__ ) . 'includes/media-meta-boxes.js',
            [ 'jquery' ],
            '1.0',
            true
        );
    }
});


	/*--------------------------------------------------------------
 	Map headings same height
	--------------------------------------------------------------*/

add_action( 'wp_footer', function(){
  if ( ! is_singular( 'clinic' ) ) {
    return;
  }
  ?>
  <script>
  // wait for *all* assets + HTML to be loaded
  window.addEventListener('load', function(){
    const headings = document.querySelectorAll('.map_heading');
    if ( ! headings.length ) return;

    // measure
    let maxH = 0;
    headings.forEach(el => {
      const h = el.offsetHeight;           // offsetHeight is simpler here
      if ( h > maxH ) maxH = h;
    });

    // apply
    headings.forEach(el => {
      el.style.height = maxH + 'px';
    });
  });
  </script>
  <?php
});



	/*--------------------------------------------------------------
    fibroidclinic /clinic/ redirects
	--------------------------------------------------------------*/

add_action( 'template_redirect', function() {
    if ( is_admin() ) {
        return;
    }

    $host = strtolower( $_SERVER['HTTP_HOST'] ?? '' );
    if ( ! in_array( $host, [
        'myfibroidcldev.wpenginepowered.com',
        'myfibroidclstg.wpenginepowered.com',
        'myfibroidclinic.com',
    ], true ) ) {
        return;
    }

    $slug = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    if ( '' === $slug ) {
        return;
    }

    // if there's a clinic CPT with that slug, redirect
    if ( $clinic = get_page_by_path( $slug, OBJECT, 'clinic' ) ) {
        wp_redirect( get_permalink( $clinic->ID ), 301 );
        exit;
    }
} );

