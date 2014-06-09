<?php
/**
 * Helper file with various data queries and other
 * lookups
 *
 * @return DripPress_Admin
 */

class DripPress_Data
{

	/**
	 * preset our allowed post types for content
	 * modification with filter
	 *
	 * @return array	post types
	 */
	static function types() {
		return apply_filters( 'dppress_types', array( 'post' ) );
	}

	/**
	 * [term_items description]
	 * @param  string $taxonomy [description]
	 * @return [type]           [description]
	 */
	static function term_items( $taxonomy = 'post_tag' ) {

		// set the args for the terms
		$args	= array(
			'hide_empty'	=> false
		);

		// fetch the terms
		$terms	= get_terms( $taxonomy, $args );

		// bail if none
		if ( ! $terms ) {
			return false;
		}

		// send it back
		return $terms;

	}

	/**
	 * preset our displayed date format
	 * modification with filter
	 *
	 * @param  boolean	$show	optional flag to include the time
	 * @return string			format for using in php date()
	 */
	static function date_format( $show = false ) {

		// fetch each value from the database
		$date	= get_option( 'date_format' );
		$time	= get_option( 'time_format' );

		if ( $show === true ) {
			return apply_filters( 'dppress_date_format', $date . ' ' . $time );
		} else {
			return apply_filters( 'dppress_date_format', $date );
		}

	}

	/**
	 * set up the possible ranges to apply a numerical value
	 * to
	 *
	 * @return mixed array		time in seconds with label as key / value pair
	 */
	static function ranges() {

		$ranges	= array(
			'hour'	=> array(
				'single'	=> __( 'Hour', 'drippress' ),
				'plural'	=> __( 'Hours', 'drippress' ),
				'value'		=> HOUR_IN_SECONDS
			),

			'day'	=> array(
				'single'	=> __( 'Day', 'drippress' ),
				'plural'	=> __( 'Days', 'drippress' ),
				'value'		=> DAY_IN_SECONDS
			),

			'week'	=> array(
				'single'	=> __( 'Week', 'drippress' ),
				'plural'	=> __( 'Weeks', 'drippress' ),
				'value'		=> WEEK_IN_SECONDS
			),

		);

		return apply_filters( 'dppress_ranges', $ranges );

	}

	/**
	 * get a written display of the drip length
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	static function drip_display_length( $post_id = 0 ) {

		// fetch the live flag and meta if available
		$live	= get_post_meta( $post_id, '_dppress_live', true );
		$meta	= get_post_meta( $post_id, '_dppress_meta', true );

		// bail if not set to live or no data
		if ( ! $live || ! $meta ) {
			return;
		}

		// parse out serialized values
		$count	= isset( $meta['count'] ) && ! empty( $meta['count'] ) ? absint( $meta['count'] ) : '';
		$range	= isset( $meta['range'] ) && ! empty( $meta['range'] ) ? esc_attr( $meta['range'] ) : '';

		// fetch our ranges data and determine label count
		$ranges	= self::ranges();
		$label	= $count === 1 ? 'single' : 'plural';
		$contxt	= $ranges[$range][$label];

		// combine the data items return
		return $count . ' ' .$contxt;

	}

	/**
	 * fetch the user signup date to compare against
	 * content being displayed
	 *
	 * @param  integer	$user_id 	current user ID being views
	 * @return string	$date		signup date in UNIX time
	 */
	static function user_signup( $user_id = 0 ) {

		// check for current user if no ID is passed
		if ( ! $user_id ) {
			$user_id	= get_current_user_id();
		}

		// bail if no ID can be found
		if ( ! $user_id ) {
			return;
		}

		// fetch WP_User object
		$user	= new WP_User( $user_id );
		$data	= $user->data;
		$date	= $data->user_registered;

		// send it back filtered with the user id
		return apply_filters( 'dppress_user_signup', strtotime( $date ), $user_id );

	}


	/**
	 * fetch the content publish date for use in the
	 * drip comparison with available filter
	 *
	 * @param  integer	$post_id 	current post ID being checked
	 * @return string	$date		signup date in UNIX time
	 */
	static function publish_date( $post_id = 0 ) {

		// check for current post if no ID is passed
		if ( ! $post_id ) {
			$post_id	= get_the_ID();
		}

		// bail if no post ID can be found
		if ( ! $post_id ) {
			return;
		}

		// get the published date in GMT
		$date	= get_post_field( 'post_date_gmt', $post_id, 'raw' );

		// fetch the local post date if GMT is missing
		if ( ! $date ) {
			$date	= get_post_field( 'post_date', $post_id, 'raw' );
		}

		// send it back filtered
		return apply_filters( 'dppress_publish_date', strtotime( $date ), $post_id );

	}

	/**
	 * cacluate the seconds for drips
	 * @param  [type] $post_id [description]
	 * @param  [type] $meta    [description]
	 * @return [type]          [description]
	 */
	static function calculate_drip( $post_id, $meta ) {

		// bail if neither value came through
		if ( empty( $meta['count'] ) || empty( $meta['range'] ) ) {
			return;
		}

		// set each item as a variable
		$count	= absint( $meta['count'] );
		$range	= esc_attr( $meta['range'] );

		// fetch ranges
		$ranges	= self::ranges();

		// check for array key
		if ( ! array_key_exists( $range, $ranges ) ) {
			return;
		}

		// confirm we have a value to calculate
		if ( ! isset( $ranges[$range]['value'] ) || isset( $ranges[$range]['value'] ) && empty( $ranges[$range]['value'] ) ) {
			return;
		}

		// get the time portion
		$time	= $ranges[$range]['value'];

		// process the actual caluculation
		return $count * $time;
	}

