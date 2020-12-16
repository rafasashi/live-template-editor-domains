<?php 

$ltple = LTPLE_Client::instance();

include_once( $ltple->views . '/profile/header.php' );

include_once( $ltple->views . '/profile/navbar.php' );

?>
		
	<main id="main" class="site-main ltple-domains" role="main">

		<?php 
		
		echo do_shortcode('[ltple-client-profile]');
		
		?>

	</main><!-- #main -->
	
<?php 

include_once( $ltple->views . '/profile/footer.php' );