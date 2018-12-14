<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Domains_Admin_API {
	
	var $parent;
	
	/**
	 * Constructor function
	 */
	public function __construct ( $parent ) {
		
		$this->parent 	= $parent;
		
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
		
		do_action( 'updated_option', array( $this, 'settings_updated' ), 10, 3 );
	}

	/**
	 * Generate HTML for displaying fields
	 * @param  array   $field Field data
	 * @param  boolean $echo  Whether to echo the field HTML or return it
	 * @return void
	 */
	public function display_field ( $data = array(), $item = false, $echo = true ) {

		// Get field info
		if ( isset( $data['field'] ) ) {
			
			$field = $data['field'];
			
		} else {
			
			$field = $data;
		}

		// Check for prefix on option name
		
		$option_name = '';
		
		if ( isset( $data['prefix'] ) ) {
			
			$option_name = $data['prefix'];
		}

		// Get saved data
		$data = '';
		
		if ( !empty( $item->caps ) ) {
			
			// Get saved field data
			
			$option_name .= $field['id'];
			
			if( isset($item->{$field['id']}) ){
				
				$option = $item->{$field['id']};
			}
			else{
				
				$option = get_user_meta( $item->ID, $field['id'], true );
			}

			// Get data to display in field
			if ( isset( $option ) ) {
				
				$data = $option;
			}

		} 
		elseif ( !empty($item->ID) ) {

			// Get saved field data
			$option_name .= $field['id'];
			$option = get_post_meta( $item->ID, $field['id'], true );

			// Get data to display in field
			if ( isset( $option ) ) {
				$data = $option;
			}

		} 
		else{

			// Get saved option
			
			$option_name .= $field['id'];
			$option = get_option( $option_name );

			// Get data to display in field
			
			if ( isset( $option ) ) {
				
				$data = $option;
			}
		}
		
		// get field id
		
		$id = esc_attr( str_replace(array('[',']'),array('_',''),$field['id']) );
		
		// get field style
		
		$style = '';
		
		if( !empty($field['style']) ){
			
			$style = ' style="'.$field['style'].'"';
		}

		// Show default data if no option saved and default is supplied

		if ( empty($data) && isset( $field['default'] ) ) {
			
			$data = $field['default'];
			
		} 
		elseif ( $data === false ) {
			
			$data = '';
		}
		
		$disabled = ( ( isset($field['disabled']) && $field['disabled'] === true ) ? ' disabled="disabled"' : '' );

		$required = ( ( isset($field['required']) && $field['required'] === true ) ? ' required="true"' : '' );
		
		$html = '';
		
		if( !empty($disabled) ){
			
			$html .= '<input class="form-control" id="' . $id . '" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '"'.$required.'/>' . "\n";
		}

		switch( $field['type'] ) {

			case 'text':
			case 'url':
			case 'email':
				$html .= '<input' . $style . ' class="form-control" id="' . $id . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" '.$required.$disabled.'/>' . "\n";
			break;
			
			case 'slug':
				$html .= '<span style="background: #e5e5e5;padding: 3px 7px;color: #666;border: 1px solid #ddd;">'.home_url() . '/</span><input class="form-control" id="' . $id . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" '.$required.$disabled.'/><span style="background: #e5e5e5;padding: 3px 7px;color: #666;border: 1px solid #ddd;">/</span>' . "\n";
			break;
			
			case 'margin':
				
				$value = esc_attr( $data );
				
				if($value == ''){
					
					$value = esc_attr( $field['default'] );
				}
				
				$html .= '<input class="form-control" id="' . $id . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $value . '" '.$required.$disabled.'/>' . "\n";
			break;

			case 'password':
			case 'number':
			case 'hidden':
				$min = '';
				if ( isset( $field['min'] ) ) {
					$min = ' min="' . esc_attr( $field['min'] ) . '"';
				}

				$max = '';
				if ( isset( $field['max'] ) ) {
					$max = ' max="' . esc_attr( $field['max'] ) . '"';
				}
				$html .= '<input class="form-control" id="' . $id . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '"' . $min . '' . $max . ''.$required.$disabled.'/>' . "\n";
			break;
			
			case 'text_secret':
				$html .= '<input class="form-control" id="' . $id . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="" '.$required.$disabled.'/>' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea'.$style.' class="form-control" id="' . $id . '" style="width:100%;height:300px;" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '"'.$required.$disabled.'>' . $data . '</textarea><br/>'. "\n";
			break;

			case 'checkbox':
				$checked = '';
				if ( $data && 'on' == $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input'.$style.' class="form-control" id="' . $id . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ''.$required.$disabled.'/>' . "\n";
			break;

			case 'checkbox_multi':
			
				foreach ( $field['options'] as $k => $v ) {
					
					$checked = false;
					if ( in_array( $k, (array) $data ) ) {
						$checked = true;
					}
					
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input class="form-control" type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" '.$required.$disabled.'/> ' . $v . '</label> ';
					$html .= '<br>';
				}
			break;
			
			case 'key_value':

				if( !isset($data['key']) || !isset($data['value']) ){

					$data = ['key' => [ 0 => '' ], 'value' => [ 0 => '' ]];
				}

				$inputs = ['string','text','number','password'];
				
				$html .= '<div id="'.$field['id'].'" class="sortable">';
					
					$html .= ' <a href="#" class="add-input-group" style="line-height:40px;">Add field</a>';
				
					$html .= '<ul class="input-group ui-sortable">';
						
						foreach( $data['key'] as $e => $key) {

							if($e > 0){
								
								$class='input-group-row ui-state-default ui-sortable-handle';
							}
							else{
								
								$class='input-group-row ui-state-default ui-state-disabled';
							}
						
							$value = str_replace('\\\'','\'',$data['value'][$e]);
									
							$html .= '<li class="'.$class.'" style="display:inline-block;width:100%;">';
						
								$html .= '<select name="'.$field['name'].'[input][]" style="float:left;">';

								foreach ( $inputs as $input ) {
									
									$selected = false;
									if ( isset($data['input'][$e]) && $data['input'][$e] == $input ) {
										
										$selected = true;
									}
									
									$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $input ) . '">' . $input . '</option>';
								}
								
								$html .= '</select> ';
						
								$html .= '<input type="text" placeholder="key" name="'.$field['name'].'[key][]" style="width:30%;float:left;" value="'.$data['key'][$e].'">';
								
								$html .= '<span style="float:left;"> => </span>';
								
								if(isset($data['input'][$e])){
									
									if($data['input'][$e] == 'number'){
										
										$html .= '<input type="number" placeholder="number" name="'.$field['name'].'[value][]" style="width:30%;float:left;" value="'.$value.'">';
									}
									elseif($data['input'][$e] == 'password'){
										
										$html .= '<input type="password" placeholder="password" name="'.$field['name'].'[value][]" style="width:30%;float:left;" value="'.$value.'">';
									}
									elseif($data['input'][$e] == 'text'){
										
										$html .= '<textarea placeholder="text" name="'.$field['name'].'[value][]" style="width:30%;float:left;height:200px;">' . $value . '</textarea>';
									}										
									else{
										
										$html .= '<input type="text" placeholder="value" name="'.$field['name'].'[value][]" style="width:30%;float:left;" value="'.$value.'">';
									}
								}
								else{
									
									$html .= '<input type="text" placeholder="value" name="'.$field['name'].'[value][]" style="width:30%;float:left;" value="'.$value.'">';
								}

								if( $e > 0 ){
									
									$html .= '<a class="remove-input-group" href="#">[ x ]</a> ';
								}

							$html .= '</li>';						
						}
					
					$html .= '</ul>';					
					
				$html .= '</div>';

			break;

			case 'radio':
			
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				
			break;

			case 'select':

				if(isset($field['name'])){
					
					$html .= '<select class="form-control" name="' . $field['name'] . '" id="' . $id . '"'.$required.$disabled.'>';
				}
				else{
					
					$html .= '<select class="form-control" name="' . esc_attr( $option_name ) . '" id="' . $id . '"'.$required.$disabled.'>';
				}

				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( $k == $data ) {
						
						$selected = true;
					}
					elseif(isset($field['selected']) && $field['selected'] == $k ){
						
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				
				
			break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . $id . '" multiple="multiple">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, (array) $data ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;

			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image' , 'live-template-editor-domains' ) . '" data-uploader_button_text="' . __( 'Use image' , 'live-template-editor-domains' ) . '" class="image_upload_button button" value="'. __( 'Upload new image' , 'live-template-editor-domains' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="'. __( 'Remove image' , 'live-template-editor-domains' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
			break;

			case 'color':
				?><div class="color-picker" style="position:relative;">
			        <input type="text" name="<?php esc_attr_e( $option_name ); ?>" class="color" value="<?php esc_attr_e( $data ); ?>" />
			        <div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>
			    </div>
			    <?php
			break;

			case 'domain':
				
				$exts = array('.com','.net','.org');
				
				$html .= '<div class="input-group">';
					
					$html .= '<input class="form-control" id="' . $id . '" type="text" name="' . esc_attr( $option_name ) . '[domain_name][name][]" placeholder="' . $placeholder . '" value="" '.$required.$disabled.'/>' . "\n";

					$html .= '<span	class="input-group-addon" style="background:#fff;">';
					
						$html .= '<select name="'.esc_attr( $option_name ).'[domain_name][ext][]" style="border:none;">';

							foreach ( $exts as $ext ) {
								
								$selected = false;
								if ( isset($data['ext']) && $data['ext'] == $ext ) {
									
									$selected = true;
								}
								
								$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $ext ) . '">' . $ext . '</option>';
							}
						
						$html .= '</select> ';
					
					$html .= '</span>';
					
					$html .= '<input type="hidden" name="valid_domain[]" value="'.$field['id'].'" />';

				$html .= '</div>';
			
			break;			
		}

		//output description
		
		switch( $field['type'] ) {

			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<br/><span class="description">' . $field['description'] . '</span>';
			break;

			default:
				if ( ! $item ) {
					$html .= '<label for="' . $id . '">' . "\n";
				}

				$html .= '<div><i style="color:#aaa;">' . $field['description'] . '</i></div>' . "\n";

				if ( ! $item ) {
					$html .= '</label>' . "\n";
				}
			break;
		}

		if ( ! $echo ) {
			return $html;
		}

		echo $html;

	}

	/**
	 * Validate form field
	 * @param  string $data Submitted value
	 * @param  string $type Type of field to validate
	 * @return string       Validated value
	 */
	public function validate_field ( $data = '', $type = 'text' ) {

		switch( $type ) {
			case 'text'	: $data = esc_attr( $data ); break;
			case 'url'	: $data = esc_url( $data ); break;
			case 'email': $data = is_email( $data ); break;
		}

		return $data;
	}

	/**
	 * Add meta box to the dashboard
	 * @param string $id            Unique ID for metabox
	 * @param string $title         Display title of metabox
	 * @param array  $post_types    Post types to which this metabox applies
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side')
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high')
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box ( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {

		// Get post type(s)
		if ( ! is_array( $post_types ) ) {
			
			$post_types = array( $post_types );
		}

		// Generate each metabox
		foreach ( $post_types as $post_type ) {
			
			add_meta_box( $id, $title, array( $this, 'meta_box_content' ), $post_type, $context, $priority, $callback_args );
		}
	}

	/**
	 * Display metabox content
	 * @param  object $post Post object
	 * @param  array  $args Arguments unique to this metabox
	 * @return void
	 */
	public function meta_box_content ( $post, $args ) {

		$fields = apply_filters( $post->post_type . '_custom_fields', array(), $post->post_type );

		if ( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		echo '<div class="custom-field-panel">' . "\n";

		foreach ( $fields as $field ) {

			if ( ! isset( $field['metabox'] ) ) continue;

			if ( ! is_array( $field['metabox'] ) ) {
				
				$field['metabox'] = array( $field['metabox'] );
			}

			if ( in_array( $args['id'], $field['metabox'] ) ) {

				$this->display_meta_box_field( $field, $post );
			}
		}

		echo '</div>' . "\n";

	}

	/**
	 * Dispay field in metabox
	 * @param  array  $field Field data
	 * @param  object $post  Post object
	 * @return void
	 */
	public function display_meta_box_field ( $field = array(), $post ) {

		if ( ! is_array( $field ) || 0 == count( $field ) ) return;

		$meta_box  = '<p class="form-field form-group">' . PHP_EOL;
		
			$meta_box .= '<label for="' . $field['id'] . '">' . $field['label'] . '</label> ' . PHP_EOL;
			
			$meta_box .= $this->display_field( $field, $post, false ) . PHP_EOL;
			
		$meta_box .= '</p>' . PHP_EOL;

		echo $meta_box;
	}

	/**
	 * Save metabox fields
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function save_meta_boxes ( $post_id = 0 ) {

		if ( ! $post_id ) return;

		$post_type = get_post_type( $post_id );

		$fields = apply_filters( $post_type . '_custom_fields', array(), $post_type );

		if ( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		foreach ( $fields as $field ) {
			
			if ( isset( $_REQUEST[ $field['id'] ] ) ) {
				
				update_post_meta( $post_id, $field['id'], $this->validate_field( $_REQUEST[ $field['id'] ], $field['type'] ) );
			} 
			else {
				
				update_post_meta( $post_id, $field['id'], '' );
			}
		}
	}
}