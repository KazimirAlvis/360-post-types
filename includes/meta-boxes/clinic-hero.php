<?php 
$bg = plugins_url( 'assets/images/clinic-hero.jpg', __DIR__ . '/../../360-post-types.php' );
?>

<div 
  class="clinic-hero-BG-wrap" 
  style="
    background-image: url('<?php echo esc_url( $bg ); ?>');
    background-size: cover;
    background-position: center;
    padding: 4rem 1rem;
    color: #fff;
    min-height: 40vh;
  "
>
  <div class="clinic-hero-inner">
   <?php the_title('<h1 class="clinic-title">', '</h1>'); ?>

  </div>
  <div class="blk_overlay"></div>
</div>
