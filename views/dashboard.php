<?php 

$ltple = LTPLE_Client::instance();

include_once( $ltple->views . '/profile/header.php' );

include_once( $ltple->views . '/profile/navbar.php' );

?>

	<main id="main" class="site-main ltple-domains" role="main" style="min-height:calc( 100vh - 110px );">

		<?php 
			
			while ( have_posts() ) : the_post();
				
				?>
				
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>	

					<div class="entry-content" style="display:inline-block;width:100%;">
					
						<?php 
						
						the_content();
						
						?>
						
					</div><!-- .entry-content -->
				</article><!-- #post-## -->
				
				<?php
				
			endwhile;
		?>

	</main><!-- #main -->
	
<?php 

include_once( $ltple->views . '/profile/footer.php' );