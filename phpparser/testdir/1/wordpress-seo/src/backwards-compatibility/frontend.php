<?php
/**
 * Backwards compatibility class for WPSEO_Frontend.
 *
 * @package Yoast\YoastSEO\Backwards_Compatibility
 */

use Yoast\WP\SEO\Initializers\Initializer_Interface;
use Yoast\WP\SEO\Memoizers\Meta_Tags_Context_Memoizer;
use Yoast\WP\SEO\Presenters\Canonical_Presenter;
use Yoast\WP\SEO\Presenters\Meta_Description_Presenter;
use Yoast\WP\SEO\Presenters\Rel_Next_Presenter;
use Yoast\WP\SEO\Presenters\Rel_Prev_Presenter;
use Yoast\WP\SEO\Presenters\Robots_Presenter;

/**
 * Class WPSEO_Frontend
 *
 * @codeCoverageIgnore Because of deprecation.
 */
class WPSEO_Frontend implements Initializer_Interface {
	/**
	 * Instance of this class.
	 *
	 * @var WPSEO_Frontend
	 */
	public static $instance;

	/**
	 * The memoizer for the meta tags context.
	 *
	 * @var Meta_Tags_Context_Memoizer
	 */
	private $context_memoizer;

	/**
	 * The WPSEO Replace Vars object.
	 *
	 * @var WPSEO_Replace_Vars
	 */
	private $replace_vars;

	/**
	 * @inheritDoc
	 */
	public function initialize() {
		self::$instance = $this;
	}

	/**
	 * @inheritDoc
	 */
	public static function get_conditionals() {
		return [];
	}

	/**
	 * WPSEO_Breadcrumbs constructor.
	 *
	 * @param Meta_Tags_Context_Memoizer $context_memoizer The context memoizer.
	 * @param \WPSEO_Replace_Vars        $replace_vars     The replace vars helper.
	 */
	public function __construct(
		Meta_Tags_Context_Memoizer $context_memoizer,
		WPSEO_Replace_Vars $replace_vars
	) {
		$this->context_memoizer = $context_memoizer;
		$this->replace_vars     = $replace_vars;
	}

	/**
	 * Catches call to methods that don't exist and might deprecated.
	 *
	 * @param string $method    The called method.
	 * @param array  $arguments The given arguments.
	 */
	public function __call( $method, $arguments ) {
		_deprecated_function( $method, 'WPSEO 14.0' );

		$title_methods = [
			'title',
			'fix_woo_title',
			'get_content_title',
			'get_seo_title',
			'get_taxonomy_title',
			'get_author_title',
			'get_title_from_options',
			'get_default_title',
			'force_wp_title',
		];
		if ( in_array( $method, $title_methods, true ) ) {
			return $this->get_title();
		}

		return null;
	}

	/**
	 * Retrieves an instance of the class.
	 *
	 * @return static The instance.
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Outputs the canonical value.
	 *
	 * @param bool $echo        Whether or not to output the canonical element.
	 * @param bool $un_paged    Whether or not to return the canonical with or without pagination added to the URL.
	 * @param bool $no_override Whether or not to return a manually overridden canonical.
	 *
	 * @return string|void
	 */
	public function canonical( $echo = true, $un_paged = false, $no_override = false ) {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context = $this->context_memoizer->for_current_page();
		if ( ! $echo ) {
			return $context->presentation->canonical;
		}

		$canonical_presenter = new Canonical_Presenter();
		$canonical_presenter->presentation = $context->presentation;
		echo $canonical_presenter->present();
	}

	/**
	 * Retrieves the meta robots value.
	 *
	 * @return string
	 */
	public function get_robots() {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context = $this->context_memoizer->for_current_page();

		return $context->presentation->robots;
	}

	/**
	 * Outputs the meta robots value.
	 */
	public function robots() {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context   = $this->context_memoizer->for_current_page();
		$presenter = new Robots_Presenter();
		$presenter->presentation = $context->presentation;
		echo $presenter->present();
	}

	/**
	 * Determine $robots values for a single post.
	 *
	 * @param array $robots  Robots data array.
	 * @param int   $post_id The post ID for which to determine the $robots values, defaults to current post.
	 *
	 * @return array
	 */
	public function robots_for_single_post( $robots, $post_id = 0 ) {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context = $this->context_memoizer->for_current_page();

		return $context->presentation->robots;
	}

	/**
	 * Used for static home and posts pages as well as singular titles.
	 *
	 * @param object|null $object If filled, object to get the title for.
	 *
	 * @return string The content title.
	 */
	private function get_title( $object = null ) {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context = $this->context_memoizer->for_current_page();
		$title   = $context->presentation->title;

		return $this->replace_vars->replace( $title, $context->presentation->source );
	}

	/**
	 * This function adds paging details to the title.
	 *
	 * @param string $sep         Separator used in the title.
	 * @param string $seplocation Whether the separator should be left or right.
	 * @param string $title       The title to append the paging info to.
	 *
	 * @return string
	 */
	public function add_paging_to_title( $sep, $seplocation, $title ) {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		return $title;
	}

	/**
	 * Add part to title, while ensuring that the $seplocation variable is respected.
	 *
	 * @param string $sep         Separator used in the title.
	 * @param string $seplocation Whether the separator should be left or right.
	 * @param string $title       The title to append the title_part to.
	 * @param string $title_part  The part to append to the title.
	 *
	 * @return string
	 */
	public function add_to_title( $sep, $seplocation, $title, $title_part ) {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		if ( 'right' === $seplocation ) {
			return $title . $sep . $title_part;
		}

		return $title_part . $sep . $title;
	}

	/**
	 * Adds 'prev' and 'next' links to archives.
	 *
	 * @link http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
	 */
	public function adjacent_rel_links() {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context = $this->context_memoizer->for_current_page();

		$rel_prev_presenter = new Rel_Prev_Presenter();
		$rel_prev_presenter->presentation = $context->presentation;
		echo $rel_prev_presenter->present();

		$rel_next_presenter = new Rel_Next_Presenter();
		$rel_next_presenter->presentation = $context->presentation;
		echo $rel_next_presenter->present();
	}

	/**
	 * Outputs the meta description element or returns the description text.
	 *
	 * @param bool $echo Echo or return output flag.
	 *
	 * @return string
	 */
	public function metadesc( $echo = true ) {
		_deprecated_function( __METHOD__, 'WPSEO 14.0' );

		$context = $this->context_memoizer->for_current_page();

		if ( ! $echo ) {
			return $context->presentation->meta_description;
		}

		$presenter = new Meta_Description_Presenter();
		$presenter->presentation = $context->presentation;
		$presenter->present();
	}
}
