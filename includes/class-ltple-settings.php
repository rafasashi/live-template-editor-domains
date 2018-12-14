<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Domains_Settings {

	/**
	 * The single instance of LTPLE_Domains_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		
		$this->parent = $parent;
		
		$this->plugin 		 	= new stdClass();
		$this->plugin->slug  	= 'live-template-editor-domains';
		
		// add plugin to addons
		
		add_action('ltple_admin_addons', array($this, 'plugin_info' ) );
		
		// add settings
		
		add_action('ltple_plugin_settings', array($this, 'settings_fields' ) );
		
		// add menu
		
		add_action( 'ltple_admin_menu' , array( $this, 'add_menu_items' ) );

		// add tabs
		
		add_filter( 'ltple_admin_tabs', array( $this, 'add_tabs'), 1 );		
	}
	
	public function plugin_info(){
		
		$this->parent->settings->addons['live-template-editor-domains'] = array(
			
			'title' 		=> 'Live Template Editor Domains',
			'addon_link' 	=> 'https://github.com/rafasashi/live-template-editor-domains',
			'addon_name' 	=> 'live-template-editor-domains',
			'source_url' 	=> 'https://github.com/rafasashi/live-template-editor-domains/archive/master.zip',
			'description'	=> 'Domain and subdomain management addon for Live Template Editor',
			'author' 		=> 'Rafasashi',
			'author_link' 	=> 'https://profiles.wordpress.org/rafasashi/',
		);
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	public function settings_fields () {
		
		$settings = [];
		
		$settings['urls']['fields'][] = array(
		
			'id' 			=> 'domainSlug',
			'label'			=> __( 'Domains' , $this->plugin->slug ),
			'description'	=> '[ltple-client-domains]',
			'type'			=> 'slug',
			'placeholder'	=> __( 'domains', $this->plugin->slug )
		);
		
		$settings['domains'] = array(
			'title'					=> __( 'Domains', $this->plugin->slug ),
			'description'			=> __( 'Domain & subdomain settings', $this->plugin->slug ),
			'fields'				=> array(
				array(
					'id' 			=> 'enable_domains',
					'label'			=> __( 'Enable Domains' , $this->plugin->slug ),
					'description'	=> '',
					'type'			=> 'switch',
				),
				array(
					'id' 			=> 'enable_subdomains',
					'label'			=> __( 'Enable Subdomains' , $this->plugin->slug ),
					'description'	=> '',
					'type'			=> 'switch',
				),
				array(
					'id' 			=> 'default_domains',
					'label'			=> __( 'Shared Domains' , $this->plugin->slug ),
					'description'	=> 'One domain per line',
					'type'			=> 'textarea',
					'placeholder'	=> 'example.com',
					'style'			=> 'height:150px;width:250px;',
				),
			)
		);		
		
		if( !empty($settings) ){
		
			foreach( $settings as $slug => $data ){
				
				if( isset($this->parent->settings->settings[$slug]['fields']) && !empty($data['fields']) ){
					
					$fields = $this->parent->settings->settings[$slug]['fields'];
					
					$this->parent->settings->settings[$slug]['fields'] = array_merge($fields,$data['fields']);
				}
				else{
					
					$this->parent->settings->settings[$slug] = $data;
				}
			}
		}
	}
	
	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_items () {
		
		//add menu in wordpress dashboard
		/*
		add_submenu_page(
			'live-template-editor-client',
			__( 'Addon test', $this->plugin->slug ),
			__( 'Addon test', $this->plugin->slug ),
			'edit_pages',
			'edit.php?post_type=post'
		);
		*/
	}
	
	public function add_tabs() {
		
		//dump($this->parent->settings->tabs);
		
		$this->parent->settings->tabs['user-contents']['user-domain'] = array( 'name' => 'Domains');
	}
}
