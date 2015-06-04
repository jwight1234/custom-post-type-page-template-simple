<?php
/*
Plugin Name: Custom Post Type Page Template - Simple
Plugin URI: wight-space.com
Description: It also let you select which templates show up under selected post type.
Author: John Wight
Version: 1.0
Author URI: http://wight-space.com/
*/

class Custom_Post_Type_Page_Template_Simple {

	/**
	 * These are the global options for the options page
	 * @var object
	 */
	private $options;

	/**
	 * Array of wordpress templates
	 * @var array
	 */
	private $templates = array();

	
	/**
	 * Everything gets started here
	 */
	public function __construct() {

		$this->options = array('html5-blank' => array(	'page-templates/template-demo-1.php', 
														'page-templates/template-demo-2.php'));

		add_action('admin_init', 							array($this, 'cptpts_admin_init'));
		add_action('save_post', 							array($this, 'cptpts_save_post'));
		add_filter('template_include', 						array($this, 'cptpts_include'));
		add_filter('body_class', 							array($this, 'cptpts_body_classes'));

		add_action('admin_print_scripts-edit.php', 			array($this, 'cptpts_enqueue_admin_edit_scripts') );
		add_action('admin_enqueue_scripts', 				array($this, 'cptpts_enqueue_admin_options_page_scripts') );

		add_action('save_post',  							array($this, 'cptpts_quick_bulk_edit_save_post' ), 10, 2 );
		add_action('wp_ajax_cptpts_save_bulk_quick_edit', 	array($this, 'cptpts_save_bulk_quick_edit') );
		add_action('quick_edit_custom_box',					array($this, 'cptpts_quick_bulk_edit_custom_box'), 10, 2);
		add_action('bulk_edit_custom_box', 					array($this, 'cptpts_quick_bulk_edit_custom_box'), 10, 2);

	}

	/**
	 * This check to see what post type are selected on the options page, then creates column and metabox
	 * @return none
	 */
	public function cptpts_admin_init() {

		
		foreach( $this->options as $post_type => $items ) :
			
			add_meta_box( 'pagetemplatediv', __('Template', 'custom-post-type-page-template'), array($this, 'cptpts_meta_box'), $post_type, 'side', 'core');
			add_filter('manage_'. $post_type .'_posts_columns', array($this, 'add_template_column'), 10, 2);

			if (is_post_type_hierarchical($post_type)) {
				add_action('manage_pages_custom_column', 	array($this, 'set_posts_data_custom_column'), 10, 2 );
			}else {
				add_action('manage_posts_custom_column', 	array($this, 'set_posts_data_custom_column'), 10, 2 );
			}

		endforeach;
		
	}

	/**
	 * Add Column to edit page
	 * @param $posts_columns
	 */
	public function add_template_column($posts_columns)
	{
		$posts_columns['wp_page_template_column'] = 'Template';
		return $posts_columns;
	}


	/**
	 * Create Meta box template dropdown
	 * @param  Object $post Returns post data object
	 * @return none
	 */
	public function cptpts_meta_box($post) {
		
		$template = get_post_meta($post->ID, '_wp_page_template', true); ?>
		
		<label class="screen-reader-text" for="cptpts_page_template"><?php _e('Page Template', 'custom-post-type-page-template') ?></label>
		<select name="page_template" id="cptpts_page_template">
		<option value='-1'>
			<?php _e('Default Template', 'custom-post-type-page-template'); ?>
		</option>
		<?php $this->cptpts_page_template_dropdown($template, $post->post_type); ?>
		</select>

		<?php
	}

	/**
	 * Combines "page_template" & "_wp_page_template"
	 * @param  $post_id 
	 * @return Integer
	 */
	public function cptpts_save_post( $post_id ) {
		if ( !empty($_POST['page_template']) ) :
			if ( $_POST['page_template'] != 'default' ) :
				update_post_meta($post_id, '_wp_page_template', $_POST['page_template']);
			else :
				delete_post_meta($post_id, '_wp_page_template');
			endif;
		endif;
	}

	/**
	 * Includes Template with wp templates
	 * @param  $template
	 * @return Array 
	 */
	public function cptpts_include($template) {
		global $wp_query, $post;

		if ( is_singular() && !is_page() ) :
			$id = get_queried_object_id();
			$new_template = get_post_meta( $id, '_wp_page_template', true );
			if ( $new_template && file_exists(get_query_template( 'page', $new_template )) ) :
				$wp_query->is_page = 1;
				$templates[] = $new_template;
				return get_query_template( 'page', $templates );
			endif;
		endif;
		return $template;
	}


	/**
	 * Add Css Classes to body class
	 * @param  $classes 
	 * @return String         
	 */
	public function cptpts_body_classes( $classes ) {
		if ( is_singular() && is_page_template() ) :
			$classes[] = 'page-template';
			$classes[] = 'page-template-' . sanitize_html_class( str_replace( '.', '-', get_page_template_slug( get_queried_object_id() ) ) );
		endif;
		return $classes;
	}
	
	/**
	 * wp_enqueue_script
	 * @return [type] [description]
	 */
	public function cptpts_enqueue_admin_edit_scripts() {
		wp_enqueue_script( 'cptpte-bulk-quick-edit',  plugin_dir_url( __FILE__ ) . '/js/cptpte-bulk-quick-edit.js', array( 'jquery', 'inline-edit-post' ), '', true );
	}

