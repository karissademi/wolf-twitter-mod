<?php
/*-----------------------------------------------------------------------------------

	Plugin Name: Wolf Twitter
	Plugin URI: http://wpwolf.com/plugin/wolf-twitter
	Description: A widget to display your Twitter Feed
	Version: 2.0.5
	Author: Constantin Saguin
	Author URI: http://wpwolf.com/about

-----------------------------------------------------------------------------------*/

class Wolf_Twitter {

	var $update_url = 'http://plugins.wpwolf.com/update';
	var $cache_duration_hour = 2; // cache duration in hour (can be decimal e.g : 1.5)

	function __construct() {

		define( 'WOLF_TWITTER_URL', plugins_url( '/' . basename( dirname( __FILE__ ) ) ) );
		define( 'WOLF_TWITTER_DIR', dirname( __FILE__ ) );

		// Require widget script
		include_once WOLF_TWITTER_DIR . '/wolf-twitter-widget.php';

		// Load plugin text domain
		add_action( 'init', array( $this, 'plugin_textdomain' ) );
		
		// shortcode
		add_shortcode( 'wolf_tweet', array( $this, 'shortcode') );
		
		// styles
		add_action( 'wp_print_styles', array( $this, 'print_styles' ) );

		add_action( 'admin_init', array( $this, 'plugin_update' ) );


	}

	// --------------------------------------------------------------------------

	/**
	 * Loads the plugin text domain for translation
	 */
	function plugin_textdomain() {

		$domain = 'wolf';
		$locale = apply_filters( 'wolf', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	}

	// --------------------------------------------------------------------------

	/**
	 * Print twitter styles
	 */
	function print_styles() {
		wp_register_style( 'wolf-twitter', WOLF_TWITTER_URL . '/css/twitter.css', array(), '0.1', 'all' );
		wp_enqueue_style( 'wolf-twitter' );
	}


	// --------------------------------------------------------------------------

	/**
	 * Display an error
	 */
	function twitter_error( $username, $list = false ) {

		$error_message = sprintf( __( 'Our Twitter feed is currently unavailable but you can visit our official twitter page  <a href="%1s" target="_blank">%2s</a>.', 'wolf' ), "http://twitter.com/$username", "@$username"  );
			
		if ( $list ) {

			$output = "<ul class=\"wolf-tweet-list\"><li>$error_message</li></ul>";

		} else {

			$output = "<div class=\"wolf-bigtweet-content\"><span class=\"wolf-tweet-text\" style=\"font-size:14px\">$error_message</span></div>";

		}


		return $output;

	}

	// --------------------------------------------------------------------------

	/**
	 * Get the Twitter feed 
	 */
	function get_twitter_feed( $username ) {

		$trans_key = 'wolf_twitter_'.$username;
		// 
		$url = "http://twitter.wpwolf.com/username/$username";
		$cache_duration = ceil( $this->cache_duration_hour*3600 );
		if ( $cache_duration < 3600 )
			$cache_duration = 3600;

		if ( false === ( $cached_data = get_transient( $trans_key ) ) ) {

			if ( function_exists( 'curl_init') ) {
				$c = curl_init( $url);
				curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $c, CURLOPT_HEADER, 0 );
				curl_setopt( $c, CURLOPT_TIMEOUT, 10 );
				$data = curl_exec( $c );
				curl_close( $c );
			} else {
				$data = file_get_contents( $url );
			}
			
			if ( $data ) {
				set_transient( $trans_key, $data, $cache_duration ); 
			}
		} else {

			$data = $cached_data;
		}
			
		return  json_decode( $data );
	} 

	// --------------------------------------------------------------------------  

	/**
	 * Display tweets as list or single tweet 
	 */
	function twitter( $username, $count = 3, $list = true ) {
		
		$tweet ='';
		$data = $this->get_twitter_feed( $username );

		if ( $data && is_array( $data ) ) {
			/* Display as list */
			if ( $count > 1) {
				if ( isset( $data[0] ) ) {
					$tweet .= "<ul class=\"wolf-tweet-list\">"; 
					for ( $i=0; $i<$count; $i++ ) {
						if ( isset( $data[$i] ) ) {
							$content = $data[$i]->text;
							$created = $data[$i]->created_at;
							$id = $data[$i]->id_str;
							$tweet_link = "https://twitter.com/$username/statuses/$id";
							
							$tweet .= "<li>";
							$tweet .= "<span class=\"wolf-tweet-time\"><a href=\"$tweet_link\" target=\"_blank\">".__( 'about', 'wolf' )." ". $this->twitter_time_ago( $created )."</a></span>";    
							$tweet .= "<span class=\"wolf-tweet-text\">".$this->twitter_to_link( $content )."</span>";
							$tweet .= "</li>";
						}
					}
					$tweet .= "</ul>";
				} else {
					$tweet = $this->twitter_error( $username, $list );
				}
			
			/* Display as single tweet */
			} else {
				if ( isset( $data[0] ) ) {
					$content = $data[0]->text;
					$created = $data[0]->created_at;
					$id = $data[0]->id_str;
					$tweet_link = "https://twitter.com/$username/statuses/$id";

					$tweet .= "<div class=\"wolf-bigtweet-content\"><span class=\"wolf-tweet-text\">". $this->twitter_to_link( $content )."</span>";
					$tweet .= "<br><span class=\"wolf-tweet-time_big\"><a href=\"$tweet_link\" target=\"_blank\">".__( 'about', 'wolf' )." ". $this->twitter_time_ago( $created )."</a> 
					<span class=\"wolf-tweet-separator\">|</span> <a href=\"http://twitter.com/$username/\" target=\"_blank\">@$username</a></span></div>"; 
				} else {
					$tweet = $this->twitter_error( $username, $list );
				}
			}

		} else {
			$tweet = $this->twitter_error( $username, $list );
		}
		
		return $tweet;
	}

