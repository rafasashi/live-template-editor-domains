<?php 

$theme = LTPLE_Theme::instance();

include_once( $theme->dir . '/header.php' );

?>
	
	<?php include_once( $theme->dir . '/navbar-profile.php' );	?>
		
	<main id="main" class="site-main" role="main" style="min-height:600px;">

		<?php 
		
		echo do_shortcode('[ltple-client-profile]');
		
		?>

	</main><!-- #main -->
	
<?php include_once( $theme->dir . '/footer.php' );	?>