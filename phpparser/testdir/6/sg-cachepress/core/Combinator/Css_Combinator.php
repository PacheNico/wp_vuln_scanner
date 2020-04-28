<?php
namespace SiteGround_Optimizer\Combinator;

use SiteGround_Optimizer\Helper\Helper;
/**
 * SG Css_Combinator main plugin class
 */
class Css_Combinator extends Abstract_Combinator {
	/**
	 * Array containing all styles that will be loaded.
	 *
	 * @since 5.1.0
	 *
	 * @var array Array containing all styles that will be loaded.
	 */
	private $combined_styles_data = array(
		'header' => array(
			'handle'   => 'siteground-optimizer-combined-css-header',
		),
		'footer' => array(
			'handle'   => 'siteground-optimizer-combined-css-footer',
		),
	);

	/**
	 * Array containing all styles that will be loaded.
	 *
	 * @since 5.1.0
	 *
	 * @var array Array containing all styles that will be loaded.
	 */
	private $combined_styles_exclude_list = array(
		'siteground-optimizer-combined-css-header',
		'siteground-optimizer-combined-css-footer',
		'elementor-pro', // Excluded in 5.2.2.
		'elementor-global', // Excluded in 5.2.5.
		'tve_style_family_tve_flt', // Excluded in 5.3.0.
		'siteorigin-widget-icon-font-fontawesome',
		'woocommerce-smallscreen',
		'theme-css',
	);

	/**
	 * The constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->combined_styles_exclude_list = array_merge(
			$this->combined_styles_exclude_list,
			get_option( 'siteground_optimizer_combine_css_exclude', array() )
		);

		// Minify the css files.
		add_action( 'wp_print_styles', array( $this, 'pre_combine_header_styles' ), 10 );
		add_action( 'print_embed_styles', array( $this, 'pre_combine_header_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_header_combined_styles' ) );
		add_action( 'enqueue_embed_scripts', array( $this, 'enqueue_header_combined_styles' ) );
	}

	/**
	 * Enqueue the combined styles in header.
	 *
	 * @since  5.1.0
	 */
	public function enqueue_header_combined_styles() {
		wp_enqueue_style(
			'siteground-optimizer-combined-css-header',
			'/siteground-optimizer-header-style.css',
			array(),
			\SiteGround_Optimizer\VERSION,
			'all'
		);

	}

	/**
	 * Wrapper function for header css combination
	 *
	 * @since  5.1.0
	 */
	public function pre_combine_header_styles() {
		$this->combine_styles( true );
	}

	/**
	 * Combine styles included in header and footer
	 *
	 * @param bool $in_header Whether we should combine header or footer styles.
	 *
	 * @since  5.1.0
	 */
	public function combine_styles( $in_header = false ) {
		global $wp_styles;

		// Bail if the scripts object is empty.
		if ( ! is_object( $wp_styles ) ) {
			return;
		}

		// Combined styles content.
		$content       = array();
		$inline_styles = '';
		$styles        = wp_clone( $wp_styles );

		$styles->all_deps( $styles->queue );

		// Get the combined styles handle.
		$combined_styles_handle = ( true === $in_header ) ? $this->combined_styles_data['header']['handle'] : $this->combined_styles_data['footer']['handle'];

		// Get groups of handles.
		foreach ( $styles->to_do as $handle ) {

			// Get the src host.
			$host = parse_url( $wp_styles->registered[ $handle ]->src, PHP_URL_HOST );

			// Bail if the style is excluded.
			if ( true === $this->is_style_excluded( $in_header, $handle, $styles ) ) {
				$excluded[] = $handle;
				continue;
			}

			$combined[] = $handle;

			// Check for inline styles.
			$item_inline_style = $styles->get_data( $handle, 'after' );

			if ( ! empty( $item_inline_style ) ) {
				// Check for inline styles.
				$inline_styles .= implode( "\n", $item_inline_style );
			}

			$content[ $wp_styles->registered[ $handle ]->src ] = $this->get_content( $wp_styles->registered[ $handle ]->src );

			// Remove the style from registered styles.
			unset( $wp_styles->registered[ $handle ] );
		}

		// Add the inline styles after the combined style.
		wp_add_inline_style( $combined_styles_handle, $inline_styles );

		// Unregister the combined style and return.
		if ( empty( $content ) ) {
			unset( $wp_styles->registered[ $combined_styles_handle ] );
			return;
		}

		$new_file_data = $this->create_temp_file_and_get_url( $content, $combined_styles_handle );

		// Finally change the source to combined style.
		$wp_styles->registered[ $combined_styles_handle ]->src    = $new_file_data['url'];
		$wp_styles->registered[ $combined_styles_handle ]->handle = $new_file_data['handle'];

		// Rewrite the deps of excluded styles.
		$wp_styles = $this->rewrite_deps( $wp_styles, $combined, $excluded, $combined_styles_handle );
	}

