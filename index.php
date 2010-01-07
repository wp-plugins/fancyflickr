<?php
/*
Plugin Name: FancyFlickr
Plugin URI: http://joshbetz.com/2009/11/fancyflickr/
Description: 
Version: 0.3
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
	global $post;
	
	if(in_the_loop()) {
		add_post_meta($post->ID, '_fancyflickr_recent', def_set(get_option('fancyflickr_api'), get_option('fancyflickr_id')), true);
		$defset = get_post_meta($post->ID, '_fancyflickr_recent', true);
	} else {
		$defset = def_set(get_option('fancyflickr_api'), get_option('fancyflickr_id'));
	}
	
	extract(shortcode_atts(array(
		'type'	=> 'set',
		'set' 	=> $defset,
		'num' 	=> get_option('fancyflickr_num'),
		'smallimage'	=> get_option('fancyflickr_smallimage'),
		'bigimage' => get_option('fancyflickr_bigimage'),
		'columns'	=> intval(get_option('fancyflickr_columns')),
	), $atts));
		
	switch($smallimage) {
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
		$photos = get_image_set(get_option('fancyflickr_api'), get_option('fancyflickr_id'), $set, $num, $smallimage, $bigimage, $columns);
		break;
		case 'random':
		$photos = get_random_images(get_option('fancyflickr_api'), get_option('fancyflickr_id'), $num, $smallimage, $bigimage, $columns);
		break;
	}
	
	return "<div style='max-width:" . $width . "px;' class='fancyflickr'>" . $photos . "<br clear='all' /></div>";
}

// get Photoset
function get_image_set($key, $userid, $set, $num, $smallimage, $bigimage, $columns) {
	$flickr = new flickr($key);
	$pics = $flickr->getPhotosetPhotos($userid, $set, $num);
	$pic = ff_layout($pics['photos'], $smallimage, $bigimage, $columns);
	return $pic;
}

// get Random Images
function get_random_images($key, $userid, $num, $smallimage, $bigimage, $columns) {
	$flickr = new flickr($key);
	$pics = $flickr->flickr_rand($userid, $columns);
	$pic = ff_layout($pics, $smallimage, $bigimage, $columns);
	return $pic;
}

function ff_layout($photos, $smallimage, $bigimage, $columns) {
	switch($smallimage) {
		case 's':
			$style = 'width:50px; padding: 4px;';
		break;
		case 't':
			$style = 'width:80px; padding: 5px;';
		break;
		case 'm':
		default:
			$style = 'width:200px; padding: 10px;';
			$smallimage = 'm';
		break;
	}
	$i = 0;
	foreach($photos as $photo) {
	
	if($bigimage == 'o' && $photo['o_url'] != '') {
		$bigpic = $photo['o_url'];
	} elseif($bigimage == 'o' && $photo['o_url'] == '' && $photo['b_url'] != '') {
		$bigpic = $photo['b_url'];
	} elseif($bigimage == 'b' && $photo['b_url'] != '') {
		$bigpic = $photo['b_url'];
	} else {
		$bigpic = $photo['m_url'];
	}
		
		$pic .= '<div class="column rotated"><a class="polaroid" href="' . $bigpic . '" rel="prettyPhoto[gallery]"><img style="' . $style . '" src="' . $photo["$smallimage"."_url"] . '" alt="'. $photo['title'] . '" /></a></div>'."\r\n";
		
		$i++;
		if(is_int($i/$columns)) $pic .= "<br clear='left' />\r\n";
		
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
	register_setting( 'fancyflickr-group', 'fancyflickr_columns', 'intval' );
	register_setting( 'fancyflickr-group', 'fancyflickr_type' );
	register_setting( 'fancyflickr-group', 'fancyflickr_smallimage' );
	register_setting( 'fancyflickr-group', 'fancyflickr_bigimage' );
	register_setting( 'fancyflickr-group', 'fancyflickr_num', 'intval' );
}

function fancyflickr_menu() {
  add_options_page('Fancy Flickr Options', 'Fancy Flickr', 'administrator', 'fancyflickr-options-page', 'fancyflickr_options');
}

//default options
function fancyflickr_activate() {
	add_option('fancyflickr_columns', '3'); // default value for number of columns
	add_option('fancyflickr_type', 'set'); // default value for default type
	add_option('fancyflickr_smallimage', 'm'); // defeault value for small image size
	add_option('fancyflickr_bigimage', 'o'); // default value for big image size
	add_option('fancyflickr_num', '500'); // default value for big image size
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

		<tr valign="top">
		<th scope="row">Default Number of Photos (max. 500)</th>
		<td><input type="text" name="fancyflickr_num" value="<?php echo get_option('fancyflickr_num'); ?>" /></td>
		</tr>
		
		<tr valign="top">
		<th scope="row">Default Columns</th>
		<td><input type="text" name="fancyflickr_columns" value="<?php echo get_option('fancyflickr_columns'); ?>" /></td>
		</tr>
		
		<tr valign="top">
		<th scope="row">Default Type</th>
		<td><input type="radio" name="fancyflickr_type" value="set" <?php if('set' == get_option('fancyflickr_type')) echo "checked"; ?>> Set
		<input type="radio" name="fancyflickr_type" value="random" <?php if('random' == get_option('fancyflickr_type')) echo "checked"; ?>> Random</td>
		</tr>
		
		<tr valign="top">
		<th scope="row">Default Small Image Size</th>
		<td><input type="radio" name="fancyflickr_smallimage" value="s" <?php if('s' == get_option('fancyflickr_smallimage')) echo "checked"; ?>> Small Square
		<input type="radio" name="fancyflickr_smallimage" value="t" <?php if('t' == get_option('fancyflickr_smallimage')) echo "checked"; ?>> Thumbnail
		<input type="radio" name="fancyflickr_smallimage" value="m" <?php if('m' == get_option('fancyflickr_smallimage')) echo "checked"; ?>> Medium</td>
		</tr>
		
		<tr valign="top">
		<th scope="row">Default Large Image Size</th>
		<td><input type="radio" name="fancyflickr_bigimage" value="m" <?php if('m' == get_option('fancyflickr_bigimage')) echo "checked"; ?>> Medium
		<input type="radio" name="fancyflickr_bigimage" value="b" <?php if('b' == get_option('fancyflickr_bigimage')) echo "checked"; ?>> Large
		<input type="radio" name="fancyflickr_bigimage" value="o" <?php if('o' == get_option('fancyflickr_bigimage')) echo "checked"; ?>> Original</td>
		</tr>
		
	</table>
	
	<?php settings_fields( 'fancyflickr-group' ); ?>
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>
	</div>
	
<?php } ?>