<script>
    // Check if the URL has the `open=true` query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const shouldOpen = urlParams.has('open');

    // Add the `open` attribute to the <pr360-questionnaire> tag if the parameter is present
    window.addEventListener('DOMContentLoaded', () => {
        const questionnaire = document.querySelector('pr360-questionnaire');
        if (shouldOpen && questionnaire) {
            questionnaire.setAttribute('open', '');
        }
    });
</script>

<?php
// grab your saved Clinic ID
$assess_id = get_post_meta(get_the_ID(), '_cpt360_assessment_id', true);

if ($assess_id) :
    // build your assessment link too if you need it
    $url = esc_url(home_url('/take-assessment/?clinic_id=' . $assess_id));
?>

    <!-- PR360 questionnaire component with dynamic site-id -->
<?php
// 1) Determine your environment
$env = function_exists('wp_get_environment_type')
       ? wp_get_environment_type()
       : 'production';

// 2) Build the classes array only on local/dev
$classes = [];
if ( 'development' === $env /* or 'local' if you prefer */ ) {
    $classes[] = 'btn';
    $classes[] = 'btn_green';
}

// 3) Turn it into a string (or leave empty)
$class_attr = $classes
    ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"'
    : '';
?>
<pr360-questionnaire
    url="wss://app.patientreach360.com/socket"<?php echo $class_attr; ?>
    site-id="<?php echo esc_attr( $assess_id ); ?>">
    Take Risk Assessment Now
</pr360-questionnaire>

<?php
endif;
?>