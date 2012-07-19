<?php
/*
Plugin Name: FancyFlickr
Plugin URI: http://joshbetz.com/2009/11/fancyflickr/
Description: 
Version: 1.0
Author: Josh Betz
Author URI: http://joshbetz.com
*/

include( 'flickr-wp.php' );

class FancyFlickr {

	const VERSION = 1.0;

	function __construct() {
		add_action( 'admin_menu',           array( $this, 'admin_menu' ) );
		add_action( 'admin_init',           array( $this, 'admin_init' ) );

		add_action( 'init',                 array( $this, 'init' ) );
		add_action( 'wp_print_scripts',     array( $this, 'print_scripts' ) );
		
		// setup [fancyflickr] shortcode
		add_shortcode( 'fancyflickr',       array( $this, 'fancyflickr' ) );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	function init() {
		wp_register_script( 'fancyflickr_polaroid', plugins_url( '/polaroid.js', __FILE__ ), self::VERSION );
		wp_register_style( 'prettyphoto', plugins_url( '/prettyPhoto.css', __FILE__ ), false, '3.1.4' );
		wp_register_script( 'prettyphoto', plugins_url( '/jquery.prettyPhoto.js', __FILE__ ), array('jquery'), '3,1.4' );

		wp_enqueue_script( 'fancyflickr_polaroid' );
		wp_enqueue_style( 'prettyphoto' );
		wp_enqueue_script( 'prettyphoto' );
	}

	// [fancyflickr set="SETID" num="NUMOFPICS"]
	function fancyflickr($atts = array()) {
		global $post;		
		
		if( in_the_loop() ) {
			// Add a custom field for the newest set
			add_post_meta( $post->ID, '_fancyflickr_recent', $this->default_set(), true );
			// Get the custom field for set
			$defset = get_post_meta( $post->ID, '_fancyflickr_recent', true) ;
		} else {
			// just get the newest set
			$defset = $this->default_set();
		}
		
		extract( shortcode_atts( array(
			'type'       => 'set',
			'set'        => $defset,
			'num'        => get_option( 'fancyflickr_num' ),
			'smallimage' => get_option( 'fancyflickr_smallimage' ),
			'columns'    => intval( get_option( 'fancyflickr_columns' ) ),
		), $atts ) );
		
		//Set default options
		if ( $num == '' ) $num = 20;
		if ( $smallimage == '' ) $smallimage = 'url_m';
		if ( $columns == '' ) $columns = 3;
		
		// Setup the options array
		$options = array (
			'set'        => $set,
			'num'        => $num,
			'smallimage' => $smallimage,
			'columns'    => $columns,
			'type'       => $type
		);
		
		// Helps to center the gallery in the middle of the content
		switch( $smallimage ) {
			case 'url_s':
				$width = $columns * 78;
				break;
			case 'url_t':
				$width = $columns * 110;
				break;
			case 'url_m':
			default:
				$width = $columns * 240;
				break;
		}
		
		// Display the gallery
		return sprintf( "<div style='max-width:%dpx;' class='fancyflickr'>%s<br clear='all' /></div>", $width, $this->get_images( $options ) );
	}

	function get_images( $options ) {
		$flickr = new Flickr_WP();

		switch( $options['type'] ) {
			case 'random':
				$pics = $flickr->get_random( $options['num'] );
				return $this->layout( $pics, $options );
				break;
			case 'set':
			default:
				$pics = $flickr->get_photoset( $options['set'], $options['num'] );
				return $this->layout( $pics, $options );
				break;
		}
	}

	// Global FF layout
	function layout( $photos, $options ) {
		
		// Setup basic thumbnail styling
		switch($options['smallimage']) {
			case 'url_s':
				$style = 'width:50px; padding: 4px;';
				break;
			case 'url_t':
				$style = 'width:80px; padding: 5px;';
				break;
			case 'url_m':
			default:
				$style = 'width:200px; padding: 10px;';
				$options['smallimage'] = 'url_m';
				break;
		}

		$i = 0; $pic = '';
		foreach( $photos as $photo ) {

			list( $width, $height ) = getimagesize( $photo->$options['smallimage'] );
			
			// Setup $ratio_class to differentiate portrait, landscape, and square thumbnails
			if ( $width < $height ) {
				$ratio_class = 'portrait';
			} elseif( $height < $width ) {
				$ratio_class = 'landscape';
			} else {
				$ratio_class = 'square';
			}
			
			// Make each gallery it's own in lightbox
			if( $options['type'] == 'random' ) {
				$gallery_id = 'random';
			} else {
				$gallery_id = $options['set'];
			}
			
			// Make sure we get the largest image that they want
			if ( isset( $photo->url_o ) ) {
				$bigpic = $photo->url_o;
			} elseif ( isset( $photo->url_l ) ) {
				$bigpic = $photo->url_l;
			} else {
				$bigpic = $photo->url_m;
			} 
				
			$pic .= '<div class="column rotated ' . $ratio_class . ' ' . $options['smallimage'] . '_' . $ratio_class . '"><a class="polaroid" href="' . $bigpic . '" rel="prettyPhoto[gallery-'.$gallery_id.']"><img style="' . $style . '" src="' . $photo->$options['smallimage'] . '" alt="'. $photo->title . '" /></a></div>'."\r\n";
				
			$i++;
			if( is_int( $i/$options['columns'] ) ) $pic .= "<br clear='left' />\r\n"; // Clear the float after every row
			
		}
		return $pic;
	}

	function default_set() {
		$flickr = new Flickr_WP();
		$us = $flickr->get_photosets();
		$default_set = $us[0]->id;
		return $default_set;
	}

	function print_scripts() { ?>
		<style type='text/css'>
			.fancyflickr { margin: 1em auto; display: block; }
			.column { float: left; margin-right: 10px; padding: 0; }
			a.polaroid { -moz-transition: all 0.2s ease-in-out; -webkit-transition: all 0.2s ease-in-out; display: block; background: #fff; margin:5px; -moz-box-shadow: rgba(0,0,0,.25) 5px 5px 20px; -webkit-box-shadow: rgba(0,0,0,.25) 5px 5px 20px; margin-bottom:1em; overflow: hidden; }
			a.polaroid img { opacity:0.85; filter:alpha(opacity=85); }
			a.polaroid:hover { -moz-box-shadow: rgba(0,0,0,.5) 5px 5px 20px; -webkit-box-shadow: rgba(0,0,0,.5) 5px 5px 20px; }
			a.polaroid:hover img {opacity:1.0; filter:alpha(opacity=100);}
		</style>
	<?php }

	function admin_init() {
		register_setting( 'fancyflickr-group', 'fancyflickr_api' );
		register_setting( 'fancyflickr-group', 'fancyflickr_id' );
		register_setting( 'fancyflickr-group', 'fancyflickr_columns', 'intval' );
		register_setting( 'fancyflickr-group', 'fancyflickr_type' );
		register_setting( 'fancyflickr-group', 'fancyflickr_smallimage' );
		register_setting( 'fancyflickr-group', 'fancyflickr_num', 'intval' );
	}

	function admin_menu() {
		add_options_page('Fancy Flickr Options', 'Fancy Flickr', 'administrator', 'fancyflickr-options-page', array( $this, 'options') );
	}

	//default options
	function activate() {
		add_option( 'fancyflickr_columns', '3' ); // default value for number of columns
		add_option( 'fancyflickr_type', 'set' ); // default value for default type
		add_option( 'fancyflickr_smallimage', 'url_m' ); // defeault value for small image size
		add_option( 'fancyflickr_num', '500' ); // default value for big image size
	}

	function options() { ?>
		
		<div class="wrap">
		<h2>Fancy Flickr</h2>
		
		<form method="post" action="options.php">
		<?php wp_nonce_field( 'update-options' ); ?>
		
		<table class="form-table">
		
			<tr valign="top">
			<th scope="row">API Key</th>
			<td><input type="text" name="fancyflickr_api" value="<?php echo get_option( 'fancyflickr_api' ); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">User ID</th>
			<td><input type="text" name="fancyflickr_id" value="<?php echo get_option( 'fancyflickr_id' ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">Default Number of Photos (max. 500)</th>
			<td><input type="text" name="fancyflickr_num" value="<?php echo get_option( 'fancyflickr_num' ); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Default Columns</th>
			<td><input type="text" name="fancyflickr_columns" value="<?php echo get_option( 'fancyflickr_columns' ); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Default Type</th>
			<td><input type="radio" name="fancyflickr_type" value="set" <?php if( 'set' == get_option( 'fancyflickr_type' ) ) echo "checked"; ?>> Set
			<input type="radio" name="fancyflickr_type" value="random" <?php if( 'random' == get_option( 'fancyflickr_type' ) ) echo "checked"; ?>> Random</td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Thumbnail Size</th>
			<td><input type="radio" name="fancyflickr_smallimage" value="url_s" <?php if( 'url_s' == get_option( 'fancyflickr_smallimage' ) ) echo "checked"; ?>> Small Square
			<input type="radio" name="fancyflickr_smallimage" value="url_t" <?php if( 'url_t' == get_option( 'fancyflickr_smallimage' ) ) echo "checked"; ?>> Thumbnail
			<input type="radio" name="fancyflickr_smallimage" value="url_m" <?php if( 'url_m' == get_option( 'fancyflickr_smallimage' ) ) echo "checked"; ?>> Medium</td>
			</tr>
			
		</table>
		
		<?php settings_fields( 'fancyflickr-group' ); ?>
		
		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
		</p>
		
		</form>
		</div>
		
	<?php }
}

new FancyFlickr();
