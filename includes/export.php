<?php
// includes/export.php

// 1) Add submenu to Clinics admin
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=clinic',
        __('Export WXR', 'cpt360'),
        __('Export WXR', 'cpt360'),
        'manage_options',
        'cpt360-export-wxr',
        'cpt360_render_export_wxr_page'
    );
});

// 2) Render the UI
function cpt360_render_export_wxr_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied.'));
    }

    $action_url = admin_url('admin-post.php?action=cpt360_export_wxr');
    ?>
    <div class="wrap">
        <h1><?php _e('Export Clinics & Doctors (WXR)', 'cpt360'); ?></h1>
        <form method="post" action="<?php echo esc_url($action_url); ?>">
            <?php wp_nonce_field('cpt360_export_wxr_nonce', 'cpt360_export_wxr_nonce'); ?>
            <p><?php _e('This will export selected Clinics and Doctors as a WordPress XML (WXR) file, including meta fields and relationships.', 'cpt360'); ?></p>
            <?php submit_button(__('Download WXR File', 'cpt360')); ?>
        </form>
    </div>
    <?php
}

// 3) Handle the export
add_action('admin_post_cpt360_export_wxr', function () {
    if (
        !current_user_can('manage_options') ||
        empty($_POST['cpt360_export_wxr_nonce']) ||
        !wp_verify_nonce($_POST['cpt360_export_wxr_nonce'], 'cpt360_export_wxr_nonce')
    ) {
        wp_die(__('Invalid request'), '', ['response' => 403]);
    }

    // Get all clinics and doctors
    $posts = get_posts([
        'post_type'      => ['clinic', 'doctor'],
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft', 'pending'],
    ]);

    if (empty($posts)) {
        wp_die(__('No posts found.'));
    }

    header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    header('Content-Disposition: attachment; filename=cpt360-export-' . date('Y-m-d') . '.wxr');

    echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . "\" ?>\n";
    ?>
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/"
>
<channel>
    <title><?php bloginfo_rss('name'); ?></title>
    <link><?php bloginfo_rss('url'); ?></link>
    <description><?php bloginfo_rss('description'); ?></description>
    <pubDate><?php echo date('r'); ?></pubDate>
    <language><?php bloginfo_rss('language'); ?></language>
    <wp:wxr_version>1.2</wp:wxr_version>
    <wp:base_site_url><?php bloginfo_rss('url'); ?></wp:base_site_url>
    <wp:base_blog_url><?php bloginfo_rss('url'); ?></wp:base_blog_url>

    <?php foreach ($posts as $post): setup_postdata($post); ?>
    <item>
        <title><?php echo esc_html($post->post_title); ?></title>
        <link><?php echo get_permalink($post); ?></link>
        <pubDate><?php echo $post->post_date; ?></pubDate>
        <dc:creator><?php echo get_the_author_meta('user_login', $post->post_author); ?></dc:creator>
        <guid isPermaLink="false"><?php echo esc_url($post->guid); ?></guid>
        <description></description>
        <content:encoded><![CDATA[<?php echo $post->post_content; ?>]]></content:encoded>
        <excerpt:encoded><![CDATA[<?php echo $post->post_excerpt; ?>]]></excerpt:encoded>
        <wp:post_id><?php echo $post->ID; ?></wp:post_id>
        <wp:post_date><?php echo $post->post_date; ?></wp:post_date>
        <wp:post_date_gmt><?php echo $post->post_date_gmt; ?></wp:post_date_gmt>
        <wp:comment_status><?php echo $post->comment_status; ?></wp:comment_status>
        <wp:ping_status><?php echo $post->ping_status; ?></wp:ping_status>
        <wp:post_name><?php echo $post->post_name; ?></wp:post_name>
        <wp:status><?php echo $post->post_status; ?></wp:status>
        <wp:post_parent><?php echo $post->post_parent; ?></wp:post_parent>
        <wp:menu_order><?php echo $post->menu_order; ?></wp:menu_order>
        <wp:post_type><?php echo $post->post_type; ?></wp:post_type>
        <wp:post_password><?php echo $post->post_password; ?></wp:post_password>
        <wp:is_sticky>0</wp:is_sticky>

        <?php
        $postmeta = get_post_meta($post->ID);
        foreach ($postmeta as $meta_key => $meta_values) {
            foreach ($meta_values as $meta_value) {
                echo "<wp:postmeta>\n";
                echo "<wp:meta_key>" . esc_html($meta_key) . "</wp:meta_key>\n";
                echo "<wp:meta_value><![CDATA[" . $meta_value . "]]></wp:meta_value>\n";
                echo "</wp:postmeta>\n";
            }
        }
        ?>

    </item>
    <?php endforeach; wp_reset_postdata(); ?>

</channel>
</rss>
<?php
    exit;
});
