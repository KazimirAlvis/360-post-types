<?php
/**
 * Register Clinics CPT
 */
add_action( 'init', function() {
    $labels = [
        'name'               => 'Clinics',
        'singular_name'      => 'Clinic',
        'add_new_item'       => 'Add New Clinic',
        'edit_item'          => 'Edit Clinic',
        'new_item'           => 'New Clinic',
        'view_item'          => 'View Clinic',
        'search_items'       => 'Search Clinics',
        'not_found'          => 'No clinics found',
        'not_found_in_trash' => 'No clinics in trash',
        'all_items'          => 'All Clinics',
    ];
    register_post_type( 'clinic', [
        'labels'             => $labels,
        'public'             => true,
        'show_in_rest'       => true,
        'has_archive'        => false,
        'rewrite'            => [ 'slug' => 'clinics' ],
        'supports'           => [ 'title', 'editor', 'thumbnail' ],
    ] );
} );

/**
 * Register Doctors CPT
 */
add_action( 'init', function() {
    $labels = [
        'name'               => 'Doctors',
        'singular_name'      => 'Doctor',
        'add_new_item'       => 'Add New Doctor',
        'edit_item'          => 'Edit Doctor',
        'new_item'           => 'New Doctor',
        'view_item'          => 'View Doctor',
        'search_items'       => 'Search Doctors',
        'not_found'          => 'No doctors found',
        'not_found_in_trash' => 'No doctors in trash',
        'all_items'          => 'All Doctors',
    ];
    register_post_type( 'doctor', [
        'labels'             => $labels,
        'public'             => true,
        'show_in_rest'       => true,
        'has_archive'        => false,
        'rewrite'            => [ 'slug' => 'doctors' ],
        'supports'           => [ 'title', 'editor', 'thumbnail' ],
    ] );
} );