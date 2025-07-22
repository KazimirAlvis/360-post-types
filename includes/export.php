<?php
// includes/export.php

// 1) Hook the submenu under Clinics
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=clinic',      // parent: Clinics menu
        __( 'Export Data', 'cpt360' ),    // page title
        __( 'Export Data', 'cpt360' ),    // menu title
        'manage_options',                 // capability
        'cpt360-export',                  // menu slug
        'cpt360_render_export_page'       // callback
    );
} );

/**
 * 2) Render the Export UI form.
 */
function cpt360_render_export_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission denied', 'cpt360' ) );
    }

    $action_url = admin_url( 'admin-post.php?action=cpt360_export_data' );
    ?>
    <div class="wrap">
      <h1><?php esc_html_e( 'Export Clinics & Doctors', 'cpt360' ); ?></h1>
      <form method="post" action="<?php echo esc_url( $action_url ); ?>">
        <?php wp_nonce_field( 'cpt360_export_nonce', 'cpt360_export_nonce' ); ?>

        <table class="form-table">
          <tr>
            <th><label for="cpt360_clinics"><?php _e( 'Clinics', 'cpt360' ); ?></label></th>
            <td>
              <select name="clinic_ids[]" id="cpt360_clinics" multiple size="8" style="width:100%;">
                <?php
                $clinics = get_posts([
                  'post_type'      => 'clinic',
                  'posts_per_page' => -1,
                  'orderby'        => 'title',
                  'order'          => 'ASC',
                ]);
                foreach ( $clinics as $c ) {
                    printf(
                        '<option value="%1$d">%2$s</option>',
                        $c->ID,
                        esc_html( $c->post_title )
                    );
                }
                ?>
              </select>
              <p class="description"><?php _e( 'Hold ⌘ (Mac) or Ctrl (Win) to select multiple.', 'cpt360' ); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="cpt360_doctors"><?php _e( 'Doctors', 'cpt360' ); ?></label></th>
            <td>
              <select name="doctor_ids[]" id="cpt360_doctors" multiple size="8" style="width:100%;">
                <?php
                $docs = get_posts([
                  'post_type'      => 'doctor',
                  'posts_per_page' => -1,
                  'orderby'        => 'title',
                  'order'          => 'ASC',
                ]);
                foreach ( $docs as $d ) {
                    printf(
                        '<option value="%1$d">%2$s</option>',
                        $d->ID,
                        esc_html( $d->post_title )
                    );
                }
                ?>
              </select>
            </td>
          </tr>
        </table>

        <?php submit_button( __( 'Download CSV', 'cpt360' ) ); ?>
      </form>
    </div>
    <?php
}

/**
 * 3) Handle the export, generate CSV and send it.
 */
add_action( 'admin_post_cpt360_export_data', function() {
    if (
        ! current_user_can( 'manage_options' )
        || empty( $_POST['cpt360_export_nonce'] )
        || ! wp_verify_nonce( $_POST['cpt360_export_nonce'], 'cpt360_export_nonce' )
    ) {
        wp_die( __( 'Invalid request', 'cpt360' ), '', [ 'response' => 403 ] );
    }

    // collect IDs
    $clinic_ids = array_map( 'absint', (array) ( $_POST['clinic_ids'] ?? [] ) );
    $doctor_ids = array_map( 'absint', (array) ( $_POST['doctor_ids'] ?? [] ) );

    // Gather all meta keys for both types
    $all_meta_keys = [];
    foreach ( $clinic_ids as $cid ) {
        $all_meta_keys = array_merge( $all_meta_keys, array_keys( get_post_meta( $cid ) ) );
    }
    foreach ( $doctor_ids as $did ) {
        $all_meta_keys = array_merge( $all_meta_keys, array_keys( get_post_meta( $did ) ) );
    }
    $all_meta_keys = array_unique( $all_meta_keys );
    sort( $all_meta_keys );

    // Standard columns + extras for Doctor
    $columns = array_merge(
        [ 'Type', 'ID', 'Title', 'Slug', 'Clinics', 'Doctor Name', 'Doctor Title' ],
        $all_meta_keys
    );

    // Send CSV headers
    $filename = 'cpt360-export-' . date( 'Y-m-d' ) . '.csv';
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=' . $filename );

    $fp = fopen( 'php://output', 'w' );
    fputcsv( $fp, $columns );

    //
    // Export Clinics
    //
    foreach ( $clinic_ids as $cid ) {
        $post = get_post( $cid );
        if ( ! $post || $post->post_type !== 'clinic' ) {
            continue;
        }

        // Base row
        $row = [
            'Type'         => 'Clinic',
            'ID'           => $cid,
            'Title'        => $post->post_title,
            'Slug'         => $post->post_name,
            'Clinics'      => '',
            'Doctor Name'  => '',
            'Doctor Title' => '',
        ];

        // Add all meta keys
        $meta = get_post_meta( $cid );
        foreach ( $all_meta_keys as $key ) {
            $val = $meta[ $key ] ?? [''];
            $row[ $key ] = is_array( $val )
                ? implode( '|', array_map( 'maybe_serialize', $val ) )
                : maybe_serialize( $val );
        }

        fputcsv( $fp, $row );
    }

    //
    // Export Doctors
    //
    foreach ( $doctor_ids as $did ) {
        $post = get_post( $did );
        if ( ! $post || $post->post_type !== 'doctor' ) {
            continue;
        }

        // Map clinic_id → names
        $cl_ids = get_post_meta( $did, 'clinic_id', false );
        $cl_names = [];
        foreach ( $cl_ids as $cid ) {
            $cl_names[] = get_the_title( $cid );
        }

        // Base row
        $row = [
            'Type'         => 'Doctor',
            'ID'           => $did,
            'Title'        => $post->post_title,
            'Slug'         => $post->post_name,
            'Clinics'      => implode( '|', $cl_names ),
            'Doctor Name'  => get_post_meta( $did, 'doctor_name', true ),
            'Doctor Title' => get_post_meta( $did, 'doctor_title', true ),
        ];

        // Add all other meta keys
        $meta = get_post_meta( $did );
        foreach ( $all_meta_keys as $key ) {
            // skip keys we’ve already handled
            if ( in_array( $key, ['clinic_id','doctor_name','doctor_title'], true ) ) {
                continue;
            }
            $val = $meta[ $key ] ?? [''];
            $row[ $key ] = is_array( $val )
                ? implode( '|', array_map( 'maybe_serialize', $val ) )
                : maybe_serialize( $val );
        }

        fputcsv( $fp, $row );
    }

    fclose( $fp );
    exit;
});