	/**
	 * wp_enqueue_script
	 * @param  $hook 
	 * @return None
	 */
	public function cptpts_enqueue_admin_options_page_scripts($hook) {
		if ($hook == "post.php"):			
			wp_enqueue_script( 'cptpte-post-edit-js',  plugin_dir_url( __FILE__  ) . 'js/cptpte-post-edit.js', array( 'jquery' ), '', true );
		endif;
	}

	/**
	 * Adds Data to column
	 * @param $column_name 
	 * @param $post_id     	 
	 * */
	public function set_posts_data_custom_column( $column_name, $post_id ) {

		switch( $column_name ) {
			case 'wp_page_template_column':
				$meta = get_post_meta( $post_id, '_wp_page_template', true );
				$name = $this->get_page_templates_nice_name($meta);
				echo '<div id="wp_page_template-' . $post_id . '" data-slug="'. $meta .'">' . $name . '</div>';
			break;
		}

	}

	/**
	 * Add our text to the quick edit box
	 * @param  $column_name
	 * @param  $post_type  
	 * @return None           
	 */
	public function cptpts_quick_bulk_edit_custom_box($column_name, $post_type)
	{
		if ( $post_type ) :
			switch( $column_name ){
				case 'wp_page_template_column':
					?><fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<label>
								<span class="title">Template</span>
								<span class="input-text-wrap">
									<select name="_wp_page_template">
										<option value="-1">— No Change —</option>;
										<?php $this->cptpts_page_template_dropdown('', $post_type); ?>
									</select>
								</span>
							</label>
						</div>
					</fieldset>
					<?php
				break;
			}
		endif;
	}

	/**
	 * Save Bulk Edit
	 * @param  $post_id
	 * @param  $post   
	 * @return         
	 */
	public function cptpts_quick_bulk_edit_save_post( $post_id, $post ) {

		// pointless if $_POST is empty (this happens on bulk edit)
		if ( empty( $_POST ) ):
			return $post_id;
		endif;

		// verify quick edit nonce
		if ( isset( $_POST[ '_inline_edit' ] ) && ! wp_verify_nonce( $_POST[ '_inline_edit' ], 'inlineeditnonce' ) ):
			return $post_id;
		endif;

		// don't save for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ):
			return $post_id;
		endif;

		// dont save for revisions
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ):
			return $post_id;
		endif;

		if( $post->post_type ):
				$custom_fields = array( '_wp_page_template' );
				foreach( $custom_fields as $field ) :
					if ( array_key_exists( $field, $_POST ) ) :
						update_post_meta( $post_id, $field, $_POST[ $field ] );
					endif;
				endforeach;
		endif;

	}

	/**
	 * Save Bulk Quick Edit
	 * @return none
	 */
	public function cptpts_save_bulk_quick_edit() {

		// we need the post IDs
		$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : NULL;
		// if we have post IDs
		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) :
			// get the custom fields
			$custom_fields = array( '_wp_page_template' );
			foreach( $custom_fields as $field ):
				// if it has a value, doesn't update if empty on bulk
				if ( isset( $_POST[ $field ] ) && !empty( $_POST[ $field ] ) ) :
					// update for each post ID
					foreach( $post_ids as $post_id ) :
						update_post_meta( $post_id, $field, $_POST[ $field ] );
					endforeach;
				endif;
			endforeach;
		endif;

	}

	/**
	 * Create Template Dropdown
	 * @param  string $default 
	 * @param  string $post_type 
	 * @return array List of templates by post type          
	 */
	public function cptpts_page_template_dropdown( $default = '', $post_type ) {

		$templates = array();		
		$cptpts_array = $this->options;
		foreach ($cptpts_array as $post_type_key => $templates_items) :
			 if ($post_type_key == $post_type):

				if(isset($templates_items)):

					$selected_arr = $templates_items;
					$theme_templates = get_page_templates();
					$name_arr = array();
					foreach ( $theme_templates as $template_name => $template_filename ):
						foreach ($selected_arr as $key => $value):
							if ($template_filename == $value):
								$name_arr[$template_name] = $template_filename;
							endif;
						endforeach;
					endforeach;
					$templates = $name_arr;
				else:
					$templates = get_page_templates();
				endif;
			endif;
		endforeach;
		ksort( $templates );
		foreach (array_keys( $templates ) as $key => $template ):
			$slug_name = $this->get_page_templates_slug($templates[$template]);
			if ( $default == $slug_name):
				$selected = " selected='selected'";
			else:
				$selected = '';
			endif;
			echo "\n\t<option value='".$slug_name."' $selected>$template</option>";
		endforeach;
		

	}


	/**
	 * Compairs Key and Value to get nice name
	 * @param  $slug
	 * @return string
	 */
	public function get_page_templates_nice_name($slug){

		$temp = get_page_templates();
		$return = '' ;

		foreach ($temp as $key => $value) {
			if ($value == $slug) {
				$return = $key;
				break;
			}
		}
		return $return;
	}


	/**
	 * Compairs Key and Value to get slug
	 * @param  $slug
	 * @return string
	 */
	public function get_page_templates_slug($slug){

		$temp = get_page_templates();
		$return = '' ;

		foreach ($temp as $key => $value) {
			if ($value == $slug) {
				$return = $value;
				break;
			}
		}
		return $return;
	}


}// end class

$custom_post_type_page_template_Simple = new Custom_Post_Type_Page_Template_Simple();