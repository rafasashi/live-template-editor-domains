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
	var $private_domains	= null;
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
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		
		$this->settings = new LTPLE_Domains_Settings( $this->parent );
		
		$this->admin = new LTPLE_Domains_Admin_API( $this );

		if ( !is_admin() ) {

			// Load API for generic admin functions
			
			add_action('wp_head', array( $this, 'header') );
			add_action('wp_footer', array( $this, 'footer') );
		}
		
		// Handle localisation
		
		$this->load_plugin_textdomain();
		
		add_action('init', array( $this, 'load_localisation' ), 0 );

		//init addon 
		
		add_action('wp_loaded', array( $this, 'init_domain' ));

		add_filter('ltple_preview_profile_tab', array($this,'filter_preview_profile_tab'),10,2);

		add_filter('ltple_preview_post_url', array($this,'filter_preview_user_profile_link'),99999,2 );

		add_filter('ltple_profile_redirect', array( $this, 'redirect_profile' ),99999);
		
		add_filter('ltple_profile_disclaimer', array( $this, 'set_user_disclaimer' ),0);
		
		add_filter('ltple_user-page_template_path',function($path){
			
			return $this->parent->views . '/layer.php';
			
		},99999,1);
		
		// site name
		
		add_filter('ltple_site_name',array($this,'filter_site_name'),99999,1);
		
		// page title
		
		add_filter('ltple_header_title', function($title){
			
			if( !empty($this->post->post_title) ){
				
				$title = $this->post->post_title;
			}
			
			return $title;
			
		},9999999,1);

		//Add Custom API Endpoints
		
		add_action('rest_api_init', function(){
			
			register_rest_route( 'ltple-list/v1', '/user-profile/', array(
				
				'methods' 	=> 'GET',
				'callback' 	=> array($this,'get_panel_rows'),
				'permission_callback' => '__return_true',
			));
		});
		
		// social icons in profile
		
		add_action('ltple_before_social_icons', array( $this, 'get_social_icons'));		
		
		// Custom template path
		
		add_filter('template_include', array( $this, 'template_path'), 1 );	
			
		// add user attributes
		
		add_filter('ltple_user_loaded', array( $this, 'add_user_attribute'));	
		
		// add panel shortocode

		add_shortcode('ltple-client-domains', array( $this , 'get_panel_shortcode' ) );	

		// add panel url
		
		add_filter('ltple_urls', array( $this, 'get_panel_url'));	
		
		// add link to theme menu
		
		//add_filter('ltple_view_my_profile', array( $this, 'add_theme_menu_link'),9);	
		
		add_filter('ltple_collect_user_information', array( $this, 'collect_user_information'));	
		
		add_filter('ltple_profile_completeness', array( $this, 'get_profile_completeness'),1,3);	
				
		// add fields

		add_filter('ltple_account_options', array( $this, 'add_account_options'),10,2);
		add_filter('ltple_account_plan_fields', array( $this, 'add_layer_plan_fields'),10,2);
		add_action('ltple_save_layer_fields', array( $this, 'save_layer_fields' ),10,1);			
		
		// add layer colums

		add_filter('ltple_layer_usage_column', array( $this, 'filter_layer_column_content'),10,3);
		
		// handle plan
		
		add_filter('ltple_subscription_plan_info', array( $this, 'get_subscription_plan_info'),10,2);	
		
		add_filter('ltple_api_layer_plan_option', array( $this, 'filter_api_layer_plan_option'),10,2);	
		add_filter('ltple_api_layer_plan_option_total', array( $this, 'filter_api_layer_plan_option_total'),10,2);
		
		add_filter('ltple_plan_table_services', array( $this, 'add_plan_table_attributes'),10,2);
		add_filter('ltple_plan_subscribed', array( $this, 'handle_subscription_plan'),10);
		
		add_filter('ltple_user_plan_option_total', array( $this, 'add_user_plan_option_total'),10,2);
		add_filter('ltple_user_plan_info', array( $this, 'add_user_plan_info'),10,1);
		
		add_filter('ltple_dashboard_manage_sidebar', array( $this, 'get_sidebar_content' ),2,3);

		add_action('ltple_edit_layer_title', array( $this, 'add_layer_url_input'));
		
		add_action('ltple_profile_theme_id', array( $this, 'filter_profile_theme_id'),10,2);
		
		add_action('ltple_user_theme_id', array( $this, 'filter_user_theme_id'),10,2);
		
		add_action('ltple_current_theme_id', array( $this, 'get_current_theme_id'),10,2);
		
		$this->add_star_triggers();
		
		// addon post types
		
		$this->parent->register_post_type('user-domain', __( 'User domains', 'live-template-editor-client' ), __( 'User domains', 'live-template-editor-client' ), '', array(

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
		
		$this->parent->register_post_type('user-theme', __( 'Themes', 'live-template-editor-profile' ), __( 'Theme', 'live-template-editor-profile' ), '', array(
			
			'public' 				=> true,
			'publicly_queryable' 	=> true,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> false,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'map_meta_cap'			=> true,
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title','author'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> 'dashicons-admin-post',
		));
		
		$this->parent->register_post_type('user-profile', __( 'Home Pages', 'live-template-editor-domains' ), __( 'Home Page', 'live-template-editor-domains' ), '', array(

			'public' 				=> true,
			'publicly_queryable' 	=> true,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'map_meta_cap'			=> true,
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title','author'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> 'dashicons-admin-post',
		));
		
		$this->parent->register_post_type('user-page', __( 'Static Pages', 'live-template-editor-client' ), __( 'Static Page', 'live-template-editor-client' ), '', array(

			'public' 				=> true,
			'publicly_queryable' 	=> true,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
			'show_in_nav_menus' 	=> false,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'map_meta_cap'			=> true,
			'has_archive' 			=> true,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> false,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail' ),
			'supports' 				=> array('title','author'),
			'menu_position' 		=> 5,
			'menu_icon' 			=> 'dashicons-admin-post',
		)); 

		add_filter('manage_user-page_posts_columns', array( $this->parent->layer, 'set_user_layer_columns'),99999);
		add_action('manage_user-page_posts_custom_column', array( $this->parent->layer, 'add_layer_type_column_content'), 10, 2);		
		
		add_filter('ltple_layer_storages',function($storages){ 
			
			$storages['user-theme'] 	= 'Theme';
			$storages['user-profile'] 	= 'Home Page';
			$storages['user-page'] 		= 'Static Page';
			
			return $storages;
		});	
		
		add_filter('ltple_dashboard_manage_sidebar',function($section,$currentTab,$output){

			$section .= '<li><a href="'.$this->parent->urls->domains . '"><span class="fa fa-globe"></span> Website</a></li>';
	
			return $section;
			
		},10,3);
		
		add_filter('ltple_profile_settings_sidebar',function($sidebar,$currentTab,$storage_count){ 
				
			// website settings

			$section = apply_filters('ltple_website_settings_sidebar','',$currentTab,$storage_count);
					
			if( !empty($section) ){

				$sidebar .= '<li class="gallery_type_title">Webite</li>';
				
				$sidebar .= $section;
			}
			
			return $sidebar;
			
		},9999,3);
		
		add_filter('ltple_website_settings_sidebar',function($section,$currentTab,$storage_count){ 
			
			$ltple = LTPLE_Client::instance();
			
			$section .= '<li'.( ($currentTab == 'default' || $currentTab == 'urls') ? ' class="active"' : '' ).'><a href="'.$this->parent->urls->domains . '"><span class="fas fa-sign"></span> Domain Name</a></li>';

			$section .='<li'.( $currentTab == 'home-page' ? ' class="active"' : '' ).'>';
				
				if( $layer = $this->get_user_profile($ltple->user->ID) ){

					$section .='<a href="'.$layer->urls['edit'].'">'.PHP_EOL;
						
						$section .='<span class="fa fa-house-user"></span> Home Page'.PHP_EOL;
					
					$section .='</a>'.PHP_EOL;
				}
				else{
					
					$gallery_url = add_query_arg( array(
					
						'output' 	=> 'widget',
						
					),$ltple->urls->gallery . '?layer[default_storage]=user-profile');
					
					$modal_id='modal_'.md5($gallery_url);

						$section .='<a href="#" data-toggle="modal" data-target="#'.$modal_id.'">'.PHP_EOL;
							
							$section .='<span class="fa fa-house-user"></span> Home Page'.PHP_EOL;
						
						$section .='</a>'.PHP_EOL;

					$section .='<div class="modal fade" id="'.$modal_id.'" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">'.PHP_EOL;
						
						$section .='<div class="modal-dialog modal-full" role="document">'.PHP_EOL;
							
							$section .='<div class="modal-content">'.PHP_EOL;
							
								$section .='<div class="modal-header">'.PHP_EOL;
									
									$section .='<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'.PHP_EOL;
									
									$section .='<h4 class="modal-title text-left" id="myModalLabel">New Project</h4>'.PHP_EOL;
								
								$section .='</div>'.PHP_EOL;
							  
								$section .= '<iframe data-src="'.$gallery_url.'" style="display:block;position:relative;width:100%;top:0;bottom: 0;border:0;height:calc( 100vh - 50px );"></iframe>';
							  
							$section .='</div>'.PHP_EOL;
							
						$section .='</div>'.PHP_EOL;
						
					$section .='</div>'.PHP_EOL;
				}

			$section .='</li>';	
			
			return $section;
			
		},0,3);
		
		add_filter('ltple_website_settings_sidebar',function($sidebar,$currentTab,$storage_count){ 
		
			if( !empty($storage_count['user-page']) ){
			
				$sidebar .=  '<li'.( $currentTab == 'user-page' ? ' class="active"' : '' ).'><a href="'.$this->parent->urls->profile . '?list=user-page"><span class="fas fa-file-alt"></span> Static Pages</a></li>';
			}
		
			return $sidebar;
		
		},99,3);

		add_filter('ltple_editor_preview_url', array( $this,'get_editor_preview_url'),1,2);
		
		// sitemaps
		
		add_filter('wp_sitemaps_post_types',array($this,'filter_sitemaps_post_types'),99999,1);
		
		add_filter('wp_sitemaps_taxonomies',array($this,'filter_sitemaps_taxonomies'),99999,1);
		
		add_filter('wp_sitemaps_posts_query_args',array($this,'filter_sitemaps_posts_query_args'),99999,1);
		
		add_filter('wp_sitemaps_users_query_args',array($this,'filter_sitemaps_users_query_args'),99999,1);
		
		add_filter('wp_sitemaps_posts_entry',array($this,'filter_sitemaps_posts_entry'),0,3);
		
		// feeds
		
		add_filter('pre_get_posts',array($this,'filter_feed_query'),99999999,1);

		add_filter('ltple_theme_template',function($template,$tab){
			
			if( $theme = $this->parent->profile->get_current_theme() ){
				
				if( !empty($theme->html) ){
					
					$template = $theme->html;
				}
				elseif( $theme->post_type != 'cb-default-layer' ){
					
					if( $defaultId = $this->parent->layer->get_default_id($theme->ID) ){
						
						if( $default_content = get_post_meta($defaultId,'layerContent',true) ){
							
							$template = $default_content;
						}
					}
				}
			}
			
			if( $layer = LTPLE_Editor::instance()->get_layer() ){
				
				$layer_type = $this->parent->layer->get_layer_type($layer);
				
				if( $layer_type->storage == 'user-theme' ){
					
					$template = apply_filters('wp_filter_content_tags',$template);
				}
			}
			
			return $template;
			
		},10,2);
		
		add_filter('ltple_layer_style',function($style,$layer){
			
			if( empty($style) && $layer->post_type == 'user-theme' ){
					
				if( $defaultId = $this->parent->layer->get_default_id($layer->ID) ){
					
					$style = get_post_meta($defaultId,'layerContent',true);
				}
			}
			
			return $style;
			
		},10,2);
		
		add_filter('ltple_preview_profile_tab', function($tab,$layer_type){
			
			if( in_array($layer_type->storage,array(
				
				'user-profile',
				'user-theme',
			
			))){
				
				return 'home';
			}
			
			return $tab;
			
		},10,2);
		
		add_filter('ltple_default_layer_css_variables_form_data', function($data,$layer_type){
			
			if( $layer_type->storage == 'user-theme' ){
				
				$data = $this->get_default_variables_form_data();
			}
			
			return $data;
			
		},10,2);
		
		add_filter('ltple_profile_id', function($user_id){
			
			if( empty($user_id) ){
				
				if( $domain = $this->get_current_domain() ){
					
					$user_id = intval($domain->post_author);
				}
				else{
					
					global $post;
					
					if( !empty($post) && $post->post_type == 'user-theme' ){
						
						$user_id = intval($post->post_author);
					
						add_filter('ltple_profile_tab', function($tab){
							
							return 'home';
						});
					}
				}
			}
			
			return $user_id;
			
		},9999999,1);

		add_filter('ltple_document_classes',function($classes,$layer_id=null){
			
			if( $layer = LTPLE_Editor::instance()->get_layer($layer_id) ){
				
				if( $layer->post_type == 'user-theme' ){
				
					$ltple = LTPLE_Client::instance();
			
					$classes .= ( !empty($classes) ? ' ' : '' ) . $ltple->layer->get_layer_classes($layer->ID);
				}
			}
			
			return $classes;
			
		},10,2);
		
		add_filter('ltple_default_layer_fields',function($layer_type){
			
			if( $layer_type->storage == 'user-theme' ){
				
				$this->parent->layer->defaultFields[]=array( 
				
					'metabox' => array(
					
						'name' 		=> 'demo-theme-content',
						'title' 	=> __( 'Demo Layer ID', 'live-template-editor-client' ), 
						'screen'	=> array('cb-default-layer'),
						'context' 	=> 'side',
						'frontend' 	=> false,
					),	
					'id'			=> 'demoLayerId',
					'type'			=> 'number',
					'description'	=> ''
				);	
			}
		});

		add_filter('ltple_user_profile_html',array($this,'get_user_profile_html'),10,2);
		
		add_filter('ltple_user_profile_css',array($this,'get_user_profile_css'),10,2);
		
		add_filter('ltple_post',function($post){
			
			$ltple = LTPLE_Client::instance();
			
			if( $ltple->profile->tab == 'home' ){
				
				if( $profile = $this->get_user_profile($ltple->profile->id) ){
					
					if( $profile->post_status == 'publish' ){
					
						$post = $profile;
					}
				}
			}
			
			return $post;
			
		},9999,1);
		
		add_filter('ltple_user_layer_fields', function ($post=null,$metabox){

			if( !empty($post) ){
				
				if( $post->post_type == 'user-theme' ){
					
					// theme variables
					
					$ltple = LTPLE_Client::instance();
					
					if( $defaultId = $ltple->layer->get_default_id($post->ID) ){
						
						if( $data = $this->get_variables_form_data($defaultId) ){
							
							$values = get_post_meta($post->ID,'themeCssVars',true);
							
							$metabox = array( 
					
								'name' 		=> 'themeCssVars',
								'title' 	=> 'Settings',
								'screen'	=> array($post->post_type),
								'context' 	=> 'advanced',
								'frontend'	=> true,
							);
							
							foreach( $data['name'] as $e => $name) {
								
								$name = str_replace('-','_',sanitize_title($name));
								
								$input = !empty($data['input'][$e]) ? $data['input'][$e] : false;
								
								if( !empty($name) && !empty($input) ){
									
									// get field id
									
									$field_id = str_replace(array('-',' '),'_',$name);
									
									// get required
											
									$required = ( ( empty($data['required'][$e]) || $data['required'][$e] == 'required' ) ? true : false );
									
									// get label
									
									$label = isset($data['label'][$e]) ? $data['label'][$e] : ucfirst(str_replace('_',' ',$name));
									
									// get value
									
									$value = isset($values[$field_id]) ? $values[$field_id] : $data['value'][$e];
									
									// set input
									
									$ltple->layer->userFields[] = array(
										
										'metabox' 		=> $metabox,
										'type'			=> $input,
										'id'			=> 'theme_' . $field_id,
										'name'			=> 'themeCssVars[' . $field_id . ']',
										'label'			=> $label,
										'data'			=> $value,
										'required' 		=> $required,
										'placeholder' 	=> '',
										'description'	=> '',
										'class'			=> 'clearfix col-xs-12 col-sm-6 col-md-4',
									);
								}
							}
						}
					}
				}
			}
			
			return $post;
			
		},10,2);

		add_action( 'add_meta_boxes', function(){
			
			$this->admin->add_meta_box (
			
				'theme_settings',
				__( 'Theme Settings', 'live-template-editor-profile' ), 
				array('user-domain'),
				'advanced'
			);
		});
		
		add_filter('user-domain_custom_fields',function($fields){

			$fields[]=array(
			
				'metabox' =>
				
					array('name'=>'theme_settings'),
					'id'			=> 'themeId',
					'label'			=> 'Theme ID',
					'placeholder'	=> '',
					'type'			=> 'number',
					'description'	=> '',
			);
			
			return $fields;
			
		},10,1);
		
		//Add Custom API Endpoints
		
		add_action('rest_api_init', function(){
						
			register_rest_route( 'ltple-list/v1', '/user-profile/', array(
				
				'methods' 	=> 'GET',
				'callback' 	=> array($this,'get_user_profile_rows'),
				'permission_callback' => '__return_true',
			));
			
			register_rest_route( 'ltple-list/v1', '/user-page/', array(
				
				'methods' 	=> 'GET',
				'callback' 	=> array($this,'get_user_page_rows'),
				'permission_callback' => '__return_true',
			));

		});
		
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
		
		$url = remove_query_arg('pr',$url);
		
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
	
	public function get_private_domains(){
		 
		if( is_null($this->private_domains) ){
		
			$this->private_domains = array();
		
			$domains = explode(PHP_EOL,get_option( $this->parent->_base . 'private_domains', '' ));
			
			if(!empty($domains)){
				
				foreach( $domains as $domain ){
					
					$domain = trim($domain);
					
					if( !empty($domain) ){
						
						$this->private_domains[] = $domain;
					}
				}
			}
		}
		
		return $this->private_domains;
	}
	
	public function get_domain_type( $domain ){
		
		$domain_type = 'domain';
		
		// get shared domains
		
		$default_domains = array_merge($this->get_default_domains(),$this->get_private_domains());
		
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

		$domain_urls = array();
		
		$urls = get_post_meta($domain_id ,'domainUrls', true);
		
		if( !empty($urls) && is_array($urls) ){
			
			foreach( $urls as $id => $path ){
				
				if( !empty($path) ){
					
					$domain_urls[$id] = trailingslashit($path); 
				}
			}
		}
	
		return $domain_urls;
	}
	
	public function get_primary_domain( $user = null, $return = 'url' ){
		
		if( $return == 'object' ){
			
			$primary_domain = false;
		}
		else{
			
			$primary_domain = $this->parent->urls->home;
		}
		
		if( $list = $this->get_user_domain_list( $user )){
			
			foreach( $list as $domain_type => $domains ){
				
				foreach( $domains as $domain ){
					
					if( $domain->is_primary ){
						
						if( $return == 'object' ){
							
							return $domain;
						}
						else{
								
							$primary_domain = apply_filters('rew_server_url',$this->parent->request->proto . $domain->post_title);
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
		
		if( !empty($domain_name) ){
			
			if( $domains = get_posts(array(
			
				'post_type' => 'user-domain',
				'title' 	=> $domain_name,
			
			))){
				
				foreach( $domains as $domain ){
					
					if( $domain->post_title == $domain_name ){
						
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
						
						return $domain;
					}
				}
			}
		}
		
		return false;
	}
	
	public function get_current_domain(){
		
		if( is_null($this->currentDomain) ){

			$domain_name = false;
			
			if( !empty($_GET['preview']) && $_GET['preview'] == 'ltple' && !empty($_GET['domain']) ){
				
				$domain_name = sanitize_text_field($_GET['domain']);
			}
			elseif( defined('REW_LTPLE') && in_array(REW_LTPLE,['domain','subdomain']) ){

				$domain_name = defined('REW_SITE') ? REW_SITE : $_SERVER['HTTP_HOST'];
			}
			
			if( $domain = $this->get_domain($domain_name) ){ 
			
				$domain->urls = $this->get_domain_urls($domain->ID);
			}
			
			$this->currentDomain = $domain;
		}
		
		return $this->currentDomain;
	}
	
	public function get_user_site_storages(){
		
		return array(
			
			'home' 	=> 'user-profile',
			'posts' => 'user-post',
			'store' => 'user-product',
		);
	}
	
	public function init_domain(){
		
		// storages
			
		if( $storages = $this->get_user_site_storages() ){
			
			foreach( $storages as $storage ){
				
				register_taxonomy_for_object_type('layer-type',$storage);
				
				add_action('ltple_'.$storage.'_link', array( $this, 'filter_user_profile_link'),10,2);	
				
				add_filter('ltple_'.$storage.'_layer_area', array( $this, 'filter_user_profile_area'),10);
			}
		}
		
		// pages
		
		register_taxonomy_for_object_type('layer-type','user-page');
			
		add_action('ltple_user-page_link', array( $this, 'filter_user_page_link'),10,2);	
		
		// get domain	
		
		if( $domain = $this->get_current_domain() ){
			
			// domain owner
			
			$user_id = intval($domain->post_author);
			
			// request uri

			$request_uri = false;
			
			if( !empty($_GET['preview']) && $_GET['preview'] == 'ltple' ){
				
				$prefix = '/' . $this->parent->profile->slug . '/' . $user_id;
				
				if( strpos($_SERVER['REQUEST_URI'],$prefix) === 0 ){
				
					$request_uri = str_replace( $prefix, '' , $_SERVER['REQUEST_URI'] );
				}
			}
			else{
				
				$request_uri = $_SERVER['REQUEST_URI'];
			}
			
			if( is_string($request_uri) ){
				
				list($this->uri) = explode('?', $request_uri);
				
				if( !empty($domain->urls) ){

					// get path
					
					foreach( $domain->urls as $layerId => $path ){
						
						if( '/' . $path == $this->uri ){
							
							if( $layer = get_post($layerId) ){

								// check license
								
								if( $this->parent->users->get_user_remaining_days($user_id) > 0 ) {
									
									$this->set_user_layer($layer);
								}
								elseif( !empty($this->parent->user->ID) && intval($user_id) == $this->parent->user->ID ){
									
									echo 'License expired, please renew your subscription.';
								}
								else{
									
									include($this->parent->views . '/profile/card.php');
								}

								exit;
							}
						}
					}
					
					if( $domain->is_primary === true ){
						
						$this->set_user_profile();
					}
					else{
						
						include($this->parent->views . '/profile/card.php');
					}
				}
				elseif( $domain->is_primary === true ){
					
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
		elseif( !defined('REW_LTPLE') || REW_LTPLE != 'client' ){
			
			echo 'This domain is not registered yet...';
			exit;
		}
	}
	
	public function set_user_disclaimer(){
		
		//check disclaimer
		
		if( !$this->parent->user->loggedin ){
			
			if( $domain = $this->get_current_domain() ){
				
				if( empty($_COOKIE['_ltple_disclaimer']) && !$this->parent->inWidget ){
					
					$domainType = $this->get_domain_type( $domain->post_title );
			
					if( $domainType == 'subdomain' ){
					
						$this->disclaimer = get_option($this->parent->_base  . 'subdomain_disclamer');
					}
					
					if( !empty($this->disclaimer) ){
						
						include( $this->views . '/disclaimer.php' );
					}
				}
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
			
			if( $domain = $this->get_current_domain() ){
			
				$url = $this->parent->request->proto . $domain->post_title;
			}
			
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

	public function get_default_variables_form_data(){
		
		$ltple = LTPLE_Client::instance();
		
		return array(
		
			'input' => array(
			
				'color',
				'color',
				'color',
			),
			'label' => array(
			
				'Main Color',
				'Navbar Color',
				'Link Color',
			),
			'name' => array(
			
				'main_color',
				'navbar_color',
				'link_color',
			),
			'required' => array(
			
				'required',
				'required',
				'required',
			),
			'value' => array(
			
				$ltple->settings->mainColor,
				$ltple->settings->navbarColor,
				$ltple->settings->linkColor,
			),				
		);
	}
	
	public function get_variables_form_data($id){
		
		if( !isset($this->vars[$id]) ){
			
			if( !$data = get_post_meta($id,'layerCssVars',true) ){
				
				$data = $this->get_default_variables_form_data();
			}
			
			$this->vars[$id] = $data;
		}
		
		return $this->vars[$id];
	}
	
	public function get_social_icons($content){
		
		if( $this->parent->profile->id > 0 ){
		
			$user_domains = $this->get_user_domain_list( $this->parent->profile->id );
			
			if( !empty($user_domains['subdomain']) ){
				
				foreach( $user_domains['subdomain'] as $domain ){
					
					if( !empty($domain->post_title) ){
							
						$content .= '<a href="' . $this->parent->request->proto . $domain->post_title . '" style="margin:5px;display:inline-block;" ref="dofollow">';
							
							$content .= '<img src="' . $this->parent->settings->options->social_icon . '" style="height:30px;width:30px;border-radius:250px;" />';
							
						$content .= '</a>';
					}
				}
			}
		}
		
		return $content;
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
		
		ob_start();
		
		if($this->parent->user->loggedin){

			if( !empty($_REQUEST['output']) && $_REQUEST['output'] == 'widget' ){
				
				include($this->views . '/widget.php');
			}
			else{
			
				include($this->parent->views . '/navbar.php');
				
				if($this->parent->user->loggedin){
					
					add_action('ltple_domains_sidebar',array($this->parent->profile,'get_sidebar'),10,3);
					
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

		return ob_get_clean();
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

		return $sidebar;
	}

	public function get_user_profile($user_id){
		
		if( $posts = get_posts( array(
			
			'post_type' 	=> 'user-profile',
			'post_status' 	=> array('publish','draft'),
			'author' 		=> $user_id,
		))){
			
			$layer = LTPLE_Editor::instance()->get_layer($posts[0]);

			if( empty($layer->html) ){
				
				if( $default_id = LTPLE_Client::instance()->layer->get_default_id($layer->ID) ){
					
					$layer->html 	= get_post_meta($default_id,'layerContent',true);
					$layer->css 	= get_post_meta($default_id,'layerCss',true);
					$layer->js 		= get_post_meta($default_id,'layerJs',true);
				}
			}
			
			return $layer;
		}	

		return false;
	}
	
	public function get_user_profile_html($html,$user_id){
		
		if( !$layer = $this->get_user_profile($user_id) ){
			
			$layer = LTPLE_Editor::instance()->get_layer();
		}
		
		if( $layer->post_type == 'user-theme' ){
			
			$default_id = $this->parent->layer->get_default_id($layer->ID);
			
			if( $demo_id = intval(get_post_meta($default_id,'demoLayerId',true)) ){
				
				if( $demo = LTPLE_Editor::instance()->get_layer($demo_id) ){
			
					$html = $demo->html;
				}
			}
			
			if( LTPLE_Editor::instance()->is_preview() ){
			
				$html = '<ltple-mod>' . $html . '</ltple-mod>';
			}
		}
		else{
		
			$layer_type = $this->parent->layer->get_layer_type($layer);
			
			if( $layer_type->storage == 'user-profile' && $layer->post_status == 'publish' ){
			
				$this->parent->layer->set_layer($layer,false);
			
				$html = apply_filters('wp_filter_content_tags',$layer->html,$layer);
			}
		}
		
		return $html;
	}
	
	public function get_user_profile_css($css,$user_id){
		
		if( $layer = $this->get_user_profile($user_id) ){
			
			if( $layer->post_status == 'publish' ){
			
				$css = $layer->css;
			}
		}
		
		return $css;
	}
	
	public function get_user_profile_js($js,$user_id){
		
		if( $layer = $this->get_user_profile($user_id) ){
			
			$js = $layer->js;
		}
		
		return $js;
	}
	
	public function filter_user_theme_link($post_link,$post){
	
		dump($post_link);
	
		return $post_link;
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
	
	public function filter_user_profile_area($area){
		
		return 'frontend';
	}
	
	public function filter_user_profile_link($post_link,$post){
		
		$storages = $this->get_user_site_storages();
		
		if( in_array($post->post_type,$storages)){
			
			if( $domain = $this->get_primary_domain($post->post_author) ){
				
				$url = parse_url($post_link);
				
				$slug = array_search($post->post_type,$storages);
				
				if( $slug != 'home' && !empty($url['path']) ){
					
					if( !empty($post->post_name) ){
						
						$post_name = $post->post_name;
					}
					else{
						
						$post_name = $this->parent->urls->assign_post_name($post);
					}
					
					$post_link = $domain . '/' . $slug . '/' . $post_name . '/';
				}
				else{
				
					$post_link = $domain;
				}
			}
		}
		
		return $post_link;
	}

	public function filter_preview_user_profile_link($url,$post){
		
		if( empty($url) ){
			
			if( is_numeric($post) ){
			
				$post = get_post($post);
			}
			
			$storages = $this->get_user_site_storages();
			
			if( in_array($post->post_type,$storages) ){
				
				$slug = array_search($post->post_type,$storages);
			
				if( !empty($post->post_name) ){
					
					$post_name = $post->post_name;
				}
				else{
					
					$post_name = $this->parent->urls->assign_post_name($post);
				}
				
				// has to be in the same domain as editor for CORS
				
				$url = apply_filters('ltple_profile_permalink',add_query_arg( array(
					
					'preview'	=> 'true',
					'_'			=> time(),
					
				),$this->parent->urls->profile . $post->post_author . '/' . $slug . '/' . $post_name . '/'),$post,$slug);
			}
			elseif( $post->post_type == 'user-theme' ){
				
				$url = add_query_arg( array(
					
					'p' 		=> $post->ID,
					'post_type' => $post->post_type,
					'preview' 	=> 'true',
					'_'			=> time(),
					
				),$this->parent->urls->home);
			}
			elseif( $post->post_status != 'publish' ){
				
				$url = add_query_arg( array(
					
					'p' 		=> $post->ID,
					'post_type' => $post->post_type,
					'preview' 	=> 'true',
					'_'			=> time(),
					
				),$this->parent->urls->home);
			}
		}
		
		return $url;
	}
	
	public function filter_preview_profile_tab($tab,$layer_type){
		
		$storage = $this->get_user_site_storages();
		
		if( in_array($layer_type->storage,$storage) ){
			
			return array_search($layer_type->storage,$storage);
		}
		
		return $tab;
	}

	public function add_layer_url_input(){
		
		if( $this->parent->user->layer->post_type == 'user-page' && $this->parent->layer->is_public($this->parent->user->layer) && $this->parent->layer->is_hosted($this->parent->user->layer) ){
			
			echo'<div id="layer_url" style="margin:15px 0 0 0;padding:15px 0 0 0;border-top:1px solid #eee;">';
				
				echo'<label style="margin:0 11px 0 7px;">URL</label>';
				
				if( $this->parent->layer->can_customize_url($this->parent->user->layer) ){
				
					echo'<select name="domainUrl[domainId]" class="form-control input-sm" style="width:auto;display:inline-block;">';
						
						$default_url = 'None / Draft';
						
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
					
					echo'<input type="text" name="domainUrl[domainPath]" value="'.$domainPath.'" placeholder="category/page-title" class="form-control input-sm" style="width:270px;display:inline-block;" required="required" />';
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

			if( $domain = $this->get_primary_domain($post->post_author) ){
		
				$permalink 	= str_replace($this->parent->profile->get_user_url($post->post_author),$domain,$permalink);
			}
		}
		
		return $permalink;
	}	

	public function filter_profile_theme_id($theme_id,$user_id){
		
		if( !empty($user_id) ){
			
			if( $domain = $this->get_current_domain() ){
				
				$theme_id = intval(get_post_meta($domain->ID,'themeId',true));
			}
			else{
				
				$theme_id = apply_filters('ltple_user_theme_id',$theme_id,$user_id);
			}
		}
		
		return $theme_id;
	}
	
	public function filter_user_theme_id($theme_id,$user_id){
		
		if( !empty($user_id) ){
			
			if( $domain = $this->get_primary_domain($user_id,'object') ){
				
				$theme_id = intval(get_post_meta($domain->ID,'themeId',true));
			}
		}
		
		return $theme_id;
	}
	
	public function get_current_theme_id($theme_id,$layer){
		
		if( $domain = $this->get_primary_domain($layer->post_author,'object') ){
			
			$theme_id = intval(get_post_meta($domain->ID,'themeId',true));
		}
		
		return $theme_id;
	}
	
	public function add_theme_menu_link(){

		// add theme menu link

		echo'<li style="position:relative;background:#182f42;">';
			
			echo '<a href="'. $this->parent->urls->domains . '"><span class="fas fa-sign" aria-hidden="true"></span> Connected Domains</a>';

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

	public function add_account_options($options,$term_id){
		
		if(!$domain_amount = $this->parent->layer->get_plan_amount($term_id,'domain')){
			
			$domain_amount = 0;
		}
		
		if(!$subdomain_amount = $this->parent->layer->get_plan_amount($term_id,'subdomain')){
			
			$subdomain_amount = 0;
		}

		$options['domain_amount'] 		= $domain_amount;
		$options['subdomain_amount'] 	= $subdomain_amount;
	
		return $options;
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
			
			$fields.='<input type="number" step="1" min="0" max="1000" placeholder="0" name="'.$taxonomy_name.'-domain-amount" id="'.$taxonomy_name.'-domain-amount" style="width:80px;" value="'.$domain_amount.'"/>';					
			
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
			
			$fields.='<input type="number" step="1" min="0" max="1000" placeholder="0" name="'.$taxonomy_name.'-subdomain-amount" id="'.$taxonomy_name.'-subdomain-amount" style="width:80px;" value="'.$subdomain_amount.'"/>';					
			
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

	public function filter_layer_column_content($content, $term){
		
		// display domain amount
		
		if(!$domain_amount = $this->parent->layer->get_plan_amount($term->term_id,'domain')){
			
			$domain_amount = 0;
		}
		
		if( $domain_amount == 1 ){
			
			$content .= '+' . $domain_amount . ' domain' . '</br>';
		}
		elseif( $domain_amount > 1 ){
			
			$content .= '+' . $domain_amount . ' domains' . '</br>';
		}
		
		// display subdomain amount
		
		if(!$subdomain_amount = $this->parent->layer->get_plan_amount($term->term_id,'subdomain')){
			
			$subdomain_amount = 0;
		}			
		
		if( $subdomain_amount == 1 ){
			
			$content .= '+' . $subdomain_amount . ' subdomain' . '</br>';
		}
		elseif( $subdomain_amount > 1 ){
			
			$content .= '+' . $subdomain_amount . ' subdomains' . '</br>';
		}

		return $content;
	}
	
	public function filter_api_layer_plan_option($html,$term){
		
		if( $term->options['domain_amount'] == 1 ){
			
			$html .= '+'.$term->options['domain_amount'].' domain' .'<br>';
		}
		elseif( $term->options['domain_amount'] > 1 ){
			
			$html .= '+'.$term->options['domain_amount'].' domains' .'<br>';
		}
		
		if( $term->options['subdomain_amount'] == 1 ){
			
			$html .= '+'.$term->options['subdomain_amount'].' subdomain' .'<br>';
		}
		elseif( $term->options['subdomain_amount'] > 1 ){
			
			$html .= '+'.$term->options['subdomain_amount'].' subdomains' .'<br>';
		}						

		return $html;
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
	
	public function filter_api_layer_plan_option_total($html,$user_plan){

		$total_domain_amount = !empty($user_plan['info']['total_domain_amount']) ? $user_plan['info']['total_domain_amount'] : 0;

		if( $total_domain_amount == 1 ){
			
			$html .= '+'.$total_domain_amount.' domain'.'<br>';
		}
		elseif( $total_domain_amount > 1 ){
			
			$html .= '+'.$total_domain_amount.' domains'.'<br>';
		}
		
		$total_subdomain_amount = !empty($user_plan['info']['total_subdomain_amount']) ? $user_plan['info']['total_subdomain_amount'] : 0;

		if( $total_subdomain_amount == 1 ){
			
			$html .= '+'.$total_subdomain_amount.' subdomain'.'<br>';
		}
		elseif( $total_subdomain_amount > 1 ){
			
			$html .= '+'.$total_subdomain_amount.' subdomains'.'<br>';
		}			

		return $html;		
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
			
			$table .='<a data-toggle="collapse" data-target="#section_'.$md5.'" class="plan_section">Domain Name <i class="fas fa-angle-down pull-right" style="font-size:25px;"></i></a>';
			
			$table .= '<div id="section_'.$md5.'" class="panel-collapse collapse">';
				
				$table .= '<table id="plan_domains" class="able-striped">';
					
					if( $total_domain_amount > 0 ){

						$table .= '<tr>';
					
							$table .= '<th style="width:80%;">';	
								
								$table .= 'Domains';
								
							$table .= '</th>';
							
							$table .= '<th style="width:20%;text-align:center;">';							
					
								$table .= 'Limit';
								
								//$table .= 'Usage'; // TODO pass usage in billing info
								
							$table .= '</th>';	
						
						$table .= '</tr>';
					
						$table .= '<tr>';
					
							$table .= '<td style="width:80%;">';	
								
								$table .= '<b>';
								
									$table .= 'Connected domains';
								
								$table .= '</b>';
								
							$table .= '</td>';
							
							$table .= '<td style="width:20%;text-align:center;background:#efefef;">';							
					
								$table .= '<span class="badge">'.$total_domain_amount.'</span>';
								
							$table .= '</td>';	
						
						$table .= '</tr>';
					}
					
					if( $total_subdomain_amount > 0 ){

						$table .= '<tr>';
					
							$table .= '<th style="width:80%;">';	
								
								$table .= '<div data-html="true" data-toggle="popover" data-placement="bottom" data-trigger="hover" data-title="Domains" data-content="Choose from the list of available domains, and replace the asterisk (*) with your desired subdomain name.">';
								
									$table .= 'Subdomains';
								
									$table .= ' <i class="fas fa-question-circle" style="font-size:13px;"></i>';
								
								$table .= '</div>';

							$table .= '</th>';
							
							$table .= '<th style="width:20%;text-align:center;">';							
					
								$table .= 'Limit';
								
								//$table .= 'Usage'; // TODO pass usage in billing info
								
							$table .= '</th>';	
						
						$table .= '</tr>';
						
						$table .= '<tr>';

							$table .= '<td style="width:80%;">';	
								
								if( $domains = $this->get_default_domains() ){
								
									foreach( $domains as $domain ){
										
										$table .= '*.' . $domain . '<br>';
									}
								}
							
							$table .= '</td>';
							
							$table .= '<td style="width:20%;text-align:center;background:#efefef;">';							
					
								$table .= '<span class="badge">'.$total_subdomain_amount.'</span>';
								
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
					
					$query->set( 'post_type', array_merge(array('user-page'),$this->get_user_site_storages()) );
				}
			}
		}
		
		return $query;
	}
	
	public function get_user_profile_rows($request){
		
		$page_rows = [];
		
		return $page_rows;
	}
	
	public function get_user_page_rows($request){
		
		$page_rows = [];
		
		if( $posts = get_posts(array(
		
			'post_type' 		=> 'user-page',
			'post_status' 		=> array('publish','draft'),
			'author' 			=> $this->parent->user->ID,
			'posts_per_page'	=> -1,
			
		))){

			foreach( $posts as $i => $post ){
				
				$layer_type = $this->parent->layer->get_layer_type($post);
				
				if( !empty($layer_type->name) ){
				
					$row = [];
				
					$row['preview'] 	= '<div class="thumb_wrapper" style="background:url(' . $this->parent->layer->get_thumbnail_url($post) . ');background-size:cover;background-repeat:no-repeat;background-position:center center;width:100%;display:inline-block;"></div>';
					$row['name'] 		= ucfirst($post->post_title);
					$row['type'] 		= $layer_type->name;
					$row['status'] 		= $this->parent->layer->parse_layer_status($post->post_status);
					$row['action'] 		= $this->parent->layer->get_action_buttons($post,$layer_type);
					
					$page_rows[] = $row;
				}
			}
		}
		
		return $page_rows;
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
