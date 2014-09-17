<?php
/*-----------------------------------------------------------------------------------*/
/*  Create the widget
/*-----------------------------------------------------------------------------------*/
add_action( 'widgets_init', 'wolf_twitter_init' );

function wolf_twitter_init() {

	register_widget( 'wolf_twitter_widget' );
	
}

/*-----------------------------------------------------------------------------------*/
/*  Widget class
/*-----------------------------------------------------------------------------------*/
class wolf_twitter_widget extends WP_Widget {

	/*-----------------------------------------------------------------------------------*/
	/*	Widget Setup
	/*-----------------------------------------------------------------------------------*/
	function wolf_twitter_widget() {
		
		// Widget settings
		$ops = array(
			'classname' => 'wolf-twitter-widget', 
			'description' => __( 'Display your latest tweets', 'wolf' ) );

		// Create the widget
		$this->WP_Widget( 'wolf-twitter-widget', 'Wolf Twitter', $ops );
		
	}

	/*-----------------------------------------------------------------------------------*/
	/*	Display Widget
	/*-----------------------------------------------------------------------------------*/
	function widget($args, $instance) {
		
		extract($args);
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		$username = $instance['username'];
		$count = $instance['count'];

		echo $before_widget;
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;
		echo wolf_twitter_widget( $username, $count );
		echo $after_widget;
	
	}

	/*-----------------------------------------------------------------------------------*/
	/*	Update Widget
	/*-----------------------------------------------------------------------------------*/
	function update($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['title'] = $new_instance['title'];

		$instance['username'] = $new_instance['username'];

		$instance['count'] = $new_instance['count'];
		if ( $instance['count']==0 || $instance['count']=='' ) $instance['count'] = 3;
		if ( $instance['username']=='' ) $instance['username'] = 'username';
		return $instance;
	}

	/*-----------------------------------------------------------------------------------*/
	/*	Displays the widget settings controls on the widget panel
	/*-----------------------------------------------------------------------------------*/
	function form( $instance ) {

			// Set up some default widget settings
			$defaults = array( 'title' => __( 'Twitter Feed', 'wolf' ), 'username' => 'username',  'count' =>'3' );
			$instance = wp_parse_args( (array) $instance, $defaults );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wolf' ); ?>:</label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Your Twitter username', 'wolf' ); ?>:</label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $instance['username']; ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of tweets', 'wolf' ); ?>:</label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>">
		</p>
		<?php
	}

	

}
?>