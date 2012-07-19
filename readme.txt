=== FancyFlickr ===
Contributors: betzster
Tags: photos, flickr
Requires at least: 3.4
Tested up to: 3.4.1
Stable tag: 1.0

Adds a nice looking gallery from flickr styled with a little CSS3 and jQuery, which degrades nicely in older browsers.

== Description ==

This plugin gets images from your flickr account and displays them in an array styled with a little CSS3 and jQuery, which degrades nicely in older browsers. Also opens photos in the prettyPhoto lightbox.

== Installation ==

1. Upload `fancyflickr` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set your API Key and User ID in the FancyFlickr menu.

== Frequently Asked Questions ==

= How do I use this Plugin? =

FancyFlickr uses the `[fancyflickr]` shortcode to display the FancyFlickr gallery in any post. The full options are `[fancyflickr set="SETID" num="NUMBEROFPICS"]`. Neither of these are required. Without any of the options set it will get the newest gallery with all of the pictures.

== Screenshots ==

1. A FancyFlickr gallery
2. The prettyPhoto lightbox
3. The FancyFlickr options page

== Changelog ==

= 1.0 =
* Rewrite helper to use `wp_http_request` instead of curl
* Use more reliable plugin URL incase it's installed in a non-standard place
* Use WordPress's jQuery instead of Google's
* Cache results in WP_Object_Cache
* Upate prettyPhoto

= 0.3.2 =
* Add defaults for the new options so they don't break anything.

= 0.3.1 =
* Optimize exchange of options throughout the plugin
* Add new classes for thumbnails to differentiate portrait, landscape, and square images
* Fix bug that causes multiple sets to be included in the same prettyPhoto gallery

= 0.3 =
* Update `class.flickr.php` to enable random images
* Add custom field for current set on publish with shortcode the set doesn't have to be defined
* Clear the float after every row to fix alignment problems
* Add new options for default number of photos, columns, and type.
* Add controls for the default small image size and large image size.
* New option: smallimage. Valid Parameters are `s` for a small square, `t` for a thumbnail, `m` for the standard size. Default value: m
* New option: bigimage. Valid Parameters are `m` for medium, `b` for large, `o` for the original size. Default value: o
* Retired option: size no longer exists. Replaced by smallimage

= 0.2.2 =
* Update `class.flickr.php` to reflect the previous change. Now the largest possible image is pulled

= 0.2.1 =
* Fixes an issue where Flickr wouldn't send the URL to the large image

= 0.2 =
* RGBA drop shadows to work on any background
* New option: size. Valid Parameters are `s` for a small square, `t` for a thumbnail, `m` for the standard size. Default value: m
* New option: columns. Sets the number of columns displayed by the plugin. Default value: 3
* New function: `fancyflickr(array('set' => 'setID', 'num' => 500, 'width' => 'm', 'columns' => '3'))`. Gets the photos in the same way as the shortcode. Set the parameters with an array. Default values: set = your latest set, num = 500, width = m, columns = 3

= 0.1 =
* First version. Adds a fancyflickr gallery with the use of the `[fancyflickr]` shortcode