<?php
	
	if(!empty($_REQUEST['action'])){
		
		if( $_REQUEST['action'] == 'addSubdomain' ){
			
			$suggested_name = strtolower($this->parent->user->user_login);
			
			$output = ( !empty( $_REQUEST['output']) ? $_REQUEST['output'] : 'default' );
			
			echo'<div style="margin:20px 2px;">';
				
				echo'<p>';
				
					echo'Your subdomain must be longer that 6 characters and containing only letters and numbers.';
				
				echo'</p>';
				
				echo'<form action="' . $this->parent->urls->current . '" method="post">';
					
					echo'<input type="hidden" name="output" value="'.$output.'" />';
					
					echo'<input type="hidden" name="action" value="addSubdomain" />';
					
					echo'<input type="text" name="subdomain" value="' . $suggested_name . '" placeholder="mysubdomain" class="form-control input-sm" style="width:270px;display:inline-block;" />';
					
					echo'<select name="domain" class="form-control input-sm" style="width:180px;display:inline-block;">';
						
						$default_domains = $this->get_default_domains();
						
						if(!empty($default_domains)){

							foreach( $default_domains as $domain ){
							
								echo'<option value="' . $domain . '">';
								
									echo '.' . $domain;

								echo'</option>';
							}
						}
						else{
							
							echo'<option value="-1">None</option>';
						}
					
					echo'</select>';
					
					echo' <button type="submit" class="btn btn-primary btn-sm" >request</button>';
				
				echo'</form>';
				
			echo'</div>';
		}
	}