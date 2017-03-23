<?php

/**
 * Links model for use when using one domain per language
 * for example mysite.com/sth and mysite.fr/qqch
 * implements the "links_model interface"
 *
 * @since 1.2
 */
class PLL_Links_Domain extends PLL_Links_Abstract_Domain {

	/**
	 * Constructor
	 *
	 * @since 1.8
	 *
	 * @param object $model PLL_Model instance
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		$this->hosts = $this->get_hosts();

		$this->check_if_host_has_language();

		 // Filrer the site url ( mainly to get the correct login form )
		 add_filter( 'site_url', array( $this, 'site_url' ) );
	}

	private function check_if_host_has_language() {
		$language_found = false;
		$current_host = $_SERVER['HTTP_HOST'];

		// check if the host is in our list of hosts
		foreach ( $this->hosts as $lang_host ) {
			if ( $lang_host == $current_host ) {
				$language_found = true;
			}
		}

		if ( ! $language_found ) {
			// get default host
			$default_lang_slug = $this->options['default_lang'];
			$default_host = $this->hosts[ $default_lang_slug ];

			// redirect to homepage of default language
			wp_redirect( '//' . $default_host, 301 );
			exit();
		}
	}


	/**
	 * Adds the language code in url
	 * links_model interface
	 *
	 * @since 1.2
	 *
	 * @param string $url  url to modify
	 * @param object $lang language
	 * @return string modified url
	 */
	public function add_language_to_link( $url, $lang ) {
		if ( ! empty( $lang ) && ! empty( $this->hosts[ $lang->slug ] ) ) {
			$url = preg_replace( '#:\/\/(' . parse_url( $this->home, PHP_URL_HOST ) . ')($|\/.*)#', '://' . $this->hosts[ $lang->slug ] . "$2", $url );
		}
		return $url;
	}

	/**
	 * Returns the url without language code
	 * links_model interface
	 *
	 * @since 1.2
	 *
	 * @param string $url url to modify
	 * @return string modified url
	 */
	public function remove_language_from_link( $url ) {
		if ( ! empty( $this->hosts ) ) {
			$url = preg_replace( '#:\/\/(' . implode( '|', $this->hosts ) . ')($|\/.*)#', '://' . parse_url( $this->home, PHP_URL_HOST ) . "$2", $url );
		}
		return $url;
	}

	/**
	 * Returns the language based on language code in url
	 * links_model interface
	 *
	 * @since 1.2
	 * @since 2.0 add $url argument
	 *
	 * @param string $url optional, defaults to current url
	 * @return string language slug
	 */
	public function get_language_from_url( $url = '' ) {
		$host = empty( $url ) ? $_SERVER['HTTP_HOST'] : parse_url( $url, PHP_URL_HOST );
		return ( $lang = array_search( $host , $this->hosts ) ) ? $lang : '';
	}

	/**
	 * Returns the home url
	 * links_model interface
	 *
	 * @since 1.3.1
	 *
	 * @param object $lang PLL_Language object
	 * @return string
	 */
	function home_url( $lang ) {
		return trailingslashit( empty( $this->options['domains'][ $lang->slug ] ) ? $this->home : $this->options['domains'][ $lang->slug ] );
	}

	/**
	 * Get hosts managed on the website
	 *
	 * @since 1.5
	 *
	 * @return array list of hosts
	 */
	public function get_hosts() {
		$hosts = array();
		foreach ( $this->options['domains'] as $lang => $domain ) {
			$hosts[ $lang ] = parse_url( $domain, PHP_URL_HOST );
		}
		return $hosts;
	}
}
