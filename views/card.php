<?php 

	$ltple = LTPLE_Client::instance();

	ob_clean(); 
	
	// get site name
	
	$site_name = ucfirst(get_bloginfo('name'));
	
	// get site logo
	
	$site_logo = ( !empty($ltple->settings->options->logo_url) ? $ltple->settings->options->logo_url : $ltple->assets_url . 'images/home.png' );
	
	// get site icon
	
	$site_icon = get_site_icon_url(512,WP_CONTENT_URL .  '/favicon.jpeg');
	
	// get background
	
	$background_image = $ltple->image->get_banner_url($ltple->domains->currentDomain->post_author) . '?' . time();
	
	// get name
	
	$name = get_user_meta( $ltple->domains->currentDomain->post_author , 'nickname', true );
	
	// get profile picture
	
	$picture = $ltple->image->get_avatar_url( $ltple->domains->currentDomain->post_author );
	
	// get stars
	
	$stars = $ltple->stars->get_count($ltple->domains->currentDomain->post_author);
	
	// get description
	
	$description = wp_trim_words(get_user_meta($ltple->domains->currentDomain->post_author, 'description', true),50,' [...]');

	if( empty($description) ){
		
		$description = 'Nothing to say';
	}
	
	// get page title
	
	$title = $name . '\'s card | ' . $site_name;
	
	$locale = get_locale();
	$robots = 'index,follow';
	
	$canonical_url = $ltple->urls->home;
