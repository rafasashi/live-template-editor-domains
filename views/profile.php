<?php 

include_once( get_template_directory() . '/header.php' );

include_once( get_template_directory() . '/navbar-profile.php' );

?>
		
	<main id="main" class="site-main" role="main" style="min-height:600px;">

		<?php 
		
		echo do_shortcode('[ltple-client-profile]');
		
		?>

	</main><!-- #main -->
	
<?php 

include_once( get_template_directory() . '/footer.php' );