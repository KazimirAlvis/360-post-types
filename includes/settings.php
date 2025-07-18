<?php /**
 * Global Settings: colors & fonts
 */
class _360_Global_Settings {
    const OPTION_KEY = '360_global_settings';
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_color_picker' ] );
        add_action( 'wp_head', [ $this, 'print_global_css_variables' ] );
    }
    public function add_admin_page() {
        add_menu_page(
            '360 Settings', '360 Settings', 'manage_options', '360-settings', [ $this, 'render_settings_page' ], 'dashicons-admin-generic', 60
        );
    }
    public function register_settings() {
        register_setting( '360_settings_group', self::OPTION_KEY, [ 'sanitize_callback' => [ $this, 'sanitize' ] ] );
        add_settings_section( '360_main_section', 'Global Colors & Fonts', '__return_false', '360-settings' );
        add_settings_field( 'primary_color',   'Primary Color',   [ $this, 'field_color_picker' ], '360-settings', '360_main_section', [ 'label_for'=>'primary_color' ] );
        add_settings_field( 'secondary_color', 'Secondary Color', [ $this, 'field_color_picker' ], '360-settings', '360_main_section', [ 'label_for'=>'secondary_color' ] );
        add_settings_field( 'body_font',       'Body Font',       [ $this, 'field_font_select' ],  '360-settings', '360_main_section', [ 'label_for'=>'body_font' ] );
        add_settings_field( 'heading_font',    'Heading Font',    [ $this, 'field_font_select' ],  '360-settings', '360_main_section', [ 'label_for'=>'heading_font' ] );
    }
    public function enqueue_color_picker( $hook ) {
        if ( $hook !== 'toplevel_page_360-settings' ) return;
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }
    public function field_color_picker( $args ) {
        $opts = get_option( self::OPTION_KEY, [] );
        $val  = $opts[ $args['label_for'] ] ?? '';
        printf('<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="color-field" data-default-color="%3$s" />', esc_attr($args['label_for']), esc_attr(self::OPTION_KEY), esc_attr($val));
        echo '<script>jQuery(function($){$(".color-field").wpColorPicker();});</script>';
    }
    public function field_font_select( $args ) {
        $fonts = [
            'system-font'=>'System Font','arvo'=>'Arvo','bodoni-moda'=>'Bodoni Moda','cabin'=>'Cabin','chivo'=>'Chivo'
            // ...add your other theme.json slugs
        ];
        $opts = get_option( self::OPTION_KEY, [] );
        $sel  = $opts[ $args['label_for'] ] ?? '';
        echo '<select id="'.esc_attr($args['label_for']).'" name="'.esc_attr(self::OPTION_KEY.'['.$args['label_for'].']').'">';
        foreach( $fonts as $slug=>$label ) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($slug), selected($sel,$slug,false), esc_html($label));
        }
        echo '</select>';
    }
    public function sanitize( $input ) {
        $output = [];
        if ( preg_match('/^#[0-9A-Fa-f]{6}$/',$input['primary_color'] ?? '') )   $output['primary_color']   = $input['primary_color'];
        if ( preg_match('/^#[0-9A-Fa-f]{6}$/',$input['secondary_color'] ?? '') ) $output['secondary_color'] = $input['secondary_color'];
        if ( isset($input['body_font']) )    $output['body_font']    = sanitize_text_field($input['body_font']);
        if ( isset($input['heading_font']) ) $output['heading_font'] = sanitize_text_field($input['heading_font']);
        return $output;
    }
    public function render_settings_page() {
        echo '<div class="wrap"><h1>360 Global Settings</h1><form method="post" action="options.php">';
        settings_fields('360_settings_group'); do_settings_sections('360-settings'); submit_button();
        echo '</form></div>';
    }
    public function print_global_css_variables() {
        $opts = get_option( self::OPTION_KEY, [] );
        echo '<style id="360-global-vars">:root {';
        if ( ! empty($opts['primary_color']) )   echo '--wp--preset--color--primary: '.esc_html($opts['primary_color']).';';
        if ( ! empty($opts['secondary_color']) ) echo '--wp--preset--color--secondary: '.esc_html($opts['secondary_color']).';';
        if ( ! empty($opts['body_font']) )       echo '--wp--preset--font-family--body-font: var(--wp--preset--font-family--'.esc_js($opts['body_font']).');';
        if ( ! empty($opts['heading_font']) )    echo '--wp--preset--font-family--heading-font: var(--wp--preset--font-family--'.esc_js($opts['heading_font']).');';
        echo '}</style>';
    }
}
new _360_Global_Settings();


add_action( 'wp_enqueue_scripts', 'cpt360_enqueue_public_css', 20 );
function cpt360_enqueue_public_css() {
    wp_enqueue_style(
        'cpt360-public',
        CPT360_PLUGIN_URL . 'css/public.css', // <-- guaranteed to hit /wp-content/plugins/your-plugin/css/public.css
        [],
        '1.0'
    );



  $opts      = get_option( '360_global_settings', [] );
  $primary   = sanitize_hex_color( $opts['primary_color'] ?? '' );
  $secondary = sanitize_hex_color( $opts['secondary_color'] ?? '' );

  // only emit vars — no example selectors
  $custom_css = ":root{";
  if ( $primary )   { $custom_css .= "--cpt360-primary:{$primary};"; }
  if ( $secondary ) { $custom_css .= "--cpt360-secondary:{$secondary};"; }
  $custom_css .= "}";

  wp_add_inline_style( 'cpt360-public', $custom_css );
}

