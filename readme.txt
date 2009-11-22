=== FancyFlickr ===
Contributors: betzster
Tags: photos, flickr
Requires at least: 2.8
Tested up to: 2.8.6
Stable tag: 0.1

Adds a nice looking gallery from flickr styled with a little CSS3 and jQuery, which degrades nicely in older browsers.

== Description ==

This plugin gets images from your flickr account and displays them in an array styled with a little CSS3 and jQuery, which degrades nicely in older browsers. Also opens photos in the prettyPhoto lightbox.

== Installation ==

1. Upload `fancyflickr` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set your API Key and User ID in the FancyFlickr menu.

== Frequently Asked Questions ==

= How do I use this Plugin? =

FancyFlickr uses the `[fancyflickr]` shortcode to display the FancyFlickr gallery in any post. The full options are `[fancyflickr set="SETID" num="NUMBEROFPICS"]`. Neither of these are required. Without any of the options set it will get the newest gallery with all of the pictures, but be careful because if you don't set a `setid` the newest set will be displayed in that post when you create a new one. The highest value that will be accepted by `num` is 500 because that's the most pictures that the Flickr API will return.

== Changelog ==

= 0.1 =
* First version. Adds a fancyflickr gallery with the use of the `[fancyflickr]` shortcode.