	/**
	 * fetch the drip date (if calculated), or attempt to make the calculation
	 *
	 * @param  integer $post_id [description]
	 * @return [type]           [description]
	 */
	static function get_drip_value( $post_id = 0 ) {

		// attempt to fetch the post ID if not passed
		if ( ! $post_id ) {
			$post_id	= get_the_ID();
		}

		// just bail if no ID
		if ( ! $post_id ) {
			return false;
		}

		// look for the calculated value first
		$drip	= get_post_meta( $post_id, '_dppress_drip', true );

		// return the drip if already calculated
		if ( ! empty( $drip ) ) {
			return $drip;
		}

		// no calculated value, get the meta and attempt to do it
		$meta	= get_post_meta( $post_id, '_dppress_meta', true );

		// bail if no meta present
		if ( ! $meta ) {
			return false;
		}

		// return the calculated value (or false)
		return self::calculate_drip( $post_id, $meta );

	}

	/**
	 * handle the actual calculation of the drip date
	 * to be used
	 *
	 * @param  integer	$post_id 	current post ID being checked
	 * @param  integer	$user_id 	current user ID being checked
	 * @return string	$date		drip date in UNIX time
	 */
	static function build_drip_date( $post_id = 0, $user_id = 0 ) {

		// check for current post if no ID is passed
		if ( ! $post_id ) {
			$post_id	= get_the_ID();
		}

		// check for current user if no ID is passed
		if ( ! $user_id ) {
			$user_id	= get_current_user_id();
		}

		// bail if no post or user ID can be found
		if ( ! $post_id || ! $user_id ) {
			return false;
		}

		// attempt to get drip calculation
		$drip_value	= self::get_drip_value( $post_id );

		// bail without a drip value
		if ( ! $drip_value ) {
			return false;
		}

		// get our user signup date
		$user_date	= self::user_signup( $user_id );

		// add the drip duration to the user signup date
		$drip_date	= $user_date + $drip_value;

		// send it back filtered
		return apply_filters( 'dppress_drip_date', $drip_date, $post_id, $user_id );

	}

	/**
	 * compare the signup date to the drip schedule
	 *
	 * @param  integer $post_id [description]
	 * @param  integer $user_id [description]
	 * @return [type]           [description]
	 */
	static function drip_date_compare( $post_id = 0, $user_id = 0 ) {

		// attempt to fetch the user ID if not passed
		if ( ! $user_id ) {
			$user_id	= get_current_user_id();
		}

		// attempt to fetch the post ID if not passed
		if ( ! $post_id ) {
			$post_id	= get_the_ID();
		}

		// bail if we dont have a user ID and a post ID
		if ( ! $user_id || ! $post_id ) {
			return false;
		}

		// get our post publish date
		$post_date	= self::publish_date( $post_id );

		// bail if we dont have a post date
		if ( ! $post_date ) {
			return false;
		}

		// pull our time now
		$now	= apply_filters( 'dppress_drip_baseline', time() );

		// bail on scheduled posts
		if ( $post_date > $now ) {
			return false;
		}

		// attempt to get drip calculation
		$drip_date	= self::build_drip_date( $post_id, $user_id );

		// return true if we've passed our drip duration
		if ( $now >= $drip_date ) {
			return array(
				'display'	=> true,
				'item_id'	=> $post_id
			);
		}

		// send back our message
		if ( $now < $drip_date ) {
			return array(
				'display'	=> false,
				'item_id'	=> $post_id,
				'remaining'	=> $drip_date - $now,
				'message'	=> self::pending_drip_message( $drip_date, $post_id )
			);
		}

	}

	/**
	 * the display message for when something is pending
	 * @param  integer $drip_date [description]
	 * @param  integer $post_id   [description]
	 * @return [type]             [description]
	 */
	static function pending_drip_message( $drip_date = 0, $post_id = 0 ) {

		// get our date all formatted
		$date	= date( self::date_format(), $drip_date );

		// set a default
		$text	= sprintf( __( 'This content will be available %1$s', 'drippress' ), esc_attr( $date ) );

		// send it back filtered
		return apply_filters( 'dppress_drip_message', $text, $post_id );
	}

	/**
	 * [get_drip_list description]
	 * @param  [type]  $term      [description]
	 * @param  string  $tax       [description]
	 * @param  integer $count     [description]
	 * @param  boolean $available [description]
	 * @param  string  $types     [description]
	 * @return [type]             [description]
	 */
	static function get_drip_list( $term, $tax = 'post_tag', $count = 5, $available = false, $types = 'post' ) {

		// set post types to array if passed
		if ( ! is_array( $types ) ) {
			$types	= explode( ',', $types );
		}

		// set args for query
		$args	= array(
			'fields'			=> 'ids',
			'post_type'			=> $types,
			'posts_per_page'	=> absint( $count ),
			'meta_key'			=> '_dppress_sort',
			'order'				=> 'ASC',
			'orderby'			=> 'meta_value_num',
			'tax_query'			=> array(
				array(
					'taxonomy'	=> $tax,
					'field'		=> 'slug',
					'terms'		=> $term
				),
			)
		);

		// filter args
		$args	= apply_filters( 'dppress_drip_list_args', $args, $term );

		// fetch posts
		$posts	= get_posts( $args );

		// bail if no posts present
		if ( ! $posts ) {
			return;
		}

		// return the IDs
		return $posts;

	}

/// end class
}


// Instantiate our class
new DripPress_Data();
