<?php 

include_once( get_template_directory() . '/header.php' );

include_once( get_template_directory() . '/navbar-profile.php' );

?>
		
	<main id="main" class="site-main ltple-domains" role="main">

		<?php 
		
		echo do_shortcode('[ltple-client-profile]');
		
		?>

	</main><!-- #main -->
	
<?php 

include_once( get_template_directory() . '/footer.php' );