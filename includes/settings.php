<?php

/**
 * Global Settings: colors, fonts & global Assessment ID
 */

if (! class_exists('_360_Global_Settings')) {

    class _360_Global_Settings
    {
        const OPTION_KEY = '360_global_settings';

        public function __construct()
        {
            add_action('admin_menu',                [$this, 'add_admin_page']);
            add_action('admin_init',                [$this, 'register_settings']);
            add_action('admin_enqueue_scripts',     [$this, 'enqueue_color_picker']);
            add_action('wp_head',                   [$this, 'print_global_css_variables']);
        }

        /**
         * Add top-level menu under “360 Settings”
         */
        public function add_admin_page()
        {
            add_menu_page(
                __('360 Settings', 'cpt360'),    // page title
                __('360 Settings', 'cpt360'),    // menu title
                'manage_options',                  // capability
                '360-settings',                    // menu slug
                [$this, 'render_settings_page'], // callback
                'dashicons-admin-generic',         // icon
                60                                 // position
            );
        }

        /**
         * Register our option group, section, and fields.
         */
        public function register_settings()
        {
            register_setting(
                '360_settings_group',            // option_group
                self::OPTION_KEY,                // option_name (array)
                [
                    'type'              => 'array',
                    'sanitize_callback' => [$this, 'sanitize'],
                    'default'           => [],
                ]
            );

            add_settings_section(
                '360_main_section',
                __('Global Colors & Fonts', 'cpt360'),
                '__return_false',
                '360-settings'
            );

            // Primary Color
            add_settings_field(
                'primary_color',
                __('Primary Color', 'cpt360'),
                [$this, 'field_color_picker'],
                '360-settings',
                '360_main_section',
                ['label_for' => 'primary_color']
            );

            // Secondary Color
            add_settings_field(
                'secondary_color',
                __('Secondary Color', 'cpt360'),
                [$this, 'field_color_picker'],
                '360-settings',
                '360_main_section',
                ['label_for' => 'secondary_color']
            );

            // Body Font
            add_settings_field(
                'body_font',
                __('Body Font', 'cpt360'),
                [$this, 'field_font_select'],
                '360-settings',
                '360_main_section',
                ['label_for' => 'body_font']
            );

            // Heading Font
            add_settings_field(
                'heading_font',
                __('Heading Font', 'cpt360'),
                [$this, 'field_font_select'],
                '360-settings',
                '360_main_section',
                ['label_for' => 'heading_font']
            );

            // ** Global Assessment ID **
            add_settings_field(
                'assessment_id',
                __('Global Assessment ID', 'cpt360'),
                [$this, 'field_assessment_id'],
                '360-settings',
                '360_main_section',
                ['label_for' => 'assessment_id']
            );
        }

        /**
         * Enqueue WP color-picker on our settings page.
         */
        public function enqueue_color_picker($hook)
        {
            if ($hook !== 'toplevel_page_360-settings') {
                return;
            }
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }

        /**
         * Render a color-picker input.
         */
        public function field_color_picker($args)
        {
            $opts = get_option(self::OPTION_KEY, []);
            $val = $opts[$args['label_for']] ?? '';
            printf(
                '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="color-field" data-default-color="%3$s" />',
                esc_attr($args['label_for']),
                esc_attr(self::OPTION_KEY),
                esc_attr($val)
            );
            echo '<script>jQuery(function($){$(".color-field").wpColorPicker();});</script>';
        }

        /**
         * Render a font-select dropdown.
         */
        public function field_font_select($args)
        {
            $fonts = [
                'system-font'  => 'System Font',
                'arvo'         => 'Arvo',
                'bodoni-moda'  => 'Bodoni Moda',
                'cabin'        => 'Cabin',
                'chivo'        => 'Chivo',
                // …add your others
            ];
            $opts = get_option(self::OPTION_KEY, []);
            $sel = $opts[$args['label_for']] ?? '';
            echo '<select id="' . esc_attr($args['label_for']) . '" name="' . esc_attr(self::OPTION_KEY . '[' . $args['label_for'] . ']') . '">';
            foreach ($fonts as $slug => $label) {
                printf(
                    '<option value="%1$s"%2$s>%3$s</option>',
                    esc_attr($slug),
                    selected($sel, $slug, false),
                    esc_html($label)
                );
            }
            echo '</select>';
        }

        /**
         * Render the Global Assessment ID input.
         */
        public function field_assessment_id($args)
        {
            $opts = get_option(self::OPTION_KEY, []);
            $val  = $opts[$args['label_for']] ?? '';
            printf(
                '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
                esc_attr($args['label_for']),
                esc_attr(self::OPTION_KEY),
                esc_attr($val)
            );
            echo '<p class="description">'
                . esc_html__('Used when a clinic has no custom Assessment ID.', 'cpt360')
                . '</p>';
        }

        /**
         * Sanitize all settings inputs.
         *
         * @param array $input
         * @return array
         */
        public function sanitize($input)
        {
            $output = [];

            // Colors
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $input['primary_color'] ?? '')) {
                $output['primary_color'] = $input['primary_color'];
            }
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $input['secondary_color'] ?? '')) {
                $output['secondary_color'] = $input['secondary_color'];
            }

            // Fonts
            if (isset($input['body_font'])) {
                $output['body_font'] = sanitize_text_field($input['body_font']);
            }
            if (isset($input['heading_font'])) {
                $output['heading_font'] = sanitize_text_field($input['heading_font']);
            }

            // Global Assessment ID
            if (isset($input['assessment_id'])) {
                $output['assessment_id'] = sanitize_text_field($input['assessment_id']);
            }

            return $output;
        }

        /**
         * Render the settings page HTML.
         */
        public function render_settings_page()
        {
            if (! current_user_can('manage_options')) {
                wp_die(__('Permission denied', 'cpt360'));
            }
?>
            <div class="wrap">
                <h1><?php esc_html_e('360 Global Settings', 'cpt360'); ?></h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('360_settings_group');
                    do_settings_sections('360-settings');
                    submit_button();
                    ?>
                </form>
            </div>
<?php
        }

        /**
         * Print CSS variables for colors/fonts (in <head>).
         */
        public function print_global_css_variables()
        {
            $opts = get_option(self::OPTION_KEY, []);
            echo '<style id="360-global-vars">:root {';
            if (! empty($opts['primary_color'])) {
                echo '--cpt360-primary: ' . esc_html($opts['primary_color']) . ';';
            }
            if (! empty($opts['secondary_color'])) {
                echo '--cpt360--preset--color--secondary: ' . esc_html($opts['secondary_color']) . ';';
            }
            if (! empty($opts['body_font'])) {
                echo '--wp--preset--font-family--body-font: var(--wp--preset--font-family--'
                    . esc_js($opts['body_font']) . ');';
            }
            if (! empty($opts['heading_font'])) {
                echo '--wp--preset--font-family--heading-font: var(--wp--preset--font-family--'
                    . esc_js($opts['heading_font']) . ');';
            }
            echo '}</style>';
        }
    }

    // Instantiate
    new _360_Global_Settings();
}