	/**
	 * Check if the style is excluded
	 *
	 * @since  5.5
	 *
	 * @param  bool   $location styles location( in header/footer ).
	 * @param  string $handle   style handle.
	 * @param  object $styles  WP_styles object.
	 *
	 * @return boolean          True if the style is excluded, false otherwise.
	 */
	public function is_style_excluded( $location, $handle, $styles ) {
		// Get the excluded styles list.
		$excluded_styles = apply_filters( 'sgo_css_combine_exclude', $this->combined_styles_exclude_list );

		// Get the src host.
		$host = parse_url( $styles->registered[ $handle ]->src, PHP_URL_HOST );

		// If the style is excluded from combination.
		if ( in_array( $handle, $excluded_styles ) ) {
			return true;
		}

		if ( true === $location && $styles->groups[ $handle ] > 0 ) {
			return true;
		}

		if ( false === $location && 0 == $styles->groups[ $handle ] ) {
			return true;
		}

		// If the source is empty.
		if ( empty( $styles->registered[ $handle ]->src ) ) {
			return true;
		}

		// If it's an external style.
		if (
			@strpos( Helper::get_home_url(), $host ) === false &&
			! strpos( $styles->registered[ $handle ]->src, 'wp-includes' )
		) {
			return true;
		}

		// Exclude all elementor styles.
		if ( is_int( strpos( $handle, 'elementor-post-' ) ) ) {
			return true;
		}

		// If it's dynamically generated css.
		if ( pathinfo( $styles->registered[ $handle ]->src, PATHINFO_EXTENSION ) === 'php' ) {
			return true;
		}

		// Do not combine conditional styles.
		if ( ! empty( $styles->registered[ $handle ]->extra['conditional'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Replace all url to full urls.
	 *
	 * @since  5.1.0
	 *
	 * @param  string $contents Array with link to styles and style content.
	 *
	 * @return string       Content with replaced urls.
	 */
	public function get_content_with_replacements( $contents ) {
		// Set the new content var.
		$new_content = array();

		foreach ( $contents as $url => $content ) {
			$dir = trailingslashit( dirname( $url ) );

			$content = $this->check_for_imports( $content, $url );

			// Remove source maps urls.
			$content = preg_replace(
				'~(\/\/|\/\*)(#|@)\s(sourceURL|sourceMappingURL)=(.*)(\*\/)?$~',
				'',
				$content
			);

			$regex = '/url\s*\(\s*(?!["\']?data:)(?![\'|\"]?[\#|\%|])([^)]+)\s*\)([^;},\s]*)/i';

			$replacements = array();

			preg_match_all( $regex, $content, $matches );

			if ( ! empty( $matches ) ) {
				foreach ( $matches[1] as $index => $match ) {
					$match = trim( $match, " \t\n\r\0\x0B\"'" );

					// Bail if the url is valid.
					if ( false == preg_match( '~(http(?:s)?:)?\/\/(?:[\w-]+\.)*([\w-]{1,63})(?:\.(?:\w{3}|\w{2}))(?:$|\/)~', $match ) ) {
						$replacement = str_replace( $match, $dir . $match, $matches[0][ $index ] );

						$replacements[ $matches[0][ $index ] ] = $replacement;
					}
				}
			}

			$keys = array_map( 'strlen', array_keys( $replacements ) );
			array_multisort( $keys, SORT_DESC, $replacements );

			$new_content[] = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
		}

		return implode( "\n", $new_content );
	}

	/**
	 * Check for imports in the files and get the import content.
	 *
	 * @since  5.4.5
	 *
	 * @param  string $content The file content.
	 * @param  string $url     The url to the file.
	 *
	 * @return string          Original content + content from import clause.
	 */
	private function check_for_imports( $content, $url ) {
		// Get the file dir.
		$dir = trailingslashit( dirname( $url ) );
		// Check for imports in the style.
		preg_match_all( '/@import\s+["\'](.+?)["\']/i', $content, $matches );

		// Return the content if there are no matches.
		if ( empty( $matches ) ) {
			return $content;
		}

		// Loop through all matches and get the imported css.
		foreach ( $matches[1] as $match ) {
			$import_content = $this->get_content_with_replacements(
				array(
					$url => $this->get_content( $dir . $match ),
				)
			);

			// Replace the @import with the css.
			$content = str_replace( $matches[0], $import_content, $content );
		}

		// Finally return the content.
		return $content;
	}
}
