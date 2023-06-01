<?php 

	if(!empty($this->parent->message)){ 
	
		//output message
	
		echo $this->parent->message;
	}
	
	if( $message = $this->parent->session->get_user_data('message') ){
		
		echo $message.PHP_EOL;
	}

	// get current tab
	
	$tabs = ['list','urls'];
	
	$currentTab = ( !empty($_GET['tab']) && in_array($_GET['tab'],$tabs) ? $_GET['tab'] : 'default' );
	
	// ------------- output panel --------------------
	
	echo'<div id="panel" class="wrapper">';

		echo '<div id="sidebar">';

			echo '<ul class="nav nav-tabs tabs-left">';
				
				echo apply_filters('ltple_domains_sidebar','',$currentTab);
				
			echo '</ul>';
			
		echo '</div>';
		
		echo'<div id="content" class="library-content" style="border-left:1px solid #ddd;background:#fbfbfb;padding-bottom:15px;min-height:700px;">';
			
			echo'<div class="tab-content">';

				if( $currentTab == 'default' ){
					
					//---------------------- output default domains --------------------------
					
					echo'<div id="domain-listing">';
					
						if(!empty($this->parent->message)){
							
							echo $this->parent->message;
						}
						else{
							
							echo'<div class="col-xs-12">';
							
								echo'<div class="bs-callout bs-callout-primary">';

									echo '<h4>Domains and Subdomains</h4>';

									echo '<p>';
									
										echo 'In this section you can manage your domains and subdomains.';
									
									echo'</p>';
								
								echo'</div>';
								
							echo'</div>';
							
							if(!empty($this->parent->user->domains->list)){
								
								echo'<div class="col-xs-12 col-lg-7">';

								foreach($this->parent->user->domains->list as $domain_type => $domains ){
									
									echo'<table class="table table-striped">';
									
										echo'<thead>';
										
											echo'<tr>';
												
												echo'<th>';
												
												if( $domain_type == 'subdomain' ){
													
													if( $holder_domains = $this->parent->user->domains->get_license_holder_domain_list('subdomain')){
														
														$domains = $holder_domains;
														
														$total_plan = $this->parent->user->domains->get_total_plan_subdomains();
													}
													else{
														
														$total_plan = count($domains);
													}
													
													echo'<span style="float:left;margin:3px 5px 0 0;display:inline-block;font-size:15px;font-weight:bold;">Subdomains</span>';
													
													echo' <span style="float:left;margin:4px;font-size:12px;" class="label label-primary">' . count($domains) . ' / ' . $total_plan . '</span>';
												}
												elseif( $domain_type == 'domain' ){
													
													$license_holder_domains = count($this->parent->user->domains->get_license_holder_domain_list('domain'));
													$total_plan_domains 	= $this->parent->user->domains->get_total_plan_domains();
																										
													echo'<span style="float: left;margin: 3px 5px 0 0;display: inline-block;font-size: 15px;font-weight: bold;">Connected Domains</span>';
													
													echo' <span style="float:left;margin:4px;font-size:12px;" class="label label-primary">' . $license_holder_domains . ' / ' . $total_plan_domains . '</span>';
													
													$permalink = 'connect-domain';
													
													$modal_id = 'modal_'.md5($permalink);

													echo'<button id="addSubdomain" type="button" class="pull-right btn btn-sm btn-success" data-toggle="modal" data-target="#'.$modal_id.'">Add</span></button>';

													echo'<div class="modal fade" id="'.$modal_id.'" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">'.PHP_EOL;
														
														echo'<div class="modal-dialog modal-lg" role="document">'.PHP_EOL;
															
															echo'<div class="modal-content">'.PHP_EOL;
															
																echo'<div class="modal-header">'.PHP_EOL;
																	
																	echo'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'.PHP_EOL;
																	
																	echo'<h4 class="modal-title text-left" id="myModalLabel">Connect an existing domain</h4>'.PHP_EOL;
																
																echo'</div>'.PHP_EOL;
															  
																echo'<div class="modal-body">'.PHP_EOL;
																	
																	if( $total_plan_domains > $license_holder_domains ){
																		
																		echo'To connect an existing domain please contact the support team';											
																	}
																	else{
																		
																		echo'You cannot connect more domains, please contact the support team...';
																	}

																echo'</div>'.PHP_EOL;
															  
															echo'</div>'.PHP_EOL;
															
														echo'</div>'.PHP_EOL;
														
													echo'</div>'.PHP_EOL;
												}
												
												echo'</th>';
												
											echo'</tr>';
											
										echo'</thead>';
										
										echo'<tbody>';
										
										if( !empty($domains) ){
											
											foreach($domains as $domain ){
												
												echo'<tr>';
													echo'<td>';
													
														echo '<a href="' . $this->parent->request->proto . $domain->post_title . '" target="_blank">' . $domain->post_title . ( intval($domain->post_author) != $this->parent->user->ID ? ' <span class="label label-default" style="margin-left:10px;font-size:10px;">managed</span>' : '' ) . '</a>';
													
													echo'</td>';
												echo'</tr>';
											}
										}
										else{
											
											echo'<tr>';
												echo'<td>';
												
													echo 'no '.$domain_type.'s for this user';
												
												echo'</td>';
											echo'</tr>';											
										}

										echo'</tbody>';
										
									echo'</table>';	

								}
								
								//echo '<a href="' . $this->parent->urls->domains . '?tab=urls">URLs & Pages</a>';
									
								echo'</div>';		
							}
							else{
								
								echo'<div class="well">';
								
									echo 'No domains found';
								
								echo'</div>';	
							}		
						}
						
					echo'</div>';
				}
				elseif( $currentTab == 'urls' ){

					//---------------------- output members --------------------------
					
					echo'<div id="urls">';

							echo'<div class="bs-callout bs-callout-primary">';

								echo '<h4>Assign Urls to Hosted Pages</h4>';

								echo '<p></p>';
							
							echo'</div>';
						
							if( !empty( $this->parent->user->layers ) ){
					
								echo'<table class="table table-striped">';
								
									/*
									echo'<thead>';
									
										echo'<tr>';
										
											echo'<th><b>Templates</b></th>';
											echo'<th><b>View</b></th>';
											
										echo'</tr>';
										
									echo'</thead>';
									*/
									
									echo'<tbody>';
								
									foreach( $this->parent->user->layers as $layer ){
										
										if( $layer->type->output == 'hosted-page' ){
											
											echo'<tr>';
											
												echo'<td>';
												
													echo $layer->post_title;
												
												echo'</td>';

												echo'<td style="width:560px;">';
													
													echo'<form action="' . $this->parent->urls->current . '" method="post">';
												
														echo'<input type="hidden" name="layerId" value="' . $layer->ID . '" />';
														
														echo'<input type="hidden" name="domainAction" value="assign" />';
												
														echo'<select name="domainUrl[domainId]" class="form-control input-sm" style="width:180px;display:inline-block;">';
															
															echo'<option value="-1">None</option>';
															
															if(!empty($this->parent->user->domains->list)){
																
																$domainName = '';
																
																foreach( $this->parent->user->domains->list as $domain_type => $domains ){
																	
																	foreach( $domains as $domain ){
																	
																		if(isset($domain->urls[$layer->ID])){
																			
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
														
														$domainPath = '';
														
														foreach($this->parent->user->domains->list as $domains){
															
															foreach($domains as $domain){
															
																if(isset($domain->urls[$layer->ID])){
																	
																	$domainPath = $domain->urls[$layer->ID];
																}
															}
														}
														
														echo'<input type="text" name="domainUrl[domainPath]" value="'.$domainPath.'" placeholder="category/page-title" class="form-control input-sm" style="width:270px;display:inline-block;" />';
													
														echo' <button type="submit" class="btn btn-primary btn-sm" >assign</button>';
													
													echo'</form>';
													
												echo'</td>';	
												
												echo'<td style="width:50px;">';
												
													$domainUrl = get_permalink($layer->ID);

													echo '<a href="' . $domainUrl . '" target="_blank" class="btn btn-success btn-sm" style="margin-left: 4px;border-color: #9c6433;color: #fff;background-color: rgb(189, 120, 61);">';
													
														echo 'view';
													
													echo '</a>';
												
												echo'</td>';
												
												echo'<td style="width:50px;">';
												
													echo '<a href="' . $this->parent->urls->edit .'?uri=' . $layer->ID . '" target="_blank" class="btn btn-success btn-sm">';
													
														echo 'edit';
													
													echo '</a>';

												echo'</td>';											
												
											echo'</tr>';
										}
									}

									echo'</tbody>';
									
								echo'</table>';
							}
							else{
								
								echo'<div class="well">';
								
									echo 'No saved templates found';
								
								echo'</div>';
							}
					
					echo'</div>';
				}

			echo'</div>';
			
		echo'</div>	';

	echo'</div>';