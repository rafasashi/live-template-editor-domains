<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Domains {

	/**
	 * The single instance of LTPLE_Domains.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;
	
	var $message			= '';
	var $enable_domains 	= 'off';
	var $enable_subdomains 	= 'off';
	var $default_domains 	= null;
	var $disclaimer			= '';
	var $currentDomain		= null;
	var $userDomains		= array();
	
	var $uri 	= '';
	var $tab 	= '';
	var $slug 	= '';
	
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file='', $parent, $version = '1.0.0' ) {

		$this->parent = $parent;
	
		$this->_version = $version;
		$this->_token	= md5($file);
		
		// Load plugin environment variables
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->vendor  		= WP_CONTENT_DIR . '/vendor';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= home_url( trailingslashit( str_replace( ABSPATH, '', $this->dir ))  . 'assets/' );
		
		//$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = '';

		$this->enable_domains 		= get_option( $this->parent->_base . 'enable_domains', 'off' );
		$this->enable_subdomains 	= get_option( $this->parent->_base . 'enable_subdomains', 'off' );

		register_activation_hook( $this->file, array( $this, 'install' ) );
		
		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		
		$this->settings = new LTPLE_Domains_Settings( $this->parent );
		
		$this->admin = new LTPLE_Domains_Admin_API( $this );

		if ( !is_admin() ) {

			// Load API for generic admin functions
			
			add_action( 'wp_head', array( $this, 'header') );
			add_action( 'wp_footer', array( $this, 'footer') );
		}
		
		// Handle localisation
		
		$this->load_plugin_textdomain();
		
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		//init addon 
		
		add_action('init', array( $this, 'init_domain' ));
		
		add_filter('ltple_profile_redirect', array( $this, 'redirect_profile' ),99999);
		
		// site name
		
		add_filter( 'ltple_site_name',array($this,'filter_site_name'),99999,1);

		// social icons in profile
		
		add_action( 'ltple_before_social_icons', array( $this, 'get_social_icons'));		
		
		// Custom template path
		
		add_filter( 'template_include', array( $this, 'template_path'), 1 );	
			
		// add user attributes
		
		add_filter( 'ltple_user_loaded', array( $this, 'add_user_attribute'));	
		
		// add panel shortocode

		add_shortcode('ltple-client-domains', array( $this , 'get_panel_shortcode' ) );	

		// add panel url
		
		add_filter( 'ltple_urls', array( $this, 'get_panel_url'));	
		
		// add link to theme menu
		
		//add_filter( 'ltple_view_my_profile', array( $this, 'add_theme_menu_link'),9);	
		
		add_filter( 'ltple_collect_user_information', array( $this, 'collect_user_information'));	
		
		add_filter( 'ltple_profile_completeness', array( $this, 'get_profile_completeness'),1,3);	
				
		// add layer fields
		
		add_filter( 'ltple_account_options', array( $this, 'add_account_options'),10,1);
		add_filter( 'ltple_account_plan_fields', array( $this, 'add_layer_plan_fields'),10,2);
		add_action( 'ltple_save_layer_fields', array( $this, 'save_layer_fields' ),10,1);			
		
		// add layer colums
		
		add_filter( 'ltple_layer_option_columns', array( $this, 'add_layer_columns'));
		
		add_filter( 'ltple_layer_column_content', array( $this, 'add_layer_column_content'),10,2);
		
		// handle plan
		
		add_filter( 'ltple_subscription_plan_info', array( $this, 'get_subscription_plan_info'),10,2);	
		
		add_filter( 'ltple_api_layer_plan_option', array( $this, 'add_api_layer_plan_option'),10,1);	
		add_filter( 'ltple_api_layer_plan_option_total', array( $this, 'add_api_layer_plan_option_total'),10,2);
		
		add_filter( 'ltple_plan_table', array( $this, 'add_plan_table_attributes'),10,2);
		add_filter( 'ltple_plan_subscribed', array( $this, 'handle_subscription_plan'),10);
		
		add_filter( 'ltple_user_plan_option_total', array( $this, 'add_user_plan_option_total'),10,2);
		add_filter( 'ltple_user_plan_info', array( $this, 'add_user_plan_info'),10,1);
		
		add_filter( 'ltple_dashboard_manage_sidebar', array( $this, 'get_sidebar_content' ),2,3);
			
		add_action( 'ltple_user-page_link', array( $this, 'filter_user_page_link'),10,2);	
		
		add_action( 'ltple_user-product_link', array( $this, 'filter_user_product_link'),10,2);	
						
		add_action( 'ltple_edit_layer_title', array( $this, 'get_edit_layer_url'));
			
		$this->add_star_triggers();
		
		// addon post types
		
		$this->parent->register_post_type( 'user-domain', __( 'User domains', 'live-template-editor-client' ), __( 'User domains', 'live-template-editor-client' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> 'user-domains',
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title','author'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> 'dashicons-admin-post',
		));

		add_filter('ltple_editor_preview_url', array( $this,'get_editor_preview_url'),1,2);
		
		// sitemaps
		
		add_filter('wp_sitemaps_post_types',array($this,'filter_sitemaps_post_types'),99999,1);
		
		add_filter('wp_sitemaps_taxonomies',array($this,'filter_sitemaps_taxonomies'),99999,1);
		
		add_filter('wp_sitemaps_posts_query_args',array($this,'filter_sitemaps_posts_query_args'),99999,1);
		
		add_filter('wp_sitemaps_users_query_args',array($this,'filter_sitemaps_users_query_args'),99999,1);
		
		add_filter('wp_sitemaps_posts_entry',array($this,'filter_sitemaps_posts_entry'),0,3);
		
		// feeds
		
		add_filter('pre_get_posts',array($this,'filter_feed_query'),99999999,1);
		
	} // End __construct ()
	
	public function redirect_profile(){

		// parse query string
		
		parse_str($_SERVER['QUERY_STRING'],$args);
		
		// is editor preview

		if( !empty($args['preview']) ){
			
			if( $args['preview'] == 'ltple' )
			
				return;
				
			if( $args['preview'] == 'true' && $this->parent->user->loggedin && $this->parent->user->ID == $this->parent->profile->id )
		
				return;
		}
		
		// is not profile
		
		if( !$this->parent->profile->id > 0 )
		
			return;
			
		// get primary domain
		
		if( !$primary_domain = $this->get_primary_domain($this->parent->profile->id) )
		
			return;

		// is primary domain
		
		if( strpos($this->parent->urls->current,$primary_domain) === 0 ){
			
			if( $this->parent->user->loggin )
			
				return;
			
			if( $this->parent->profile->tab == 'home' )
				
				return;
				
			if( $primary_domain == $this->parent->urls->primary )
			
				return;
		}
		
		// redirect profile url
		
		if( $this->is_primary_tab() ){
			
			$url = $this->parent->urls->primary;
		}
		else{
			
			$url = $primary_domain;
		}
		
		if( !empty($this->parent->profile->tab) ){
			
			$url .= preg_replace('#^\/' . $this->parent->profile->slug . '\/' . $this->parent->profile->id . '#', '', $_SERVER['REQUEST_URI']);
		}
		elseif( !empty($_SERVER['QUERY_STRING']) ){

			$url .= '?' . $_SERVER['QUERY_STRING'];
		}
		
		if( $url != $this->parent->urls->current ){
			
			wp_redirect($url);
			exit;
		}
	}
	
	public function get_default_domains(){
		 
		if( is_null($this->default_domains) ){
		
			$this->default_domains = array();
		
			$domains = explode(PHP_EOL,get_option( $this->parent->_base . 'default_domains', '' ));
			
			if(!empty($domains)){
				
				foreach( $domains as $domain ){
					
					$domain = trim($domain);
					
					if( !empty($domain) ){
						
						$this->default_domains[] = $domain;
					}
				}
			}
		}
		
		return $this->default_domains;
	}
	
	public function get_domain_type( $domain ){
		
		$domain_type = 'domain';
		
		// get shared domains
		
		$default_domains = $this->get_default_domains();
		
		foreach( $default_domains as $default_domain ){
		
			if( strpos( $domain, '.' . $default_domain ) !== FALSE ){
				
				$domain_type = 'subdomain';
			}
		}
		
		return $domain_type;
	}
	
	public function get_user_domain_list( $user = null ){
		
		$list = array('subdomain'=>[],'domain'=>[]);
		
		$user_id = 0;
		
		if( is_numeric($user) ){
			
			$user_id = intval($user);
		}
		elseif( !empty($user->ID) ){
			
			$user_id = $user->ID;
		}
		
		if( !isset($this->userDomains[$user_id]) ){
			
			if( $domains = get_posts(array(
				
				'author'   		=> $user_id,
				'post_type'   	=> 'user-domain',
				'post_status' 	=> 'publish',
				'orderby' 		=> 'date',
				'order'         => 'ASC',
				//'numberposts' => -1,
				
			))){
				
				$is_primary = true;
				
				foreach( $domains as $domain ){
					
					$domain->urls = $this->get_domain_urls($domain->ID);
				
					$domain->type = $this->parent->domains->get_domain_type($domain->post_title);
					
					$domain->is_primary = $is_primary;
					
					$is_primary = false;
					
					$list[$domain->type][] = $domain;
				}
				
				$this->userDomains[$user_id] = $list;
			}
		}
		else{
			
			$list = $this->userDomains[$user_id];
		}
			
		return $list;
	}
	
	public function get_domain_urls($domain_id){
		
		if( $urls = get_post_meta($domain_id ,'domainUrls', true) ){
			
			foreach( $urls as $id => $path ){
				
				if( !empty($path) ){
					
					$urls[$id] = trailingslashit($path); 
				}
			}
		}
	
		return $urls;
	}
	
	public function get_primary_domain( $user = null ){
		
		$primary_domain = $this->parent->urls->home;
		
		if( $list = $this->get_user_domain_list( $user )){
			
			foreach( $list as $domain_type => $domains ){
				
				foreach( $domains as $domain ){
					
					if( $domain->is_primary ){
						
						$primary_domain = $this->parent->request->proto . $domain->post_title;
						
						if( defined('REW_DEV_ENV') && REW_DEV_ENV === true ){
							
							$primary_domain = str_replace('.','--',untrailingslashit($primary_domain)) . '.' . REW_SERVER;
						}
						
						break 2;
					}
				}
			}
		}
		
		return $primary_domain;
	}
	
	public function is_primary_domain(){
		
		if( $primary_domain = $this->get_primary_domain($this->parent->profile->id) ){

			if( strpos($this->parent->urls->current,$primary_domain) === 0 )
			
				return true;
		}
		
		return false;
	}
	
	public function is_primary_tab(){
		
		if( in_array($this->parent->profile->tab,array(
			
			'editor',
			'ranking',
			
		)) ){
			
			return true;
		}
		
		return false;
	}
	
	public function filter_site_name($site_name){

		if( $this->is_primary_domain() ){
			
			$site_name =  ucfirst(get_user_meta( $this->parent->profile->id , 'nickname', true ));
		}
	
		return $site_name;
	}
		
	public function template_path( $template_path ){
		
		return $template_path;
	}
	
	public function get_editor_preview_url($preview_url,$post_id){
		
		$u = parse_url($preview_url);
		
		if( !empty($u['host']) && $_SERVER['HTTP_HOST'] != $u['host'] ){
			
			if( $domain = $this->get_domain($u['host']) ){
				
				$preview_url = $this->parent->urls->profile . $domain->post_author . ( !empty($u['path']) ? $u['path'] : '/' ) . '?' . $u['query'] . '&domain=' . $u['host'];
			}
		}
		
		return $preview_url;
	}
	
	
	public function get_domain($domain_name=''){
		
		$domain = null;
		
		if( $domains = get_posts(array(
		
			'post_type' => 'user-domain',
			'title' 	=> $domain_name,
		
		)) ){
		
			$domain = $domains[0];
			
			// get type
			
			$domain->type = $this->get_domain_type( $domain->post_title );
			
			// is primary domain
			
			$domain->is_primary = false;
			
			$user_domains = $this->get_user_domain_list( $domain->post_author );
			
			if( !empty($user_domains[$domain->type]) ){
			
				foreach( $user_domains[$domain->type] as $user_domain ){
					
					if( $user_domain->post_title == $domain_name ){
						
						$domain->is_primary = $user_domain->is_primary;
						
						break;
					}
				}
			}
		}
		
		return $domain;
	}
	
	public function init_domain(){	
		
		// get domain	
		
		$domain_name = false;
		
		if( !empty($_GET['preview']) && $_GET['preview'] == 'ltple' && !empty($_GET['domain']) ){
			
			$domain_name = $_GET['domain'];
		}
		elseif( defined('REW_LTPLE') && in_array(REW_LTPLE,['domain','subdomain']) ){

			$domain_name = defined('REW_SITE') ? REW_SITE : $_SERVER['HTTP_HOST'];
		}
		
		if( !empty($domain_name) ){
			
			if( $this->currentDomain = $this->get_domain($domain_name) ){
				
				if( !$this->parent->user->loggedin ){
					
					if( empty($_COOKIE['_ltple_disclaimer']) ){
						
						//check disclaimer
							
						$domainType = $this->get_domain_type( $this->currentDomain->post_title );
				
						if( $domainType == 'subdomain' ){
						
							$this->disclaimer = get_option($this->parent->_base  . 'subdomain_disclamer');
						}
						
						if( !empty($this->disclaimer) ){
							
							include( $this->views . '/disclaimer.php' );
						}
					}
				}
					
				// get request uri

				$request_uri = false;
				
				if( !empty($_GET['preview']) && $_GET['preview'] == 'ltple' ){
					
					$prefix = '/' . $this->parent->profile->slug . '/' . $this->currentDomain->post_author;
					
					if( strpos($_SERVER['REQUEST_URI'],$prefix) === 0 ){
					
						$request_uri = str_replace( $prefix, '' , $_SERVER['REQUEST_URI'] );
					}
				}
				else{
					
					$request_uri = $_SERVER['REQUEST_URI'];
				}
				
				if( is_string($request_uri) ){
					
					list($this->uri) = explode('?', $request_uri);
					
					// get urls
				
					if( $this->currentDomain->urls = $this->get_domain_urls($this->currentDomain->ID) ){

						// get path
						
						foreach( $this->currentDomain->urls as $layerId => $path ){
							
							if( '/' . $path == $this->uri ){
								
								if( $layer = get_post($layerId) ){
									
									// check license
									
									if( $this->parent->users->get_user_remaining_days($this->currentDomain->post_author) > 0 ) {
										
										$this->set_user_layer($layer);
									}
									elseif( !empty($this->parent->user->ID) && intval($this->currentDomain->post_author) == $this->parent->user->ID ){
										
										echo 'License expired, please renew your subscription.';
									}
									else{
										
										include($this->parent->views . '/profile/card.php');
									}

									exit;
								}
							}
						}
						
						if( $this->currentDomain->is_primary === true ){
							
							$this->set_user_profile();
						}
						else{
							
							include($this->parent->views . '/profile/card.php');
						}
					}
					elseif( $this->currentDomain->is_primary === true ){
						
						$this->set_user_profile();
					}
					elseif( !empty($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '/' ){
						
						wp_redirect($this->parent->urls->home);
						exit;
					}
					else{
						
						include($this->parent->views . '/profile/card.php');					
					}
				}
				else{
					
					echo 'Wrong domain request uri...';
					exit;					
				}
			}
			else{
				
				echo 'This domain is not registered yet...';
				exit;
			}
		}
	}
	
	public function set_user_layer($layer){
		
		if( !empty($layer) && $layer->post_status == 'publish' ){
		
			// output subdomain layer
						
			$this->parent->layer->set_layer($layer);
					
			include( $this->parent->views . '/layer.php' );
		}
		else{
			
			include($this->parent->views . '/profile/card.php');
		}
	}
	
	public function set_user_profile(){

		remove_action('template_redirect', 'redirect_canonical');
					
		add_filter('ltple_profile_id', function($id){
			
			return intval($this->currentDomain->post_author);
			
		},99999);
		
		add_filter('ltple_profile_tab', function($tab){
			
			$uri = explode('/',$this->uri);
			
			$tab = !empty($uri[1]) ? $uri[1] : $tab;
			
			return $tab;
		
		},99999);
		
		add_filter('ltple_profile_slug', function($slug){
			
			$uri = explode('/',$this->uri);
			
			$slug = !empty($uri[2]) ? $uri[2] : $slug;
			
			return $slug;
		
		},99999);
		
		add_filter('ltple_profile_url', function($url){
			
			$url = $this->parent->request->proto . $this->currentDomain->post_title;

			return $url;
			
		},99999);

		add_action( 'template_include', function($template){
			
			if( $this->parent->profile->id > 0 ){
				
				if( !$this->parent->profile->in_tab ){
					
					// detect ltple shortcode

					global $post;
					
					if( strpos($post->post_content,'[ltple-client-') !== false ){
						
						if( $this->parent->inWidget  ){
							
							// TODO widget view
							
							return $this->views . '/dashboard.php';
						}
						elseif( !$this->parent->user->loggedin  ){
						
							// TODO login view
						
							return $this->views . '/dashboard.php';
						}
						elseif( $this->parent->user->ID == $this->parent->profile->id ){
							
							return $this->views . '/dashboard.php';
						}
					}
					elseif( $this->parent->profile->tab == 'login' ){
						
						return $this->views . '/dashboard.php';
					}
					elseif( $this->parent->inWidget ){
						
						return $template;
					}
				}
				
				// redirect to primary site
				
				$url = $this->parent->urls->primary . $this->uri;
				
				if( !empty($_SERVER['QUERY_STRING']) ){
					
					$url .= '?' . $_SERVER['QUERY_STRING'];
				}
				
				wp_redirect($url);
				exit;		
			}
			
			return $template;
			
		},999999);		
	}
	
	public function get_social_icons(){
		
		if( $this->parent->profile->id > 0 ){
		
			$user_domains = $this->get_user_domain_list( $this->parent->profile->id );
			
			if( !empty($user_domains['subdomain']) ){
				
				foreach( $user_domains['subdomain'] as $domain ){
					
					if( !empty($domain->post_title) ){
							
						echo'<a href="' . $this->parent->request->proto . $domain->post_title . '" style="margin:5px;display:inline-block;" ref="dofollow">';
							
							echo'<img src="' . $this->parent->settings->options->social_icon . '" style="height:30px;width:30px;border-radius:250px;" />';
							
						echo'</a>';
					}
				}
			}
		}
	}
	
	public function header(){
		
		//echo '<link rel="stylesheet" href="https://raw.githubusercontent.com/dbtek/bootstrap-vertical-tabs/master/bootstrap.vertical-tabs.css">';	
	}
	
	public function footer(){
		
		
	}
	
	public function add_user_attribute(){
		
		// add user domains
			
		$this->parent->user->domains = new LTPLE_Domains_User( $this->parent );	
	}
	
	public function get_panel_shortcode(){

		if($this->parent->user->loggedin){

			if( !empty($_REQUEST['output']) && $_REQUEST['output'] == 'widget' ){
				
				include($this->views . '/widget.php');
			}
			else{
			
				include($this->parent->views . '/navbar.php');
				
				if($this->parent->user->loggedin){
					
					add_action('ltple_dashboard_sidebar',array($this->parent->dashboard,'get_sidebar'),10,3);
					
					include($this->views . '/panel.php');
				}
				else{
					
					echo $this->parent->login->get_form();
				}
			}
		}
		else{
			
			echo'<div style="font-size:20px;padding:20px;margin:0;" class="alert alert-warning">';
				
				echo'You need to log in first...';
				
				echo'<div class="pull-right">';

					echo'<a style="margin:0 2px;" class="btn-lg btn-success" href="'. wp_login_url( $this->parent->request->proto . $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ) .'">Login</a>';
					
					echo'<a style="margin:0 2px;" class="btn-lg btn-info" href="'. wp_login_url( $this->parent->urls->gallery ) .'&action=register">Register</a>';
				
				echo'</div>';
				
			echo'</div>';
		}		
	}
	
	public function get_panel_url(){
		
		$slug = get_option( $this->parent->_base . 'domainSlug' );
		
		if( empty( $slug ) ){
			
			$post_id = wp_insert_post( array(
			
				'post_title' 		=> 'Domains',
				'post_type'     	=> 'page',
				'comment_status' 	=> 'closed',
				'ping_status' 		=> 'closed',
				'post_content' 		=> '[ltple-client-domains]',
				'post_status' 		=> 'publish',
				'menu_order' 		=> 0
			));
			
			$slug = update_option( $this->parent->_base . 'domainSlug', get_post($post_id)->post_name );
		}
		
		$this->parent->urls->domains = $this->parent->urls->home . '/' . $slug . '/';		
	}
	
	public function get_sidebar_content($sidebar,$currentTab,$output){
		
		$sidebar .= '<li'.( ($currentTab == 'default' || $currentTab == 'urls') ? ' class="active"' : '' ).'><a href="'.$this->parent->urls->domains . '"><span class="fa fa-network-wired"></span> Domains</a></li>';

		//$sidebar .= '<li'.( $currentTab == 'urls' ? ' class="active"' : '' ).'><a href="'.$this->parent->urls->domains . '?tab=urls">Urls & Pages</a></li>';
		
		return $sidebar;
	}
	
	public function filter_user_page_link($post_link,$post){
		
		$user_domains = $this->get_user_domain_list( $post->post_author );

		if( !empty($user_domains) ){
			
			foreach( $user_domains as $domains ){
				
				if( !empty($domains) ){
				
					foreach( $domains as $domain){
						
						if( isset($domain->urls[$post->ID]) ){
							
							if( $post->post_status == 'publish' ){
								
								return $this->parent->request->proto . trailingslashit($domain->post_title . '/' . $domain->urls[$post->ID]);
							}
							else{
								
								return $this->parent->request->proto . $domain->post_title;
							}
						}
					}
				}
			}
		}
		
		return $post_link;
	}
	
	public function filter_user_product_link($post_link,$post){
		
		if( $primary_domain = $this->get_primary_domain($post->post_author) ){
			
			$url = parse_url($post_link);
			
			if( !empty($url['path']) ){
				
				$post_link = $primary_domain . preg_replace('#^\/' . $this->parent->profile->slug . '\/' . $post->post_author . '#', '', $url['path']);
			}
			else{
			
				$post_link = $primary_domain;
			}
		}
		
		return $post_link;
	}
	
	public function get_edit_layer_url(){
		
		if( $this->parent->layer->is_public($this->parent->user->layer) && $this->parent->layer->is_hosted($this->parent->user->layer) ){
			
			echo'<div id="layer_url" style="margin:15px 0 0 0;padding:15px 0 0 0;border-top:1px solid #eee;">';
				
				echo'<label style="margin:0 11px 0 7px;">URL</label>';
				
				if( $this->parent->layer->can_customize_url($this->parent->user->layer) ){
				
					echo'<select name="domainUrl[domainId]" class="form-control input-sm" style="width:auto;display:inline-block;">';
						
						$default_url = 'None';
						
						if( $this->parent->user->layer->post_type != 'user-page' ){
						
							$post_type = get_post_type_object($this->parent->user->layer->post_type);
							
							if( !empty($post_type->rewrite['slug']) ){
							
								$default_url = str_replace($this->parent->request->proto,'',$this->parent->urls->parse_permalink($this->parent->urls->home . '/' . $post_type->rewrite['slug'],$this->parent->user->layer));
							}
						}
						
						echo'<option value="-1">'.$default_url.'</option>';
						
						if( !empty($this->parent->user->domains->list) ){
							
							$domainName = '';
							
							foreach( $this->parent->user->domains->list as $domain_type => $domains ){
								
								foreach( $domains as $domain ){
								
									if( isset($domain->domainUrls[$this->parent->user->layer->ID]) ){
										
										$domainName = $domain->post_title;
									}																
									
									echo'<option value="' . $domain->ID . '"' . ( ( $domainName == $domain->post_title ) ? ' selected="true"' : '' ) . '>';
									
										echo $domain->post_title;

									echo'</option>';
								}
							}
						}
					
					echo'</select>';
					
					echo' / ';
					
					$domainPath = $this->parent->user->layer->post_name;
					
					foreach($this->parent->user->domains->list as $domains){
						
						foreach($domains as $domain){
						
							if(isset($domain->urls[$this->parent->user->layer->ID])){
								
								$domainPath = $domain->urls[$this->parent->user->layer->ID];
							}
						}
					}
					
					$domainPath = trailingslashit($domainPath);
					
					echo'<input type="text" name="domainUrl[domainPath]" value="'.$domainPath.'" placeholder="category/page-title" class="form-control input-sm" style="width:270px;display:inline-block;" />';
				}
				elseif( $this->parent->user->layer->post_status == 'publish' ){
					
					$permalink 	= $this->get_permalink($this->parent->user->layer);
	
					echo '<a href="' . $permalink . '" target="_blank">' . $permalink . '</a>';
				}
				else{

					echo '<i>Select "Public" status </i>';
				}
			
			echo '</div>';
		}
	}
	
	public function get_permalink($post){
		
		if( is_numeric($post) ){
			
			$post = get_post($post);
		}
		
		if( $permalink = get_permalink($post) ){

			$profile_url 	= $this->parent->profile->get_user_url($post->post_author);
		
			$primary 		= $this->get_primary_domain($post->post_author);
		
			$permalink 		= str_replace($profile_url,$primary,$permalink);
		}
		
		return $permalink;
	}		
	
	public function add_theme_menu_link(){

		// add theme menu link

		echo'<li style="position:relative;background:#182f42;">';
			
			echo '<a href="'. $this->parent->urls->domains . '"><span class="fa fa-network-wired" aria-hidden="true"></span> Connected Domains</a>';

		echo'</li>';

	}

	public function collect_user_information(){

		if( $this->parent->user->remaining_days > 0 ) {
			
			$domains = $this->get_user_domain_list($this->parent->user);
			
			$user_subdomains = ( !empty($domains['subdomain']) ? count($domains['subdomain']) : 0 );
			
			if( $user_subdomains === 0 ){
			
				$total_plan_subdomains = $this->parent->user->domains->get_total_plan_subdomains();
														
				if( $total_plan_subdomains > 0 ){		

					$license_holder_subdomains = count($this->parent->user->domains->get_license_holder_domain_list('subdomain'));
					
					if( $total_plan_subdomains > $license_holder_subdomains ){
					
						include($this->views . '/modals/modal.php');
					}
				}
			}
		}
	}

	public function get_profile_completeness($completeness,$user,$user_meta){
		
		$completeness['domains'] = array(
		
			'name' 		=> 'Domain Name',
			'complete' 	=> false,
			'points' 	=> 3,
		);
		
		$primary_domain = $this->get_primary_domain($user);
		
		if( $primary_domain != $this->parent->urls->home ){
			
			$completeness['domains']['complete'] = true;
		}			
		
		return $completeness;
	}	

	public function add_account_options($term_id){
		
		if(!$domain_amount = $this->parent->layer->get_plan_amount($term_id,'domain')){
			
			$domain_amount = 0;
		}
		
		if(!$subdomain_amount = $this->parent->layer->get_plan_amount($term_id,'subdomain')){
			
			$subdomain_amount = 0;
		}

		$this->parent->layer->options['domain_amount'] 		= $domain_amount;
		$this->parent->layer->options['subdomain_amount'] 	= $subdomain_amount;
	}
	
	public function add_layer_plan_fields( $taxonomy, $term_id ){
		
		$data = [];

		if( !empty($term_id) ){
			
			$data['domain_amount'] 		= $this->parent->layer->get_plan_amount($term_id,'domain'); 
			$data['subdomain_amount'] 	= $this->parent->layer->get_plan_amount($term_id,'subdomain'); 
		}
		
		echo'<div class="form-field" style="margin-bottom:15px;">';
			
			echo'<label for="'.$taxonomy.'-domain-amount">Domains</label>';

			echo $this->get_layer_domain_fields($taxonomy,$data);
			
		echo'</div>';

		echo'<div class="form-field" style="margin-bottom:15px;">';
			
			echo'<label for="'.$taxonomy.'-subdomain-amount">Subdomains</label>';

			echo $this->get_layer_subdomain_fields($taxonomy,$data);
			
		echo'</div>';
	}
	
	public function get_layer_domain_fields( $taxonomy_name, $args = [] ){
		
		//get domain amount
		
		$domain_amount = 0;
		
		if(isset($args['domain_amount'])){
			
			$domain_amount = $args['domain_amount'];
		}		
		
		//get fields
		
		$fields='';
		
		$fields.='<div class="input-group">';

			$fields.='<span class="input-group-addon" style="color: #fff;padding: 5px 10px;background: #9E9E9E;">+</span>';
			
			$fields.='<input type="number" step="1" min="0" max="1000" placeholder="0" name="'.$taxonomy_name.'-domain-amount" id="'.$taxonomy_name.'-domain-amount" style="width: 60px;" value="'.$domain_amount.'"/>';					
			
		$fields.='</div>';
		
		$fields.='<p class="description">The amount of additional connected domains for hosting purpose </p>';

		return $fields;
	}
	
	public function get_layer_subdomain_fields( $taxonomy_name, $args = [] ){
		
		//get subdomain amount
		
		$subdomain_amount = 0;
		
		if(isset($args['subdomain_amount'])){
			
			$subdomain_amount = $args['subdomain_amount'];
		}
		
		//get fields
		
		$fields='';
		
		$fields.='<div class="input-group">';

			$fields.='<span class="input-group-addon" style="color: #fff;padding: 5px 10px;background: #9E9E9E;">+</span>';
			
			$fields.='<input type="number" step="1" min="0" max="1000" placeholder="0" name="'.$taxonomy_name.'-subdomain-amount" id="'.$taxonomy_name.'-subdomain-amount" style="width: 60px;" value="'.$subdomain_amount.'"/>';					
			
		$fields.='</div>';
		
		$fields.='<p class="description">The amount of additional subdomains for hosting purpose </p>';
		
		return $fields;
	}
	
	public function save_layer_fields($term){
		
		if( isset($_POST[$term->taxonomy .'-domain-amount']) && is_numeric($_POST[$term->taxonomy .'-domain-amount']) ){
			
			$this->parent->layer->update_plan_amount($term->term_id,'domain',round(intval(sanitize_text_field($_POST[$term->taxonomy . '-domain-amount'])),1));			
		}			
		
		if( isset($_POST[$term->taxonomy .'-subdomain-amount']) && is_numeric($_POST[$term->taxonomy .'-subdomain-amount']) ){
			
			$this->parent->layer->update_plan_amount($term->term_id,'subdomain',round(intval(sanitize_text_field($_POST[$term->taxonomy . '-subdomain-amount'])),1));			
		}		
	}
	
	public function add_layer_columns(){
		
		$this->parent->layer->columns['domains'] = 'Domains';
	}
	
	public function add_layer_column_content($column_name, $term){
		
		if( $column_name === 'domains') {
			
			// display domain amount
			
			if(!$domain_amount = $this->parent->layer->get_plan_amount($term->term_id,'domain')){
				
				$domain_amount = 0;
			}
			
			if( $domain_amount == 1 ){
				
				$this->parent->layer->column .= '+' . $domain_amount . ' domain' . '</br>';
			}
			elseif( $domain_amount == 0 ){
				
				$this->parent->layer->column .= $domain_amount . ' domains' . '</br>';
			}
			else{
				
				$this->parent->layer->column .= '+' . $domain_amount . ' domains' . '</br>';
			}
			
			// display subdomain amount
			
			if(!$subdomain_amount = $this->parent->layer->get_plan_amount($term->term_id,'subdomain')){
				
				$subdomain_amount = 0;
			}			
			
			if( $subdomain_amount == 1 ){
				
				$this->parent->layer->column .= '+' . $subdomain_amount . ' subdomain' . '</br>';
			}
			elseif( $subdomain_amount == 0 ){
				
				$this->parent->layer->column .= $subdomain_amount . ' subdomains' . '</br>';
			}
			else{
				
				$this->parent->layer->column .= '+' . $subdomain_amount . ' subdomains' . '</br>';
			}
		}
	}
	
	public function add_api_layer_plan_option ($term){
		
		$this->parent->admin->html .= '<td>';
		
			$this->parent->admin->html .= '<span style="display:block;padding:1px 0 3px 0;margin:0;">';
				
				if($term->options['domain_amount']==1){
					
					$this->parent->admin->html .= '+'.$term->options['domain_amount'].' dom';
				}
				elseif($term->options['domain_amount']>0){
					
					$this->parent->admin->html .= '+'.$term->options['domain_amount'].' doms';
				}	
				else{
					
					$this->parent->admin->html .= $term->options['domain_amount'].' doms';
				}
				
				$this->parent->admin->html .= ' / ';

				if($term->options['subdomain_amount']==1){
					
					$this->parent->admin->html .= '+'.$term->options['subdomain_amount'].' sub';
				}
				elseif($term->options['subdomain_amount']>0){
					
					$this->parent->admin->html .= '+'.$term->options['subdomain_amount'].' subs';
				}	
				else{
					
					$this->parent->admin->html .= $term->options['subdomain_amount'].' subs';
				}						
		
			$this->parent->admin->html .= '</span>';
		
		$this->parent->admin->html .= '</td>';
	}	
	
	public function sum_domain_amount( &$total_domain_amount=0, $options){
		
		if( isset($options['domain_amount']) ){
			
			$total_domain_amount = $total_domain_amount + $options['domain_amount'];
		}
		
		return $total_domain_amount;
	}
	
	public function sum_subdomain_amount( &$total_subdomain_amount=0, $options){
		
		if( isset($options['subdomain_amount']) ){
		
			$total_subdomain_amount = $total_subdomain_amount + $options['subdomain_amount'];
		}
		
		return $total_subdomain_amount;
	}
	
	public function add_api_layer_plan_option_total($taxonomies,$plan_options){

		$total_domain_amount 	= 0;
		$total_subdomain_amount = 0;	
	
		foreach ( $taxonomies as $taxonomy => $terms ) {
	
			foreach($terms as $term){

				if ( in_array( $term->slug, $plan_options ) ) {
					
					$total_domain_amount 	= $this->sum_domain_amount( $total_domain_amount, $term->options);
					$total_subdomain_amount = $this->sum_subdomain_amount( $total_subdomain_amount, $term->options);
				}
			}
		}
		
		$this->parent->admin->html .= '<td style="width:150px;">';
		
			if($total_domain_amount==1){
				
				$this->parent->admin->html .= '+'.$total_domain_amount.' domain';
			}
			elseif($total_domain_amount>0){
				
				$this->parent->admin->html .= '+'.$total_domain_amount.' domains';
			}									
			else{
				
				$this->parent->admin->html .= $total_domain_amount.' domains';
			}
			
			$this->parent->admin->html .= '<br/>';
			
			if($total_subdomain_amount==1){
				
				$this->parent->admin->html .= '+'.$total_subdomain_amount.' subdomain';
			}
			elseif($total_subdomain_amount>0){
				
				$this->parent->admin->html .= '+'.$total_subdomain_amount.' subdomains';
			}									
			else{
				
				$this->parent->admin->html .= $total_subdomain_amount.' subdomains';
			}			
		
		$this->parent->admin->html .= '</td>';		
	}
	
	public function get_subscription_plan_info($plan,$options){
		
		$plan['info']['total_domain_amount'] 	= $this->sum_domain_amount( $plan['info']['total_domain_amount'], $options );
		$plan['info']['total_subdomain_amount'] = $this->sum_subdomain_amount( $plan['info']['total_subdomain_amount'], $options );
		
		return $plan;
	}
	
	public function add_plan_table_attributes($table,$plan){
		
		$total_domain_amount 	= isset( $plan['info']['total_domain_amount'] ) ? $plan['info']['total_domain_amount'] : 0;
		$total_subdomain_amount = isset( $plan['info']['total_subdomain_amount'] ) ? $plan['info']['total_subdomain_amount'] : 0;
		
		if( $total_domain_amount > 0 || $total_subdomain_amount > 0 ){
			
			$md5 = md5(rand());
			
			$table .='<a data-toggle="collapse" data-target="#section_'.$md5.'" class="plan_section">Domain Name <i class="glyphicon glyphicon-chevron-down pull-right"></i></a>';
			
			$table .= '<div id="section_'.$md5.'" class="panel-collapse collapse">';
				
				$table .= '<table id="plan_domains" class="able-striped">';

					if( $total_domain_amount > 0 ){
					
						$table .= '<tr>';
					
							$table .= '<td>';	
							
								$table .= 'Connected domains';
							
							$table .= '</td>';

							$table .= '<td>';							
					
								$table .= '<span class="badge">+'.$total_domain_amount.'</span> connected domain' . ( $total_domain_amount > 1 ? 's' : '');
								
							$table .= '</td>';	
						
						$table .= '</tr>';
					}
					
					if( $total_subdomain_amount > 0 ){
					
						$table .= '<tr>';
					
							$table .= '<td>';	
							
								$table .= 'Subdomains';
							
							$table .= '</td>';

							$table .= '<td>';							
					
								$table .= '<span class="badge">+'.$total_subdomain_amount.'</span> subdomain' . ( $total_subdomain_amount > 1 ? 's' : '');
								
							$table .= '</td>';	
						
						$table .= '</tr>';
					}

				$table .= '</table>';
				
			$table .= '</div>';
		}
		
		return $table;
	}
		
	public function handle_subscription_plan(){
				
		
	}
	
	public function add_user_plan_option_total( $user_id, $options ){
		
		$this->parent->plan->user_plans[$user_id]['info']['total_domain_amount'] 	= $this->sum_domain_amount( $this->parent->plan->user_plans[$user_id]['info']['total_domain_amount'], $options);
		$this->parent->plan->user_plans[$user_id]['info']['total_subdomain_amount'] = $this->sum_subdomain_amount( $this->parent->plan->user_plans[$user_id]['info']['total_subdomain_amount'], $options);
	}
	
	public function add_user_plan_info( $user_id ){
		

	}
	
	public function add_star_triggers(){

		$this->parent->stars->triggers['domains & urls']['ltple_subdomain_reserved'] = array(
				
			'description' => 'when you reserve a subdomain'
		);	

		return true;
	}
	
	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new LTPLE_Client_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new LTPLE_Client_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		
		//wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-frontend' );
	
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		//wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		//wp_enqueue_script( $this->_token . '-frontend' );
	
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		
		//wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-admin' );
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		
		load_plugin_textdomain( $this->settings->plugin->slug, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
		
	    $domain = $this->settings->plugin->slug;

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()
	
	public function filter_sitemaps_post_types($post_types){
		
		if( defined('REW_PRIMARY_SITE') && REW_PRIMARY_SITE != WP_HOME ){
			
			foreach( $post_types as $slug => $post_type ){
				
				if( strpos($slug,'user-') !== 0 ){
					
					unset($post_types[$slug]);
				}
			}
		}
		else{
			
			foreach( $post_types as $slug => $post_type ){
				
				if( strpos($slug,'user-') === 0 ){
					
					unset($post_types[$slug]);
				}
			}			
		}
		
		return $post_types;
	}

	public function filter_sitemaps_taxonomies($taxonomies){
		
		if( defined('REW_PRIMARY_SITE') && REW_PRIMARY_SITE != WP_HOME ){
			
			return array();
		}
		
		return $taxonomies;
	}
	
	public function filter_sitemaps_posts_query_args($args){
		
		if( defined('REW_PRIMARY_SITE') && REW_PRIMARY_SITE != WP_HOME ){
			
			if( $domain = $this->get_domain(REW_SITE) ){
				
				$args['author'] = $domain->post_author;
			}
		}
		
		return $args;
	}
	
	public function filter_sitemaps_users_query_args($args){
		
		if( defined('REW_PRIMARY_SITE') && REW_PRIMARY_SITE != WP_HOME ){
			
			$args['has_published_posts'] = array('fake_type'); // return empty posts
		}
		
		return $args;
	}
	
	public function filter_sitemaps_posts_entry($entry, $post, $post_type){
		
		if( isset($entry['loc']) ){
		
			$host = parse_url($entry['loc'],PHP_URL_HOST);
			
			if( $_SERVER['HTTP_HOST'] != $host ){
				
				// TODO replace url by similar content or search page results
				
				$entry['loc'] = WP_HOME . '#' . $post->ID;
			}
		}
		
		return $entry;
	}
	
	public function filter_feed_query( $query ) {

		if ( is_feed() && $query->is_main_query() ){
			
			// add custom post types to main feed
			
			if( defined('REW_PRIMARY_SITE') && REW_PRIMARY_SITE != WP_HOME ){
				
				if( $domain = $this->get_domain(REW_SITE) ){
					
					// filter author
				
					$query->set( 'author', $domain->post_author );
					
					// filter post types
					
					$query->set( 'post_type', apply_filters('ltple_domain_feed_post_types',array('user-page'),$domain) );
				}
			}
		}
		
		return $query;
		
	}

	/**
	 * Main LTPLE_Domains Instance
	 *
	 * Ensures only one instance of LTPLE_Domains is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see LTPLE_Domains()
	 * @return Main LTPLE_Domains instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
