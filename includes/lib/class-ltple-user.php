<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Domains_User {

	var $parent;
	var $list = null;
	
	/**
	 * Constructor function
	 */
	public function __construct ( $parent ) {

		$this->parent 	= $parent;

		if( $this->parent->user->loggedin ){
			
			$this->list = $this->get_domain_list( $this->parent->user, true );
			
			$this->save_urls();
		}
	}
	
	public function get_domain_list( $user = null ){
		
		$list = array('subdomain'=>[],'domain'=>[]);
		
		$user_id = 0;
		
		if( is_numeric($user) ){
			
			$user_id = intval($user);
		}
		elseif( !empty($user->ID) ){
			
			$user_id = $user->ID;
		}
		
		if( $domains = get_posts(array(
			
			'author'   		=> $user_id,
			'post_type'   	=> 'user-domain',
			'post_status' 	=> 'publish',
			//'numberposts' => -1,
			
		))){
			
			foreach( $domains as $domain ){
				
				$domain->urls = get_post_meta($domain->ID ,'domainUrls', true);
			
				$domain->type = $this->parent->domains->get_domain_type($domain->post_title);
				
				$list[$domain->type][] = $domain;
			}				
		}

		return $list;
	}
	
	public function get_user_plan_domains(){
		
		$user_plan = $this->parent->plan->get_user_plan_info($this->parent->user->ID);
		
		return $user_plan['info']['total_domain_amount'];
	}	
	
	public function get_user_plan_subdomains(){
		
		$user_plan = $this->parent->plan->get_user_plan_info($this->parent->user->ID);
		
		return $user_plan['info']['total_subdomain_amount'];
	}
	
	public function save_urls(){

		if( !is_admin() && !empty($_POST) ){
			
			if( !empty($_POST['layerId']) && !empty($_POST['domainUrl']['domainId']) && isset($_POST['domainUrl']['domainPath']) && !empty($_POST['domainAction']) ){
				
				$layerId 	= floatval($_POST['layerId']);
				
				$domainId 	= floatval($_POST['domainUrl']['domainId']);
				
				$domainPath = sanitize_text_field($_POST['domainUrl']['domainPath']);
				
				if( $_POST['domainAction'] == 'assign' && $layerId > 0 && is_numeric($domainId) ){
					
					if( $this->parent->user->is_admin || in_array_field($layerId, 'ID', $this->parent->user->layers) ){
						
						foreach( $this->list as $domain_type => $domains ){
							
							foreach( $domains as $domain ){
								
								if( $domainId == $domain->ID ){

									if( in_array( $domainPath, $domain->urls) ){
										
										// unset previous url
										
										foreach($domain->urls as $id => $path){
											
											if( $path == $domainPath){
												
												unset($domain->urls[$id]);
											}
										}
									}
									
									// update domain url

									$domain->urls[$layerId] = $domainPath;
								
									update_post_meta( $domain->ID, 'domainUrls', $domain->urls );

									// output message
									
									$this->parent->message .= '<div class="alert alert-success">';
									
										$this->parent->message .= 'Url successfully updated...';
										
									$this->parent->message .= '</div>';
								}
								/*
								elseif( isset($domain->domainUrls[$layerId]) ){
									
									// update previous domain
									
									unset($domain->domainUrls[$layerId]);
									
									update_post_meta( $domain->ID, 'domainUrls', $domain->domainUrls );
								}
								*/
							}
						}
					}
				}
			}
		}		
	}
	
	/**
	 * Main LTPLE_Client_User_Domains Instance
	 *
	 * Ensures only one instance of LTPLE_Client_Stars is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see LTPLE_Client()
	 * @return Main LTPLE_Client_Stars instance
	 */
	public static function instance ( $parent ) {
		
		if ( is_null( self::$_instance ) ) {
			
			self::$_instance = new self( $parent );
		}
		
		return self::$_instance;
		
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()
}