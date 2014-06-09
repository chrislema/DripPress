<?php
/**
 * This is all the functionality related to the metaboxes
 *
 * @return DripPress_PostMeta
 */

class DripPress_Admin
{


	/**
	 * This is our constructor
	 *
	 * @return DripPress_PostMeta
	 */
	public function __construct() {
		add_action			(	'admin_enqueue_scripts',				array(	$this,	'scripts_styles'			),	10		);
		add_action			(	'manage_posts_custom_column',			array(	$this,	'post_columns_data'			),	10,	2	);
		add_filter			(	'manage_edit-post_columns',				array(	$this,	'post_columns_display'		)			);
	}


	/**
	 * load CSS on post editor
	 * @param  [type] $hook [description]
	 * @return [type]       [description]
	 */
	public function scripts_styles( $hook ) {

		// get our post types
		$types	= DripPress_Data::types();

		// get current screen info
		$screen	= get_current_screen();

		if ( is_object( $screen ) && in_array( $screen->post_type, $types ) ) {
			wp_enqueue_script( 'drippress-admin', plugins_url( '/js/drippress.admin.js', __FILE__ ) , array( 'jquery' ), DRIPPRESS_VER, true );
			wp_enqueue_style( 'drippress-admin', plugins_url( '/css/drippress.admin.css', __FILE__), array(), DRIPPRESS_VER, 'all' );
		}

	}


	/**
	 * [column_display description]
	 * @return [type] [description]
	 */
	public function post_columns_display( $columns ) {

		$columns['drip-length']	= __( 'Drip Length', 'drippress' );

		return $columns;

	}

	/**
	 * [columns_data description]
	 * @param  [type] $column  [description]
	 * @param  [type] $post_id [description]
	 * @return [type]          [description]
	 */
	public function post_columns_data( $column, $post_id ) {

		switch ( $column ) {

			case 'drip-length':
				$label	= DripPress_Data::drip_display_length( $post_id );

				echo '<p class="drip-length">'. $label . '</p>';
	 		break;

		// end all case breaks
		}

	}

/// end class
}


// Instantiate our class
new DripPress_Admin();