?>
<!DOCTYPE html>
<html>
	<head>
		
		<title><?php echo $title; ?></title>
		
		<link rel="shortcut icon" type="image/jpeg" href="<?php echo $site_icon; ?>" />
		
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/css/font-awesome.min.css">
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		<meta name="subject" content="<?php echo $title; ?>" />
		<meta property="og:title" content="<?php echo $title; ?>" />
		<meta name="twitter:title" content="<?php echo $title; ?>" />
		
		<meta name="author" content="<?php echo $name; ?>" />
		<meta name="creator" content="<?php echo $name; ?>" />
		<meta name="owner" content="<?php echo $title; ?>" />
		
		<meta name="language" content="<?php echo $locale; ?>" />
		
		<meta name="robots" content="<?php echo $robots; ?>" />
		
		<meta name="description" content="<?php echo $description; ?>" />
		<meta name="abstract" content="<?php echo $description; ?>" />
		<meta name="summary" content="<?php echo $description; ?>" />
		<meta property="og:description" content="<?php echo $description; ?>" />
		<meta name="twitter:description" content="<?php echo $description; ?>" />
		
		<meta name="classification" content="Business" />

		<meta name="copyright" content="<?php echo $site_name; ?>" />
		<meta name="designer" content="<?php echo $site_name; ?> team" />

		<meta name="url" content="<?php echo $canonical_url; ?>" />
		<meta name="canonical" content="<?php echo $canonical_url; ?>" />
		<meta name="original-source" content="<?php echo $canonical_url; ?>" />
		<link rel="original-source" href="<?php echo $canonical_url; ?>" />
		<meta property="og:url" content="<?php echo $canonical_url; ?>" />
		<meta name="twitter:url" content="<?php echo $canonical_url; ?>" />
		
		<meta name="rating" content="General" />
		<meta name="directory" content="submission" />
		<meta name="coverage" content="Worldwide" />
		<meta name="distribution" content="Global" />
		<meta name="target" content="all" />
		<meta name="medium" content="blog" />
		<meta property="og:type" content="article" />
		<meta name="twitter:card" content="summary" />
				
		<style>
		
			* {
			  box-sizing: border-box;
			  transition: .5s ease-in-out;
			}

			html, body {
			  background-image: linear-gradient(to bottom right,#284d6bdb,<?php echo $ltple->settings->mainColor; ?>63);
			  height: 100%;
			  margin: 0;
			  overflow: hidden;
			  font-family: helvetica neue,helvetica,arial,sans-serif;
			}
			html h1, body h1 {
			  font-size: 25px;
			  font-weight: 200;
			  color: white;
			  line-height: 30px;
			  margin-bottom: 15px;
			}
			html h2, body h2 {
				font-size: 16px;
				color: <?php echo $ltple->settings->mainColor; ?>;
				background: #fff;
				display: inline;
				padding: 3px 11px;
				box-shadow: inset 0px 0px 1px #666;
				border-radius: 250px;
			}

			#wrapper {
			  opacity: 0;
			  display: table;
			  height: 100%;
			  width: 100%;
			}
			#wrapper.loaded {
			  opacity: 1;
			  transition: 2.5s ease-in-out;
			}
			#wrapper #content {
			  display: table-cell;
			  vertical-align: middle;
			}
			#logo{
				z-index: 1;
				position: absolute;
				left: 50%;
				margin-left: -50px;
				top: 25px;				
			}
			#logo img{
				height:50px;	
				width:auto;
			}
			#card {
			  height: 400px;
			  width: 300px;
			  margin: 0 auto;
			  position: relative;
			  z-index: 1;
			  perspective: 600px;
			}
			#card #front, #card #back {
			  border-radius: 10px;
			  height: 100%;
			  width: 100%;
			  position: absolute;
			  left: 0;
			  top: 0;
			  transform-style: preserve-3d;
			  backface-visibility: hidden;
			  box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
			}
			#card #front {
			  transform: rotateY(0deg);
			  overflow: hidden;
			  z-index: 1;
			}
			#card #front #arrow {
			  position: absolute;
			  height: 50px;
			  line-height: 50px;
			  font-size: 30px;
			  z-index: 10;
			  bottom: 0;
			  right: 50px;
			  color: rgba(255, 255, 255, 0.5);
			  animation: arrowWiggle 1s ease-in-out infinite;
			}
			#card #front #top-pic {
			  height: 50%;
			  width: 100%;
			  background-image: url(<?php echo $background_image; ?>);
			  background-image: linear-gradient(to bottom right,#284d6bdb,<?php echo $ltple->settings->mainColor; ?>63), url(<?php echo $background_image; ?>);
			  background-size: cover;
			  background-position: center center;
			}
			#card #front #avatar {
			  width: 114px;
			  height: 114px;
			  top: 50%;
			  left: 50%;
			  margin: -77px 0 0 -57px;
			  border-radius: 100%;
			  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.8), 0 4px 5px rgba(107, 5, 0, 0.6), 0 0 50px 50px rgba(255, 255, 255, 0.25);
			  background-image: url(<?php echo $picture; ?>);
			  background-size: contain;
			  position: absolute;
			  z-index: 1;
			}
			#card #front #info-box {
			  height: 50%;
			  width: 100%;
			  position: absolute;
			  display: table;
			  left: 0;
			  bottom: 0;
			  background: <?php echo $ltple->settings->mainColor; ?>cc;
			  padding: 50px 0px;
			}
			#card #front #social-bar {
			  height: 50px;
			  width: 100%;
			  position: absolute;
			  bottom: 0;
			  left: 0;
			  line-height: 50px;
			  font-size: 18px;
			  text-align: center;
			}
			#card #front #social-bar a {
			  display: inline-block;
			  color: #ffffffb0;
			  font-size:13px;
			  text-decoration: none;
			  padding: 5px;
			  line-height: 18px;
			  border-radius: 5px;
			}
			#card #front #social-bar a:hover {
			  color: #450300;
			  background: rgba(255, 255, 255, 0.3);
			  transition: .25s ease-in-out;
			}
			#card #back {
			  transform: rotateY(180deg);
			  background-color: rgba(255, 255, 255, 0.6);
			  display: table;
			  z-index: 2;
			  font-size: 13px;
			  line-height: 20px;
			  padding: 50px;
			}
			#card #back .back-info {
			  text-align: justify;
			  text-justify: inter-word;
			}
			#card #back .back-info a {
				
				color:<?php echo $ltple->settings->mainColor; ?>;
			}
			#card #back #social-bar {
			  height: 50px;
			  width: 100%;
			  position: absolute;
			  bottom: 0;
			  left: 0;
			  line-height: 50px;
			  font-size: 18px;
			  text-align: center;
			}
			#card #back #social-bar a {
			  display: inline-block;
			  line-height: 18px;
			  color: <?php echo $ltple->settings->mainColor; ?>;
			  text-decoration: none;
			  padding: 5px;
			  border-radius: 5px;
			}
			#card #back #social-bar a:hover {
			  color: #450300;
			  background: rgba(223, 74, 66, 0.5);
			  transition: .25s ease-in-out;
			}
			#card .info {
			  display: table-cell;
			  height: 100%;
			  vertical-align: middle;
			  text-align: center;
			}
			#card.flip #front {
			  transform: rotateY(180deg);
			}
			#card.flip #back {
			  transform: rotateY(360deg);
			}

			#background {
			  position: fixed;
			  background-color: black;
			  top: 0;
			  left: 0;
			  height: 100%;
			  width: 100%;
			}
			#background #background-image {
			  height: calc(100% + 60px);
			  width: calc(100% + 60px);
			  position: absolute;
			  top: -30px;
			  left: -30px;
			  -webkit-filter: blur(10px);
			  background-image: url(<?php echo $background_image; ?>);
			  background-image: linear-gradient(to bottom right,#284d6bdb,<?php echo $ltple->settings->mainColor; ?>63), url(<?php echo $background_image; ?>);
			  background-size: cover;
			  background-position: center;
			}

			@keyframes arrowWiggle {
			  0% {
				right: 50px;
			  }
			  50% {
				right: 35px;
			  }
			  100% {
				right: 50px;
			  }
			}
		
		</style>
	
	</head>
	<body>
	
		<div id="wrapper">
		
		  <div id="content">
		  
			<a id="logo" href="<?php echo REW_PRIMARY_SITE; ?>">
			
				<img src="<?php echo $site_logo; ?>">
			
			</a>		  
					  
			<div id="card">
			  <div id="front">
				<div id="arrow"><i class="fa fa-arrow-left"></i></div>
				<div id="top-pic"></div>
				<div id="avatar"></div>
				<div id="info-box">
				  <div class="info">
					<h1><?php echo $name; ?></h1>
					<h2>
					
						<span class="fa fa-star" aria-hidden="true"></span> 
						
						<?php echo $stars; ?>			
					
					</h2>
				  </div>
				</div>
				<div id="social-bar">
				  <a href="javascript:void" class="more-info">
					<i class="fa fa-user"></i> Flip me
				  </a>
				</div>
			  </div>
			  <div id="back">
				<div class="back-info">
					<h3>About</h3>
					<p><?php echo $description; ?></p>
					<a href="<?php echo $ltple->profile->url . '/about/'; ?>">Read More</a>
				</div>
				<div id="social-bar">
				
				  <a href="javascript:void" class="more-info">
					<i class="fa fa-undo"></i>
				  </a>
				</div>
			  </div>
			</div>
			<div id="background">
			  <div id="background-image"></div>
			</div>
		  </div>
		</div>
	
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	
		<script>

			$(window).load(function(){
				
			  $('#wrapper').addClass('loaded');
			})

			$('.more-info').click(function(){
				
			  $("#card").toggleClass('flip');
			  $('#arrow').remove();
			});
			
			$('#background').click(function(){
				
			  $('#card').removeClass('flip');
			})
		
		</script>
	
	</body>
</html>

<?php exit; ?>