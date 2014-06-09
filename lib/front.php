<?php
/**
 * This is all the functionality related to the front end area
 *
 * @return DripPress_Front
 */

class DripPress_Front
{


	/**
	 * This is our constructor
	 *
	 * @return DripPress_Front
	 */
	public function __construct() {

		add_action		(	'widgets_init',					array(	$this,	'load_widget'			)			);
		add_filter		(	'the_content',					array(	$this,	'drip_control'			),	10		);

		add_shortcode	(	'drippress',					array(	$this,	'drippress_display'		)			);


	}

	/**
	 * [load_widget description]
	 * @return [type] [description]
	 */
	public function load_widget() {
		register_widget( 'DPPress_Tag_Widget' );
		register_widget( 'DPPress_Cat_Widget' );
	}

	/**
	 * run our various checks for drips
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function drip_control( $content ) {

		// fetch the global $post object
		global $post;
		$post_id	= absint( $post->ID );

		// get our post types
		$types	= DripPress_Data::types();

		// bail if set to none or not in our array
		if ( ! $types || empty( $types ) || ! in_array( get_post_type( $post_id ), $types ) ) {
			return $content;
		}

		// check for live flag
		$live	= get_post_meta( $post_id, '_dppress_live', true );

		// bail if not set
		if ( ! $live || empty( $live ) ) {
			return $content;
		}

		// run our check
		$check	= DripPress_Data::drip_date_compare( $post_id );

		// if we have a false return (which means missing data) then just return the content
		if ( ! $check ) {
			return $content;
		}

		// check for empty display and a message
		if ( empty( $check['display'] ) && ! empty( $check['message'] ) ) {
			return wpautop( $check['message'] );
		}

		// just bail?
		return $content;

	}

	/**
	 * display weekly list
	 * @param  [type] $atts [description]
	 * @return [type]       [description]
	 */
	public function drippress_display( $atts, $content = null ) {

		extract(shortcode_atts( array(
			'term'			=> '',
			'tax'			=> 'post_tag',
			'count'			=> 5,
			'available'		=> false,
			'types'			=> 'post'
		), $atts ) );

		// bail if we don't have any term data
		if ( empty( $term ) && empty( $tax ) ) {
			return;
		}

		// make sure we didn't use the name 'tag'
		if ( $tax = 'tag' ) {
			$tax	= 'post_tag';
		}

		// fetch the items
		$items	= DripPress_Data::get_drip_list( $term, $tax, $count, $available, $types );

		// bail if nothing found
		if ( ! $items ) {
			return;
		}

		// fetch the HTML for our list and return it
		return self::drippress_list_html( $items, 'shortcode' );

	}

	/**
	 * [drippress_list_markup description]
	 * @param  array  $items [description]
	 * @param  string $type  [description]
	 * @return [type]        [description]
	 */
	static function drippress_list_html( $items = array(), $type = 'widget' ) {

		// set up our HTML
		$html	= '';

		$html	.= '<ul class="drippress-list drippress-list-' . esc_html( $type ) . '">';
		// loop them for display
		foreach( $items as $item_id ) {

			// fetch some initial data
			$title	= get_the_title( $item_id );
			$link	= get_permalink( $item_id );

			// check our drip status
			$check	= DripPress_Data::drip_date_compare( $item_id );

			// if we have passed the drip check, show the name and link as normal
			if ( ! empty( $check['display'] ) ) {
				$html	.= '<li class="drippress-item drippress-item-show">';
				$html	.= '<a href="' . esc_url( $link ) . '">' . esc_attr( $title ) . '</a>';
				$html	.= '</li>';
			}

			// if we have a false display, show the message without a link
			if ( empty( $check['display'] ) && ! empty( $check['message'] ) ) {
				$html	.= '<li class="drippress-item drippress-item-delay">';
				$html	.= esc_attr( $check['message'] );
				$html	.= '</li>';
			}
		// close loop
		}

		$html	.= '</ul>';

		// send it back
		return $html;

	}

/// end class
}


// Instantiate our class
new DripPress_Front();


/**
 * widget for displaying the ordered drip content
 *
 * @since 1.0
 */
