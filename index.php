<?php

/**
 * Plugin Name: WP Team Members
 * Description: Adds a custom post type for storing team members' bio and contact info.
 * Version: 1.2
 * Author: @mismith
 * Author URI: http://mismith.info/
 * License: GPL2
 */
 
class WP_Team_Members {
	
	private $type;
	private $prefix;
	private $category;
	private $name;
	private $names;
	private $custom_meta_fields;
	
	public function __construct(){
		
		// add custom post type, along with respective custom meta fields
		$this->type     = 'team';
		$this->prefix   = $this->type.'_';
		$this->category = 'category';
		$this->name     = 'Team Member';
		$this->names    = $this->name.'s';
		$this->icon     = 'groups';
		$this->custom_meta_fields = array(
			array(
				'label' => 'Job title',
				'id'    => $this->prefix.'jobtitle',
				'type'  => 'text'
			),
			array(
				'label' => 'Email',
				'id'    => $this->prefix.'email',
				'type'  => 'email'
			),
			array(
				'label' => 'Phone Number',
				'id'    => $this->prefix.'phone',
				'type'  => 'tel'
			),
			array(
				'label' => 'Facebook Username',
				'id'    => $this->prefix.'facebook',
				'type'  => 'text'
			),
			array(
				'label' => 'Twitter Username',
				'id'    => $this->prefix.'twitter',
				'type'  => 'text'
			),
			array(
				'label' => 'LinkedIn URL',
				'id'    => $this->prefix.'linkedin',
				'type'  => 'text'
			),
		);
		
		if($this->category !== 'category') add_action( 'init', array( $this, 'add_custom_taxonomy' ));
		
		add_action( 'init', array( $this, 'add_custom_post_type' ));
		add_theme_support( 'post-thumbnails', array( $this->type ));
		
		add_action('save_post', array( $this, 'save_custom_meta' ));
	}
	
	function add_custom_taxonomy() {
		register_taxonomy(
			$this->category,  //The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
			$this->type,      //post type name
			array(
				'hierarchical'   => true,
				'label'          => 'Categories',
				'query_var'      => true,
				'rewrite'        => array(
					'slug'       => $this->type, // This controls the base slug that will display before each term
					'with_front' => false        // Don't display the category base before
				)
			)
		);
	}
	
	// Registers the new post type and taxonomy
	function add_custom_post_type() {
		register_post_type( $this->type,
			array(
				'labels' => array(
					'name' => __( $this->names ),
				),
				'public' => true,
				'menu_icon' => 'dashicons-' . $this->icon,
				'taxonomies' => array( $this->category ),
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'capability_type' => 'post',
				'rewrite' => true, // Permalinks format
				'menu_position' => 23, // above comments
				'register_meta_box_cb' => array( $this, 'add_custom_meta_box' )
			)
		);
	}
	
	
	function add_custom_meta_box() {
		add_meta_box('custom_meta_box', $this->name, array( $this, 'show_custom_meta_box'), $this->type, 'normal');
	}
	
	function show_custom_meta_box() {
		global $post;
		
		// Use nonce for verification
		echo '<input type="hidden" name="custom_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';
		
		// Begin the field table and loop
		echo '<table class="form-table">';
		foreach ($this->custom_meta_fields as $field) {
			// get value of this field if it exists for this post
			$meta = get_post_meta($post->ID, $field['id'], true);
			// begin a table row with
			echo '<tr><th><label for="'.$field['id'].'">'.$field['label'].'</label></th><td>';
			switch($field['type']) {
				// text
				default:
				case 'text':
					echo '<input type="'.$field['type'].'" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="30" /><br /><span class="description">'.$field['desc'].'</span>';
					break;
				// textarea
				case 'textarea':
					echo '<textarea name="'.$field['id'].'" id="'.$field['id'].'" cols="60" rows="4">'.$meta.'</textarea><br /><span class="description">'.$field['desc'].'</span>';
					break;
				// checkbox
				case 'checkbox':
					echo '<input type="checkbox" name="'.$field['id'].'" id="'.$field['id'].'" ',$meta ? ' checked="checked"' : '','/><label for="'.$field['id'].'">'.$field['desc'].'</label>';
					break;
				// select
				case 'select':
					echo '<select name="'.$field['id'].'" id="'.$field['id'].'">';
					foreach ($field['options'] as $option) {
						echo '<option', $meta == $option['value'] ? ' selected="selected"' : '', ' value="'.$option['value'].'">'.$option['label'].'</option>';
					}
					echo '</select><br /><span class="description">'.$field['desc'].'</span>';
					break;
			} //end switch
			echo '</td></tr>';
		} // end foreach
		echo '</table>'; // end table
	}
	
	function save_custom_meta($post_id) {
		// verify nonce
		if (!wp_verify_nonce($_POST['custom_meta_box_nonce'], basename(__FILE__))) 
			return $post_id;
		// check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $post_id;
		// check permissions
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} elseif (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
		 
		// loop through fields and save the data
		foreach ($this->custom_meta_fields as $field) {
			$old = get_post_meta($post_id, $field['id'], true);
			$new = $_POST[$field['id']];
			if ($new && $new != $old) {
				update_post_meta($post_id, $field['id'], $new);
			} elseif ('' == $new && $old) {
				delete_post_meta($post_id, $field['id'], $old);
			}
		} // end foreach
	}

}
$wp_team_members = new WP_Team_Members;