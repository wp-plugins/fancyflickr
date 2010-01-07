<?php
/*
Plugin Name: FancyFlickr
Plugin URI: http://joshbetz.com/2009/11/fancyflickr/
Description: 
Version: 0.2.2
Author: Josh Betz
Author URI: http://joshbetz.com
*/

include('addicted_to_flickr/class.flickr.php');

if ( is_admin() ){
  add_action('admin_menu', 'fancyflickr_menu');
  add_action( 'admin_init', 'register_fancyflickr_settings' );
} else {
	// load jQuery from google
	wp_deregister_script('jquery'); 
	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"), false, '1.3.2'); 
	wp_enqueue_script('jquery');
	
	// polaroids JavaScript
	wp_register_script('polaroid', WP_PLUGIN_URL . '/fancyflickr/polaroid.js');
	wp_enqueue_script('polaroid');
	
	// prettyPhoto CSS
    wp_register_style('prettyphoto_css', WP_PLUGIN_URL .'/fancyflickr/prettyPhoto.css', false, '2.5.2', 'screen');
    wp_enqueue_style('prettyphoto_css');

    // prettyPhoto JavaScript
    wp_register_script('prettyphoto', WP_PLUGIN_URL .'/fancyflickr/jquery.prettyPhoto.js', array('jquery'), '2.5.4');
    wp_enqueue_script('prettyphoto');

	// FancyFlickr css
	add_action('wp_head', 'fancyflickr_css');
	
	// prettyPhoto "make it work" script
	add_action('wp_footer', 'fancyflickr_pp_miw');
	
	// setup [fancyflickr]
	add_shortcode('fancyflickr', 'fancyflickr');
}


// [fancyflickr set="SETID" num="NUMOFPICS"]
function fancyflickr($atts = array()) {
	extract(shortcode_atts(array(
		'type'	=> 'set',
		'set' 	=> def_set(get_option('fancyflickr_api'), get_option('fancyflickr_id')),
		'num' 	=> '500',
		'size'	=> 'm',
		'columns'	=> '3',
	), $atts));
	
	switch($size) {
		case 's':
			$width = $columns * 78;
		break;
		case 't':
			$width = $columns * 110;
		break;
		case 'm':
		default:
			$width = $columns * 240;
		break;
	}
	
	switch($type) {
		case 'set':
		$photos = get_image_set(get_option('fancyflickr_api'), get_option('fancyflickr_id'), $set, $num, $size);
		break;
		case 'random':
		$photos = get_random_images(get_option('fancyflickr_api'), get_option('fancyflickr_id'), $num, $size);
		break;
	}
	
	return "<div style='max-width:" . $width . "px;' class='fancyflickr'>" . $photos . "<br clear='all' /></div>";
}

// get Photoset
function get_image_set($key, $userid, $set, $num, $size) {
	$flickr = new flickr($key);
	$pics = $flickr->getPhotosetPhotos($userid, $set, $num);
	$pic = ff_layout($pics['photos'], $size);
	return $pic;
}

// get Random Images
function get_random_images($key, $userid, $num, $size) {
	$flickr = new flickr($key);
	$pics = $flickr->flickr_rand($userid, $num);
	$pic = ff_layout($pics, $size);
	return $pic;
}

function ff_layout($photos, $size) {
	switch($size) {
		case 's':
			$style = 'width:50px; padding: 4px;';
		break;
		case 't':
			$style = 'width:80px; padding: 5px;';
		break;
		case 'm':
		default:
			$style = 'width:200px; padding: 10px;';
			$size = 'm';
		break;
	}
	
	foreach($photos as $photo) {
		if($photo['o_url'] == '') $bigpic = $photo['b_url'];
		else $bigpic = $photo['o_url'];
		$pic .= '<div class="column rotated"><a class="polaroid" href="' . $bigpic . '" rel="prettyPhoto[gallery]"><img style="' . $style . '" src="' . $photo["$size"."_url"] . '" alt="'. $photo['title'] . '" /></a></div>'."\r\n";
	}
	return $pic;
}

function def_set($key, $userid) {
	$flickr = new flickr($key);
	$us = $flickr->getUsersPhotosets($userid);
	$def_set = $us[1]['id'];
	return $def_set;
}

function fancyflickr_css() { ?>
	<style type='text/css'>
	.fancyflickr { margin: 1em auto; display: block; }
	.column { float: left; margin-right: 10px; padding: 0; }
	a.polaroid { -moz-transition: all 0.2s ease-in-out; -webkit-transition: all 0.2s ease-in-out; display: block; background: #fff; margin:5px; -moz-box-shadow: rgba(0,0,0,.25) 5px 5px 20px; -webkit-box-shadow: rgba(0,0,0,.25) 5px 5px 20px; margin-bottom:1em; overflow: hidden; }
	a.polaroid img { opacity:0.85; filter:alpha(opacity=85); }
	a.polaroid:hover { -moz-box-shadow: rgba(0,0,0,.5) 5px 5px 20px; -webkit-box-shadow: rgba(0,0,0,.5) 5px 5px 20px; }
	a.polaroid:hover img {opacity:1.0; filter:alpha(opacity=100);}
	</style>
<?php 
}

function fancyflickr_pp_miw() { ?>
	<script type="text/javascript" charset="utf-8">
		$(function(){
			$("a[rel^='prettyPhoto']").prettyPhoto();
		});
	</script>
<?php }

function register_fancyflickr_settings() {
	register_setting( 'fancyflickr-group', 'fancyflickr_api' );
	register_setting( 'fancyflickr-group', 'fancyflickr_id' );
}

function fancyflickr_menu() {
  add_options_page('Fancy Flickr Options', 'Fancy Flickr', 'administrator', 'fancyflickr-options-page', 'fancyflickr_options');
}

//default options
function fancyflickr_activate() {
}

function fancyflickr_options() { ?>
	
	<div class="wrap">
	<h2>Fancy Flickr</h2>
	
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	
	<table class="form-table">
	
		<tr valign="top">
		<th scope="row">API Key</th>
		<td><input type="text" name="fancyflickr_api" value="<?php echo get_option('fancyflickr_api'); ?>" /></td>
		</tr>
		
		<tr valign="top">
		<th scope="row">User ID</th>
		<td><input type="text" name="fancyflickr_id" value="<?php echo get_option('fancyflickr_id'); ?>" /></td>
		</tr>
		
	</table>
	
	<?php settings_fields( 'fancyflickr-group' ); ?>
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>
	</div>
	
<?php } ?>