	// --------------------------------------------------------------------------


	/**
	 * Find url strings, tags and username strings and make them as link
	 */
	function  twitter_to_link( $text ) {
		
		// Match URLs
		$text = preg_replace( '/(^|[^=\"\/])\b((?:\w+:\/\/|www\.)[^\s<]+)((?:\W+|\b)(?:[\s<]|$))/m', '<a href="$0" target="_blank">$0</a>', $text);

		// Match @name
		$text = preg_replace( '/(@)([a-zA-ZÀ-ú0-9\_]+)/', '<a href="https://twitter.com/$2" target="_blank">@$2</a>', $text);

		// Match #hashtag
		$text = preg_replace( '/(#)([a-zA-ZÀ-ú0-9\_]+)/', '<a href="https://twitter.com/search/?q=$2" target="_blank">#$2</a>', $text);
		
		return $text;
	}


	// --------------------------------------------------------------------------
		    
	/**
	 * Convert the twitter date to "X ago" type
	 */        
	function   twitter_time_ago( $date ) {
		$h         = date( "H", strtotime( $date ) );
		$m         = date( "i", strtotime( $date ) );
		$s         = date( "s", strtotime( $date ) );
		$d         = date( "d", strtotime( $date ) );
		$m         = date( "m", strtotime( $date ) );
		$y         = date( "Y", strtotime( $date ) );
		$timestamp = mktime( $h,$m,$s,$m,$d,$y);
		$stf       = 0;
		$cur_time  = time();
		$diff      = $cur_time - $timestamp;
		$phrase = array(__( 'second', 'wolf' ),__( 'minute', 'wolf' ),__( 'hour', 'wolf' ),__( 'day', 'wolf' ),__( 'week', 'wolf' ),__( 'month', 'wolf' ),__( 'year', 'wolf' ),__( 'decade', 'wolf' ) );
		$length = array(1,60,3600,86400,604800,2630880,31570560,315705600 );
		for( $i =sizeof( $length ) - 1; ( $i >=0 )&&( ( $no =  $diff/$length[$i] )<=1); $i--); if ( $i < 0 ) $i=0; $_time = $cur_time  -( $diff%$length[$i] );
		$no = floor( $no); if ( $no <> 1) $phrase[$i] .='s'; $value=sprintf( "%d %s ",$no,$phrase[$i] );
		if ( ( $stf == 1 ) && ( $i >= 1)&&( ( $cur_tm-$_time) > 0 ) ) $value .= time_ago( $_time);
		return $value.' '.__( 'ago', 'wolf' );
	} 

	// --------------------------------------------------------------------------


	/**
	 * Shortcode
	 */ 
	function shortcode($atts) {

		extract(shortcode_atts(array(
			'username'  => 'wp_wolf',
			'count'	=> '2'
		), $atts) );

		return $this->twitter( $username, $count, true);
		//KD edit to allow mutiple tweets in shortcode
		//return $this->twitter( $username, 1, false);
	}

	// --------------------------------------------------------------------------

	/**
	 * Plugin update
	 */
	function plugin_update() {
		
		$plugin_data = get_plugin_data( __FILE__ );

		$current_version = $plugin_data['Version'];
		$plugin_slug = plugin_basename( dirname( __FILE__ ) );
		$plugin_path = plugin_basename( __FILE__ );
		$remote_path = $this->update_url . '/' . $plugin_slug;
		
		if ( ! class_exists( 'Wolf_WP_Update' ) )
			include_once( 'class/class-wp-update.php');
		
		new Wolf_WP_Update( $current_version, $remote_path, $plugin_path );
	}


} // end class

global $wolf_twitter;
$wolf_twitter = new Wolf_Twitter;

// Widget function
function wolf_twitter_widget( $username, $count ) {
	global $wolf_twitter;
	echo $wolf_twitter->twitter( $username, $count , true);
}
?>