class DPPress_Tag_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'dppress-tag-widget', 'description' => __( 'Display a list of your dripped content via tags' , 'tqm-author' ) );
		parent::__construct( 'dppress-tag-widget', __( 'DripPress List - Tags', 'drippress' ), $widget_ops );
		$this->alt_option_name = 'dppress-tag-widget';
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );

		// bail if no term was set
		if ( empty( $instance['term'] ) ) {
			return;
		}

		// fetch the count
		$count	= ! empty( $instance['count'] ) ? $instance['count'] : 5;

		// fetch the items
		$items	= DripPress_Data::get_drip_list( $instance['term'], 'post_tag', $count );

		// bail if nothing for the list exists
		if ( empty( $items ) ) {
			return;
		}

		// begin display
		echo $before_widget;

		// display widget title
		$title = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };

		// get our HTML
		echo DripPress_Front::drippress_list_html( $items, 'widget' );

		// close widget
		echo $after_widget;

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']	= strip_tags( $new_instance['title'] );
		$instance['term']	= strip_tags( $new_instance['term'] );
		$instance['count']	= strip_tags( $new_instance['count'] );

		return $instance;
	}

	function form( $instance ) {
		$title	= ! empty( $instance['title'] )	? esc_attr( $instance['title'] )	: '';
		$term	= ! empty( $instance['term'] )	? esc_attr( $instance['term'] )		: '';
		$count	= ! empty( $instance['count'] )	? absint( $instance['count'] )		: 5;

		$term_data	= DripPress_Data::term_items( 'post_tag' );
	?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<?php if ( empty( $term_data ) ) {
				echo __( 'There are no tags available.', 'drippress' );
			} else {
				echo '<label for="'. $this->get_field_id( 'term' ) . '">' . __( 'Term:' ) . '</label>';
				echo '<select class="widefat" id="' . $this->get_field_id( 'term' ) . '" name="' . $this->get_field_name( 'term' ) . '">';
					echo '<option value="">' . __( '(Select)', 'drippress' ) . '</option>';
					foreach( $term_data as $term_item ) {
						// parse out the data we use
						$slug	= $term_item->slug;
						$name	= $term_item->name;
						// make the option
						echo '<option value="' . esc_html( $slug ) . '" '. selected( $term, $slug, false ).'>' . esc_attr( $name ) . '</option>';
					}
				echo '</select>';
			}
			?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Post Count:' ); ?></label>
			<input class="small-text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo $count; ?>" />
		</p>

		<?php

	}


} // end widget class


/**
 * widget for displaying the ordered drip content
 *
 * @since 1.0
 */
class DPPress_Cat_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'dppress-cat-widget', 'description' => __( 'Display a list of your dripped content via category' , 'tqm-author' ) );
		parent::__construct( 'dppress-cat-widget', __( 'DripPress List - Category', 'drippress' ), $widget_ops );
		$this->alt_option_name = 'dppress-cat-widget';
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );

		// bail if no term was set
		if ( empty( $instance['term'] ) ) {
			return;
		}

		// fetch the count
		$count	= ! empty( $instance['count'] ) ? $instance['count'] : 5;

		// fetch the items
		$items	= DripPress_Data::get_drip_list( $instance['term'], 'category', $count );

		// bail if nothing for the list exists
		if ( empty( $items ) ) {
			return;
		}

		// begin display
		echo $before_widget;

		// display widget title
		$title = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };

		// get our HTML
		echo DripPress_Front::drippress_list_html( $items, 'widget' );

		// close widget
		echo $after_widget;

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']	= strip_tags( $new_instance['title'] );
		$instance['term']	= strip_tags( $new_instance['term'] );
		$instance['count']	= strip_tags( $new_instance['count'] );

		return $instance;
	}

	function form( $instance ) {
		$title	= ! empty( $instance['title'] )	? esc_attr( $instance['title'] )	: '';
		$term	= ! empty( $instance['term'] )	? esc_attr( $instance['term'] )		: '';
		$count	= ! empty( $instance['count'] )	? absint( $instance['count'] )		: 5;

		$term_data	= DripPress_Data::term_items( 'category' );
	?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<?php if ( empty( $term_data ) ) {
				echo __( 'There are no tags available.', 'drippress' );
			} else {
				echo '<label for="'. $this->get_field_id( 'term' ) . '">' . __( 'Term:' ) . '</label>';
				echo '<select class="widefat" id="' . $this->get_field_id( 'term' ) . '" name="' . $this->get_field_name( 'term' ) . '">';
					echo '<option value="">' . __( '(Select)', 'drippress' ) . '</option>';
					foreach( $term_data as $term_item ) {
						// parse out the data we use
						$slug	= $term_item->slug;
						$name	= $term_item->name;
						// make the option
						echo '<option value="' . esc_html( $slug ) . '" '. selected( $term, $slug, false ).'>' . esc_attr( $name ) . '</option>';
					}
				echo '</select>';
			}
			?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Post Count:' ); ?></label>
			<input class="small-text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo $count; ?>" />
		</p>

		<?php

	}


} // end widget class