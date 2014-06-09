<?php
/**
 * This is all the functionality related to the metaboxes
 *
 * @return DripPress_PostMeta
 */

class DripPress_PostMeta
{


	/**
	 * This is our constructor
	 *
	 * @return DripPress_PostMeta
	 */
	public function __construct() {
		add_action			(	'add_meta_boxes',						array(	$this,	'create_metaboxes'			)			);
		add_action			(	'save_post',							array(	$this,	'save_drip_meta'			),	10		);
	}

	/**
	 * initial call for metaboxes
	 *
	 * @return void
	 */

	public function create_metaboxes() {

		// get our post types
		$types	= DripPress_Data::types();

		// bail if set to none
		if ( ! $types || empty( $types ) ) {
			return;
		}

		// add side box for drip schedule
		foreach( $types as $type ) {
			add_meta_box( 'dppress-schd', __( 'Drip Schedule', 'drippress' ), array( $this, 'drip_schedule_box' ), $type, 'side', 'core' );
		}


	}

	/**
	 *
	 * @return [type] [description]
	 */
	public function drip_schedule_box() {

		global $post;

		// fetch data
		$live	= get_post_meta( $post->ID, '_dppress_live', true );
		$meta	= get_post_meta( $post->ID, '_dppress_meta', true );
		$ranges	= DripPress_Data::ranges();

		// parse out serialized values
		$count	= isset( $meta['count'] ) && ! empty( $meta['count'] ) ? absint( $meta['count'] ) : '';
		$range	= isset( $meta['range'] ) && ! empty( $meta['range'] ) ? esc_attr( $meta['range'] ) : '';
		$label	= $count === 1 ? 'single' : 'plural';

		// Use nonce for verification
		wp_nonce_field( 'dppress_schd_nonce', 'dppress_schd_nonce' );

		// checkbox display
		echo '<p class="dppress-on-field">';
			echo '<input id="dppress-live" type="checkbox" name="dppress-live" value="1" ' . checked( $live, 1, false ) . ' />';
			echo '<label for="dppress-live">' . __( 'Drip this content', 'drippress' ) . '</label>';
		echo '</p>';

		echo '<ul class="dppress-data">';

			echo '<li class="dppress-count-field">';
				echo '<span class="field-title">' . __( 'Count', 'drippress' ) . '</span>';
				echo '<span class="field-input">';
				echo '<input type="text" class="widefat" name="dppress-meta[count]" id="dppress-meta-count" value="' . $count . '">';
				echo '</span>';
			echo '</li>';

			echo '<li class="dppress-range-field">';
				echo '<span class="field-title">' . __( 'Range', 'drippress' ) . '</span>';

				echo '<span class="field-input">';
				echo '<select name="dppress-meta[range]" id="dppress-meta-range">';

					echo ' <option value="">' . __( 'Select', 'drippress' ) . '</option>';
					foreach ( $ranges as $key => $values ):
						// build out the select dropdown
						echo '<option value="' . $key . '" ' . selected( $range, $key, false ) . '>' . esc_html( $values[$label] ) . '</option>';
					endforeach;

				echo '</select>';
				echo '<span>';
			echo '</li>';
		echo '</ul>';

		// our hidden date / time setup for sorting
		$date	= get_post_field( $post->ID, 'post_date_gmt', 'raw' );
		$sort	= ! $date ? time() : strtotime( $date );

		echo '<input type="hidden" name="dppress-sort" value="' . $sort . '">';
	}

	/**
	 * save metadata for the drip scheduling
	 * @param  $post_id
	 * @return void
	 */
	public function save_drip_meta( $post_id ) {

		// make sure we aren't using autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// do our nonce check. ALWAYS A NONCE CHECK
		if ( ! isset( $_POST['dppress_schd_nonce'] ) || ! wp_verify_nonce( $_POST['dppress_schd_nonce'], 'dppress_schd_nonce' ) ) {
			return $post_id;
		}

	    if ( ! current_user_can( 'edit_post', $post_id ) ) {
	        return $post_id;
	    }

		// get our post types
		$types	= DripPress_Data::types();

		// bail if set to none or not in our array
		if ( ! $types || empty( $types ) || ! in_array( get_post_type( $post_id ), $types ) ) {
			return $post_id;
		}

		// set the sort time
		$sort	= ! empty( $_POST['dppress-sort'] ) ? $_POST['dppress-sort'] : time();

		// bail and purge if we didn't check the box
		if ( ! isset( $_POST['dppress-live'] ) || isset( $_POST['dppress-live'] ) && empty( $_POST['dppress-live'] ) ) {
			// set the drip sort time for sorting purposes
			update_post_meta( $post_id, '_dppress_sort', $sort );
			// delete the rest
			delete_post_meta( $post_id, '_dppress_live' );
			delete_post_meta( $post_id, '_dppress_meta' );
			delete_post_meta( $post_id, '_dppress_drip' );

			return $post_id;
		}

		// bail if we don't have any data
		if ( ! isset( $_POST['dppress-meta'] ) ) {
			update_post_meta( $post_id, '_dppress_sort', $sort );
			return $post_id;
		}

		// fetch the event date info
		$data	= (array) $_POST['dppress-meta'];

		// set an empty
		$meta	= array();

		// clean up and sanitize the data
		$meta['count']	= isset( $data['count'] ) && ! empty( $data['count'] ) ? absint( $data['count'] ) : false;
		$meta['range']	= isset( $data['range'] ) && ! empty( $data['range'] ) ? sanitize_text_field( $data['range'] ) : false;

		// check for data and save or delete
		if ( ! empty( $meta ) ) {
			update_post_meta( $post_id, '_dppress_live', 1 );
			update_post_meta( $post_id, '_dppress_meta', $meta );
		} else {
			update_post_meta( $post_id, '_dppress_sort', $sort );

			delete_post_meta( $post_id, '_dppress_live' );
			delete_post_meta( $post_id, '_dppress_meta' );
			delete_post_meta( $post_id, '_dppress_drip' );

			return $post_id;
		}

		// run the drip calculation
		$drip	= DripPress_Data::calculate_drip( $post_id, $meta );
		if ( ! empty( $drip ) ) {
			update_post_meta( $post_id, '_dppress_sort', $sort + $drip );
			update_post_meta( $post_id, '_dppress_drip', $drip );
		} else {
			update_post_meta( $post_id, '_dppress_sort', $sort );
			delete_post_meta( $post_id, '_dppress_drip' );
		}
	}



/// end class
}


// Instantiate our class
new DripPress_PostMeta();
