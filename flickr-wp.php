<?php

class Flickr_WP {

	const URL = "http://api.flickr.com/services/rest/";

	private $api;
	private $id;

	function __construct() {
		$this->api = get_option( 'fancyflickr_api' );
		$this->id = get_option( 'fancyflickr_id' );
	}

	function get_endpoint( $method, $options ) {
		$options_query = '&api_key=' . $this->api;

		foreach ( $options as $option => $value )
			$options_query .= '&' . $option . '=' . $value ;

		$options_query .= '&format=json';

		return self::URL . '?method=' . $method . $options_query;
	}

	function get_results( $method, $options ) {
		$key = md5( $method . serialize( $options ) );
		if ( ! $json = wp_cache_get( $key, 'fancyflickr' ) ) {
			$endpoint = $this->get_endpoint( $method, $options );
			$json = wp_remote_retrieve_body( wp_remote_get( $endpoint ) );
			$json = str_replace( 'jsonFlickrApi(', '', $json );
			$json = substr( $json, 0, -1 );
			wp_cache_set( $key, $json, 'fancyflickr' );
		}
		return json_decode( $json );
	}

	function get_photoset( $id, $num ) {
		return $this->get_results( 'flickr.photosets.getPhotos', array( 'photoset_id' => $id, 'per_page' => $num, 'extras' => "url_sq,url_t,url_m,url_l,url_o" ) )->photoset->photo;
	}

	function get_photosets() {
		return $this->get_results( 'flickr.photosets.getList', array( 'user_id' => $this->id ) )->photosets->photoset;
	}

	function get_recent( $num = false ) {
		if ( !$num ) $num = get_option( 'fancyflickr_num' );
		return $this->get_results( 'flickr.people.getPublicPhotos', array( 'user_id' => $this->id, 'per_page' => $num, 'extras' => "url_sq,url_t,url_m,url_l,url_o" ) )->photos->photo;
	}

	function get_random( $num ) {
		$recent = $this->get_recent( 500 );
		$pics = array();
		for ( $i = 0; $i < $num; $i++ ) {
			$pics[] = $recent[ mt_rand( 0, 499 ) ];
		}
		return $pics;
	}

}