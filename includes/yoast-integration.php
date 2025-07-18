<?php
function cpt360_append_clinic_bio_to_yoast( $content, $post ) {
    $bio = get_post_meta( $post->ID, '_cpt360_clinic_bio', true );
    
    // Log which filter is running
    if ( doing_filter( 'wpseo_rest_pre_analysis_post_content' ) ) {
        error_log( "[Yoast REST] firing for clinic#{$post->ID}" );
    } else {
        error_log( "[Yoast PHP] firing for clinic#{$post->ID}" );
    }
    
    // Log a snippet of the raw bio
    error_log( "[Yoast DEBUG] bio starts: " . substr( wp_strip_all_tags( $bio ), 0, 80 ) );
    
    $new = $content . ' ' . wp_strip_all_tags( $bio );
    // Log the tail of the final content Yoast will scan
    error_log( "[Yoast DEBUG] final content ends in: " . substr( $new, -80 ) );
    
    return $new;
}


// Live analysis in the editor (even Classic Editor)
add_filter(
  'wpseo_rest_pre_analysis_post_content',
  'cpt360_append_clinic_bio_to_yoast',
  10, 2
);

