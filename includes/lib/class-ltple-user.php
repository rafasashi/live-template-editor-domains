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
			
			$this->list = $this->parent->domains->get_user_domain_list( $this->parent->user, true );
			
			$this->save_domain();
			
			$this->save_urls();
			
			$this->edit_layer_url();
		}
	}
	
	public function get_total_plan_domains(){
		
		$user_plan = $this->parent->plan->get_user_plan_info($this->parent->user->ID);
		
		$total_domain_amount = isset( $user_plan['info']['total_domain_amount'] ) ? $user_plan['info']['total_domain_amount'] : 0;
		
		return $total_domain_amount;
	}

	public function get_license_holder_domain_list($filter=''){
		
		$domain_list = array(
			
			'subdomain' => array(),
			'domain' 	=> array(),
		);
		
		$user_plan = $this->parent->plan->get_user_plan_info($this->parent->user->ID);
	
		if( !empty($user_plan['holder']) ){
			
			$users = $this->parent->plan->get_license_users($user_plan['holder']);
			
			foreach( $users as $user_id ){
				
				$user_list = $this->parent->domains->get_user_domain_list( $user_id );
			
				if( !empty($user_list['subdomain']) ){
					
					$domain_list['subdomain'] = array_merge($domain_list['subdomain'],$user_list['subdomain']);
				}
				
				if( !empty($user_list['domain']) ){
					
					$domain_list['domain'] = array_merge($domain_list['domain'],$user_list['domain']);
				}
			}
		}
		
		if( !empty($filter) ){
			
			if( isset($domain_list[$filter]) ){
				
				return $domain_list[$filter];
			}
			
			return array();
		}
		
		return $domain_list;
	}	
	
	public function get_total_plan_subdomains(){
		
		$user_plan = $this->parent->plan->get_user_plan_info($this->parent->user->ID);
				
		$total_subdomain_amount = isset( $user_plan['info']['total_subdomain_amount'] ) ? $user_plan['info']['total_subdomain_amount'] : 0;
		
		return $total_subdomain_amount;
	}
	
	public function save_domain(){
		
		if( !empty($_POST['action']) ){
							
			$message = '';
				
			if( $_POST['action'] == 'addSubdomain' ){
				
				// validate subdomain
				
				$domain = !empty($_POST['domain']) ? strtolower($_POST['domain']) : '';
				
				$subdomain = !empty($_POST['subdomain']) ? strtolower($_POST['subdomain']) : '';
				
				$default_domains = $this->parent->domains->get_default_domains();

				if( strlen($subdomain) < 6 ){
					
					// error length
					
					$message .= '<div class="alert alert-warning">This subdomain is smaller than 6 characters</div>';
				}
				elseif( !ctype_alnum($subdomain) ){
					
					$message .= '<div class="alert alert-warning">This subdomain is not alphanumeric, please use only letters and numbers</div>';
				}
				elseif( !in_array($domain,$default_domains) ){
					
					// error domain
					
					$message .= '<div class="alert alert-warning">This shared domain is not registered</div>';
				}
				elseif( get_posts(array(
				
					'post_type' => 'user-domain',
					'title' 	=> $subdomain . '.' . $domain,
				
				)) ){ 

					$message .= '<div class="alert alert-warning">This subdomain is already taken</div>';
				}
				else{
					
					$user_subdomains 		= ( !empty($this->parent->user->domains->list['subdomain']) ? count($this->parent->user->domains->list['subdomain']) : 0 );
					$user_plan_subdomains 	= $this->get_total_plan_subdomains();

					if( $user_plan_subdomains > $user_subdomains ){
						
						// add subdomain
						
						$post_id = wp_insert_post( array(
						
							'post_title' 		=> $subdomain . '.' . $domain,
							'post_type'     	=> 'user-domain',
							'post_status'     	=> 'publish',
							'post_author' 		=> $this->parent->user->ID,
						));					
					
						do_action('ltple_subdomain_reserved');
					
						$message .= '<div class="alert alert-success">You have successfully added a subdomain</div>';
					}
					else{
					
						$message .= '<div class="alert alert-warning">You cannot create more subdomains</div>';
					}
				}
				
				$message .= '<script>' . PHP_EOL;
					$message .= 'window.onunload = refreshParent;' . PHP_EOL;
					$message .= 'function refreshParent() {' . PHP_EOL;
						$message .= 'window.opener.location.reload();' . PHP_EOL;
					$message .= '}' . PHP_EOL;
				$message .= '</script>' . PHP_EOL;
			}
			elseif( $_POST['action'] == 'addDomain' ){
				
				// add connected domain
				
				
			}
			
			if( !empty($message) ){
				
				$this->parent->session->update_user_data('message',$message);
			}
		}
	}
	
	public function edit_layer_url(){
		
		// update urls
		
		if( !empty($this->parent->user->layer) && !empty($_POST['postAction']) && $_POST['postAction'] == 'edit' && isset($_POST['domainUrl']['domainId']) && isset($_POST['domainUrl']['domainPath']) ){
			
			$domainId 	= floatval($_POST['domainUrl']['domainId']);
			
			$domainPath = sanitize_text_field($_POST['domainUrl']['domainPath']);
			
			if( is_numeric($domainId) ){
				
				foreach( $this->list as $domain_type => $domains ){
					
					foreach( $domains as $domain ){
						
						if( $domainId == $domain->ID ){
							
							if( !empty($domain->urls) && in_array( $domainPath, $domain->urls ) ){
								
								// unset previous url
								
								foreach($domain->urls as $id => $path){
									
									if( $path == $domainPath ){
										
										unset($domain->urls[$id]);
									}
								}
							}
							
							// update domain url

							$domain->urls[$this->parent->user->layer->ID] = $domainPath;
						
							update_post_meta( $domain->ID, 'domainUrls', $domain->urls );
						}
						elseif( $domainId == -1 && isset($domain->urls[$this->parent->user->layer->ID]) ){
							
							// unset domain url
							
							unset($domain->urls[$this->parent->user->layer->ID]);
							
							update_post_meta( $domain->ID, 'domainUrls', $domain->urls );
						}
					}
				}
				
				if( $domainId == -1 && !empty($domainPath) ){
					
					// update post slug
					
					wp_update_post( array(
					
						'ID' 		=> $this->parent->user->layer->ID,
						'post_name'	=> trailingslashit($domainPath),
					));
				}
			}
		}		
	}
	
	public function save_urls(){

		if( !is_admin() && !empty($_POST) ){
			
			if( !empty($_POST['layerId']) && !empty($_POST['domainUrl']['domainId']) && isset($_POST['domainUrl']['domainPath']) && !empty($_POST['domainAction']) ){
				
				$layerId 	= floatval($_POST['layerId']);
				
				$domainId 	= floatval($_POST['domainUrl']['domainId']);
				
				$domainPath = trailingslashit(sanitize_text_field($_POST['domainUrl']['domainPath']));
				
				if( $_POST['domainAction'] == 'assign' && $layerId > 0 && is_numeric($domainId) ){
					
					if( $this->parent->user->is_admin || in_array_field($layerId, 'ID', $this->parent->user->layers) ){
						
						foreach( $this->list as $domain_type => $domains ){
							
							foreach( $domains as $domain ){
								
								if( $domainId == $domain->ID ){

									if( in_array( $domainPath, $domain->urls) ){
										
										// unset previous url
										
										foreach($domain->urls as $id => $path){
											
											if( $path == $domainPath ){
												
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
								elseif( $domainId == -1 && isset($domain->urls[$layerId]) ){
								
									// unset domain url
								
									unset($domain->urls[$layerId]);
								
									update_post_meta( $domain->ID, 'domainUrls', $domain->urls );
								
									// output message
									
									$this->parent->message .= '<div class="alert alert-success">';
									
										$this->parent->message .= 'Url successfully removed...';
										
									$this->parent->message .= '</div>';								
								}
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
