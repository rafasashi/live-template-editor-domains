<?php

	$agreeButton 		= get_option($this->parent->_base  . 'disclamer_agree_buttom', 'I agree');
	$disagreeButton 	= get_option($this->parent->_base  . 'disclamer_disagree_buttom', 'I disagree');
							
	$disclaimer  = '<!DOCTYPE html>';
	
	$disclaimer .= '<html>';
	
		$disclaimer .= '<meta name="robots" content="noindex">';
		
		$disclaimer .= '<link rel="stylesheet" href="'.$this->parent->assets_url . 'css/bootstrap.min.css'.'">';
	
	$disclaimer .= '</html>';
	
	$disclaimer .= '<body style="background:#000;">';
	
		$disclaimer .= '<div id="logo" style="text-align:center;z-index:2000;position:absolute;width: 100%;">';
	
			$disclaimer .= '<div id="logo_wrapper" style="box-shadow:inset 0px 0px 3px #000000;background:#fff;display:inline-block;padding:30px 10px;border-radius:250px;height:100px;width:100px;margin-top:15px;">';
	
				$disclaimer .= '<img style="width:100%;" src="' . $this->parent->settings->options->logo_url . '" />';
	
			$disclaimer .= '</div>';
	
		$disclaimer .= '</div>';
	
		$disclaimer .= '<div class="modal-backdrop in"></div>';
		$disclaimer .= '<div class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="channelModal" style="display:block;">';
			
			$disclaimer .= '<div class="modal-dialog modal-lg" role="document" style="margin-top:130px;">';
				
				$disclaimer .= '<div class="modal-content">';
				
					$disclaimer .= '<div class="modal-header">';

						$disclaimer .= '<h4 class="modal-title" id="channelModal">Content Disclaimer</h4>';
					
					$disclaimer .= '</div>';
				  
					$disclaimer .= '<div class="modal-body" style="height:200px;overflow: auto;line-height: 30px;font-size: 16px;">';
						
						$disclaimer .= str_replace( PHP_EOL, '<br>', $this->disclaimer);

					$disclaimer .= '</div>';
					
					$disclaimer .= '<div class="modal-footer">';
					
						$disclaimer .= '<button id="agreeBtn" class="btn btn-success">' . $agreeButton . '</button>';
					
						$disclaimer .= '<a id="disagreeBtn" href="https://google.com" ref="nofollow" class="btn btn-danger">' . $disagreeButton . '</a>';
						
					$disclaimer .= '</div>';
					
				$disclaimer .= '</div>';
				
			$disclaimer .= '</div>';
			
		$disclaimer .= '</div>';

		$disclaimer .= '<script src="'.$this->parent->assets_url . 'js/jquery.min.js'.'"></script>';
		$disclaimer .= '<script src="'.$this->parent->assets_url . 'js/jquery.cookie.min.js'.'"></script>';
		
		$disclaimer .= '<script>';
				
			$disclaimer .= ';(function($){';

				$disclaimer .= '$(document).ready(function(){';
					
					$disclaimer .= "$('#agreeBtn').on('click', function (e) {";
						
						$disclaimer .= "$.cookie('_ltple_disclaimer', 1, {
							
							domain: '" . $_SERVER['SERVER_NAME'] . "',
							path: '/'
								
						});";
						
						$disclaimer .= 'location.reload();';
					
					$disclaimer .= '});';
					
				$disclaimer .= '});';
				
			$disclaimer .= '})(jQuery);	';	
				
		$disclaimer .= '</script>';		
	
	$disclaimer .= '</body>';

	die($disclaimer);