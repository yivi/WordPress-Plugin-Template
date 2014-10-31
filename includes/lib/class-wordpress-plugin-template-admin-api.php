<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WordPress_Plugin_Template_Admin_API {

	public $textdomain;
	/**
	 * Constructor function
	 */
	public function __construct ($textdomain = '') {
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
		$this->textdomain = $textdomain;
	}

	/**
	 * Generate HTML for displaying fields
	 * @param  array   $field Field data
	 * @param  boolean $echo  Whether to echo the field HTML or return it
	 */
	public function display_field ( $data = array(), $post = false, $echo = true ) {

		// Get field info
		if( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		// Some defaults for field data, in case not all field's fields are provided
		$field_defaults = array(
			'placeholder' => '',
			'label' => __('Default label', $this->textdomain),
			'description' => __('Default description', $this->textdomain),
			'type' => 'text',
			'metabox' => array(),
			'default' => __('Default Default', $this->textdomain)

		);

		$repeatable = isset($field['type']) && $field['type'] == 'repeatable' ? true : false;

		$field = wp_parse_args($field, $field_defaults);


		// Check for prefix on option name
		if( isset( $data['prefix'] ) )
			$option_name = $data['prefix'] . $field['id'];
		else
			$option_name = $field ['id'];

		# Also for a suffix.
		if ( isset ( $data['suffix'] ) )
			$option_name .= $data['suffix'];

		$data = false;
		if ( isset($field['data']) ) {
			# Get data from call, do not query the DB
			$data = $field['data'];
		}

		elseif ( $repeatable && $post ) {
			$option = get_post_meta( $post->ID, $field['id'], true);

			if ( isset($option) )
				$data = unserialize($option);

		}

		elseif( $post && !$repeatable) {

			// Get saved field data
			$option = get_post_meta( $post->ID, $field['id'], true );

			// Get data to display in field
			if( isset( $option ) ) {
				$data = $option;
			}

		} else {

			// Get saved option
			$option = get_option( $option_name );

			// Get data to display in field
			if( isset( $option ) ) {
				$data = $option;
			}

		}

		// Show default data if no option saved and default is supplied
		if( $data === false && isset( $field['default'] ) && !$repeatable ) {
			$data = $field['default'];
		} elseif( $data === false && !$repeatable) {
			$data = '';
		}
		elseif ( $data === false && $repeatable)
			$data = array();

		$html = '';

		switch( $field['type'] ) {

			case 'repeatable':

				if ( !isset( $field['options'] ) or !is_array( $field['options'] ) ) break;

				$html .= "<span class='repeater-group'>";

				$fields_per_group = count( $field['options'] );
				$count_fields = 0;
				$open_tag = true;

				foreach ( $data as $group_of_fields ) {
					foreach ( $group_of_fields as $group_field ) {
						$count_fields++;

						if ( $open_tag ) {
							$open_tag = false;
							$id_field = "repeater-fields-$count_fields";
							$html .= "<div class='repeater-fields' id='$id_field' >";
						}

						## setting whatever else we need to capture this fields later on
						$group_field['prefix'] = $field['id'] . '_';
						$group_field['suffix'] = '[]';
						$group_field['data'] = $group_field['value'];
						$html .= '<p class="form-field"><label for="' . esc_attr( $group_field['id'] ) . '">' . esc_attr( $group_field['label'] ) . '</label>';
						$html .= $this->display_field( $group_field, $post, false );
						$html .= '</p>';
						if ( $count_fields % $fields_per_group == 0 ) {
							$html .= "<a class='repeater-remover'>" . __('Remove Group', $this->textdomain) . "</a>";
							$html .= "\n</div>";
							$open_tag = true;
						}
					}

				}

				$html .= "<div class='repeater-fields template' style='display: none'>\n";
				foreach ( $field['options'] as $repeatable_field ) {
					$repeatable_field['prefix'] = $field['id'] . '_';
					$repeatable_field['suffix'] = '-template';
					$html .= '<p class="form-field"><label for="' . esc_attr($repeatable_field['id']) . '">' . esc_attr($repeatable_field['label']) . '</label>';
					$html .= $this->display_field($repeatable_field, $post, false);
					$html .= '</p>';
				}
				$html .= "<a class='repeater-remover'>" . __('Remove Group', $this->textdomain) . "</a>";
				$html .= "\n</div>";

				$html .= "<a class='repeater-adder'>" . __('Add New', $this->textdomain) . "</a>";


				break;

			case 'text':
			case 'url':
			case 'email':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
				break;

			case 'password':
			case 'number':
			case 'hidden':
				$min = '';
				if( isset( $field['min'] ) ) {
					$min = ' min="' . esc_attr( $field['min'] ) . '"';
				}

				$max = '';
				if( isset( $field['max'] ) ) {
					$max = ' max="' . esc_attr( $field['max'] ) . '"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '"' . $min . '' . $max . '/>' . "\n";
				break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="" />' . "\n";
				break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br/>'. "\n";
				break;

			case 'checkbox':
				$checked = '';
				if( $data && 'on' == $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
				break;

			case 'checkbox_multi':
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( in_array( $k, $data ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'radio':
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach( $field['options'] as $k => $v ) {
					$selected = false;
					if( $k == $data ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
				foreach( $field['options'] as $k => $v ) {
					$selected = false;
					if( in_array( $k, $data ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				break;

			case 'image':
				$image_thumb = '';
				if( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
					$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				}
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image' , $this->textdomain ) . '" data-uploader_button_text="' . __( 'Use image' , $this->textdomain ) . '" class="image_upload_button button" value="'. __( 'Upload new image' , $this->textdomain ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="'. __( 'Remove image' , $this->textdomain ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
				break;

			case 'color':
				?><div class="color-picker" style="position:relative;">
				<input type="text" name="<?php esc_attr_e( $option_name ); ?>" class="color" value="<?php esc_attr_e( $data ); ?>" />
				<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>
				</div>
				<?php
				break;

		}

		switch( $field['type'] ) {

			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<br/><span class="description">' . $field['description'] . '</span>';
				break;

			case 'repeatable':
				break;

			default:
				if( ! $post ) {
					$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
				}

				$html .= '<span class="description">' . $field['description'] . '</span>' . "\n";

				if( ! $post ) {
					$html .= '</label>' . "\n";
				}
				break;
		}

		if( ! $echo ) {
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
			case 'text': $data = esc_attr( $data ); break;
			case 'url': $data = esc_url( $data ); break;
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
	 * @param array  $callback_args Any extra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box ( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {


		// Get post type(s)
		if( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		// Generate each metabox
		foreach( $post_types as $post_type ) {
			add_meta_box( $id, $title, array( $this, 'meta_box_content' ), $post_type, $context, $priority, $callback_args );
		}
	}

	/**
	 * Add Meta Boxes to the Dashboard
	 *
	 * Convenience method to generate metaboxes from hash
	 * array
	 *     ['id'] Id for the Metabox (mandatory, wont add otherwise)
	 *     ['title'] Title for the Metabox
	 *     ['context'] Context for the metabox  ('normal', 'advanced', or 'side')
	 *     ['priority'] The priority within the context where the boxes should show ('high', 'core', 'default' or 'low')
	 *     ['callback_args'] Arguments to pass into your callback function. The callback will receive the $post object and whatever parameters are passed through this variable.
	 *
	 * @param array $metaboxes
	 */
	public function add_meta_boxes($metaboxes = array()) {
		// Shortcircuit if empty
		if (empty($metaboxes)) return;

		$default_metabox = array(
			'title' => '',
			'post_types' => array(),
			'context' => 'advanced',
			'priority' => 'default',
			'callback_args' => null
		);

		foreach ( $metaboxes as $metabox ) {
			if ( ! isset( $metabox['id'] ) or empty( $metabox['id'] ) ) {
				continue;
			}

			$metabox = wp_parse_args( $metabox, $default_metabox );

			$this->add_meta_box(
				$metabox['id'],
				$metabox['title'],
				$metabox['post_types'],
				$metabox['context'],
				$metabox['priority'],
				$metabox['callback_args'] );
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

		if( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		echo '<div class="custom-field-panel">' . "\n";

		foreach( $fields as $field ) {

			if( ! isset( $field['metabox'] ) ) continue;

			if( ! is_array( $field['metabox'] ) ) {
				$field['metabox'] = array( $field['metabox'] );
			}

			if( in_array( $args['id'], $field['metabox'] ) ) {
				$this->display_meta_box_field( $field, $post );
			}

		}
		/**
		 * filter {$metaboxid}_post_meta_box_content
		 *
		 * To add content at the end of a particular metabox.
		 *
		 * @parm Post $post
		 * @parm array $args
		 */
		do_action($args['id'] .'_post_meta_box_content', $post, $args);

		echo '</div>' . "\n";

	}

	/**
	 * Dispay field in metabox
	 * @param  array  $field Field data
	 * @param  object $post  Post object
	 * @return void
	 */
	public function display_meta_box_field ( $field = array(), $post ) {

		if( ! is_array( $field ) || 0 == count( $field ) ) return;

		if (isset($field['type']) && $field['type'] == 'repeatable') {
			echo '<div class="form-field repeatable"><label>' . $field['label'] . '</label>';

			$this->display_field($field, $post, true);

			echo '</div>' . "\n";
		}
		else {
			echo '<p class="form-field"><label for="' . $field['id'] . '">' . $field['label'] . '</label>';

			$this->display_field($field, $post, true);

			echo '</p>' . "\n";
		}


	}

	/**
	 * Save metabox fields
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function save_meta_boxes ( $post_id = 0 ) {

		if( ! $post_id ) return;

		$post_type = get_post_type( $post_id );

		$fields = apply_filters( $post_type . '_custom_fields', array(), $post_type );

		if( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		foreach( $fields as $field ) {
			if ( $field['type'] == 'repeatable' )
				# repeatables are treated differently. and diffidently.
				$this->save_repeatable($post_id, $field);

			elseif ( isset( $_REQUEST[ $field['id'] ] ) ) {
				update_post_meta( $post_id, $field['id'], $this->validate_field( $_REQUEST[ $field['id'] ], $field['type'] ) );
			} else {
				update_post_meta( $post_id, $field['id'], '' );
			}
		}
	}

	/**
	 * @param $post_id
	 * @param $repeater_field
	 */
	public function save_repeatable($post_id, $repeater_field) {

		$existing = get_post_meta( $post_id, $repeater_field['id'], true );

		# initialize array
		$newdata = array();

		# get all the individual "ids" for the repeater fields in this group
		$field_keys  = wp_list_pluck( $repeater_field['options'], 'id' );

		# get how many repetitions we have
		$count =  count($_REQUEST[$repeater_field['id'] . '_' . $field_keys[0]]);

		for ( $i = 0; $i < $count; $i++ ) {
			# iterate through all the repeatable groups
			foreach ( $repeater_field['options'] as $field_template ) {
				# iterate through each field in a repeatable
				$input_field = $repeater_field['id'] . '_' . $field_template['id'];
				foreach ( array_keys( $field_template ) as $field_option) {
					# iterate through each option for each field, and save it in $newdata array.
					$newdata[$i][$field_template['id']][$field_option] = $field_template[$field_option];
				}

				# finally get the value from the input proper, and save it on a new key.
				$newdata[$i][$field_template['id']]['value'] = $_REQUEST[$input_field][$i];
			}
		}

		$new_serial = serialize($newdata);
		if ( ! empty( $newdata ) && $new_serial != $existing )
			update_post_meta( $post_id, $repeater_field['id'], $new_serial, $existing );
		elseif ( empty( $newdata ) && $existing )
			delete_post_meta( $post_id, $repeater_field['id'], $existing );

		# fingers crossed
	}

}