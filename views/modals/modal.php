<?php

	$form_url = add_query_arg(array(
		
		'_' 	 => time(),
		
	),$this->parent->urls->current);
	
	$modal_id = 'modal_'.md5($form_url);
	
?>

<div id="<?php echo $modal_id; ?>Backdrop" class="modal-backdrop in"></div>

<div id="<?php echo $modal_id; ?>" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="channelModal" style="display:block;">
	
	<div class="modal-dialog modal-lg" role="document">
		
		<div class="modal-content">
		
			<div class="modal-header">

				<h4 class="modal-title" id="channelModal">Subdomain Reservation</h4>
			
			</div>
		  
			<div class="modal-body" style="height:350px;">
				
				<form target="_self" action="<?php echo $form_url; ?>" method="post">
					
					<label>You haven't reserved your Subdomain yet!</label>
					
					<?php 
					
						if( !empty($_SESSION['message']) ){
							
							echo $_SESSION['message'];
						
							$_SESSION['message'] = '';
						}
						
						$_REQUEST['action'] = 'addSubdomain';
						
						include($this->views . '/widget.php');
					?>
				</form>
				
				<div style="font-style:italic;margin:30px 0px;max-width:400px;width:100%;">
				
					You can assign Hosted Pages to your subdomain or keep it as a Profile Card.
					
					<br>
					
					<!-- Just click on the link <b>"Unsubscribe from the Newsletter"</b> located in the footer of every email we send.-->
				
				</div>

			</div>

		</div>
		
	</div>
	
</div>

<script>
		
	;(function($){

		$(document).ready(function(){
			
			$('#submitsubdomainReservation').on('click', function (e) {
				
				e.preventDefault();
				
				$form = $(this).closest("form");

				$.ajax({
					
					type 		: $form.attr('method'),
					url  		: $form.attr('action'),
					data		: $form.serialize(),
					beforeSend	: function() {

						$('#subdomainReservation').css('display','none');
						$('#subdomainReservationBackdrop').css('display','none');
					},
					success: function(data) {
						
					}
				});
			});
		});
		
	})(jQuery);		
		
</script>