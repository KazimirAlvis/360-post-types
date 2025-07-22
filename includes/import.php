<?php
// includes/import.php

// 1) Add “Import CSV” under Clinics menu
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=clinic',
        __( 'Import Data', 'cpt360' ),
        __( 'Import Data', 'cpt360' ),
        'manage_options',
        'cpt360-import',
        'cpt360_render_import_page'
    );
} );

/**
 * 2) Render the upload form
 */
function cpt360_render_import_page() {
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('Permission denied','cpt360'));
    }
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Import Clinics & Doctors','cpt360'); ?></h1>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('cpt360_import_nonce','cpt360_import_nonce'); ?>
        <input type="hidden" name="action" value="cpt360_handle_import">
        <table class="form-table">
          <tr>
            <th><label for="cpt360_csv_file"><?php esc_html_e('CSV File','cpt360');?></label></th>
            <td><input type="file" name="cpt360_csv_file" id="cpt360_csv_file" accept=".csv" required /></td>
          </tr>
        </table>
        <?php submit_button(__('Run Import','cpt360')); ?>
      </form>
    </div>
    <?php
}

/**
 * 3) Handle the upload, parse CSV, insert/update CPT + meta
 */
add_action( 'admin_post_cpt360_handle_import', function() {
    // Permissions + nonce
    if (
        ! current_user_can('manage_options') ||
        empty($_POST['cpt360_import_nonce']) ||
        ! wp_verify_nonce( $_POST['cpt360_import_nonce'], 'cpt360_import_nonce' )
    ) {
        wp_die(__('Invalid request','cpt360'), '', ['response'=>403]);
    }

    // Check upload
    if ( empty($_FILES['cpt360_csv_file']['tmp_name']) ) {
        wp_die(__('No file uploaded','cpt360'), '', ['response'=>400]);
    }

    $fh = fopen( $_FILES['cpt360_csv_file']['tmp_name'], 'r' );
    if ( ! $fh ) {
        wp_die(__('Could not open uploaded file','cpt360'), '', ['response'=>500]);
    }

    // Read header row
    $headers = fgetcsv($fh);
    if ( ! is_array($headers) ) {
        wp_die(__('Invalid CSV file','cpt360'), '', ['response'=>400]);
    }

    // Loop rows
    while ( $row = fgetcsv($fh) ) {
        $data = array_combine( $headers, $row );

        // Determine CPT
        $type = $data['Type'] ?? '';
        if ( ! in_array($type, ['Clinic','Doctor'], true) ) {
            continue;
        }
        $post_type = strtolower($type);

        // Find existing post by Slug or create new
        $slug = sanitize_title( $data['Slug'] ?? '' );
        $existing = get_page_by_path( $slug, OBJECT, $post_type );

        $post_args = [
            'post_title'  => sanitize_text_field( $data['Title'] ?? '' ),
            'post_name'   => $slug,
            'post_type'   => $post_type,
            'post_status' => 'publish',
        ];
        if ( $existing ) {
            $post_args['ID'] = $existing->ID;
        }

        $post_id = wp_insert_post( $post_args, true );
        if ( is_wp_error($post_id) ) {
            error_log('Import failed for '. $slug .': '. $post_id->get_error_message());
            continue;
        }

        // Update every other column as meta
        foreach ( $data as $key => $val ) {
            if ( in_array($key, ['Type','ID','Title','Slug'], true) ) {
                continue;
            }
            // flatten arrays if needed
            $clean = maybe_unserialize($val);
            update_post_meta( $post_id, $key, $clean );
        }
    }

    fclose($fh);

    // Redirect back with a success message
    wp_redirect( add_query_arg( 'imported', 1, admin_url('edit.php?post_type=clinic&page=cpt360-import') ) );
    exit;
} );
