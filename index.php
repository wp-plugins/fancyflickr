<?php
/*
Plugin Name: FancyFlickr
Plugin URI: http://joshbetz.com/2009/11/fancyflickr/
Description: 
Version: 0.1
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
		'set' => def_set(get_option('fancyflickr_api'), get_option('fancyflickr_id')),
		'num' => '500',
	), $atts));
	
	$photos = get_image_set(get_option('fancyflickr_api'), get_option('fancyflickr_id'), $set, $num);
	
	return "<div class='fancyflickr'>" . $photos . "<br clear='all' /></div>";
}

function get_image_set($key, $userid, $set, $num) {
	$flickr = new flickr($key);
	$pics = $flickr->getPhotosetPhotos($userid, $set, $num);
	$photos = $pics['photos'];
	foreach($photos as $photo) {
		$pic .= '<div class="column rotated"><a class="polaroid" href="' . $photo['o_url'] . '" rel="prettyPhoto[gallery]"><img src="' . $photo['m_url'] . '" alt="'. $photo['title'] . '" /></a></div>'."\r\n";
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
	.fancyflickr { margin: 1em auto; display: block; max-width: 720px; }
	.column { float: left; margin-right: 10px; padding: 0; }
	a.polaroid { -moz-transition: all 0.2s ease-in-out; -webkit-transition: all 0.2s ease-in-out; display: block; background: #fff; padding: 10px; margin:5px; -moz-box-shadow: #ccc 5px 5px 20px; -webkit-box-shadow: #ccc 5px 5px 20px; margin-bottom:1em; }
	a.polaroid img { width: 200px; opacity:0.85; filter:alpha(opacity=85); }
	a.polaroid:hover { -moz-box-shadow: #666 5px 5px 20px; -webkit-box-shadow: #666 5px 5px 20px; }
	a.polaroid:hover img {opacity:1.0; filter:alpha(opacity=100);}
	</style>
<?php 
}

function fancyflickr_pp_miw() { ?>
	<script type="text/javascript" charset="utf-8">
		$(document).ready(function(){
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