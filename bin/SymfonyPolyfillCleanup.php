<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Util;

use Composer\Script\Event;

/**
 * Utilities to remove PHP 8.0 specific code of Symfony polyfills to make it compatible with woorelease.
 * See issue: https://github.com/woocommerce/google-listings-and-ads/issues/331#issuecomment-838372148
 *
 * This class is based on https://github.com/woocommerce/google-listings-and-ads/blob/develop/bin/GoogleAdsCleanupServices.php
 */
class SymfonyPolyfillCleanup {

	/**
	 * Display debug output.
	 *
	 * @var boolean
	 */
	protected $debug = false;

	/**
	 * @var Event Composer event.
	 */
	protected $event = null;

	/**
	 * @var string The path of the Symfony library.
	 */
	protected $path = null;

	/**
	 * @var array The list of symfony polyfills.
	 */
	protected const LIST_OF_POLYFILLS = [
		'polyfill-intl-normalizer',
		'polyfill-mbstring',
	];


	/**
	 * Constructor.
	 *
	 * @param Event|null  $event Composer event.
	 * @param string|null $path  Path of the Symfony library.
	 */
	public function __construct( Event $event = null, string $path = null ) {
		$this->event = $event;
		$this->path  = $path ?: dirname( __DIR__ ) . '/vendor/symfony/';
	}

	/**
	 * Get list of Sympfony polyfills.
	 *
	 * @return array List of sympfony polyfills.
	 */
	protected function get_list_of_polyfills(): array {
		return self::LIST_OF_POLYFILLS;
	}

	/**
	 * Remove PHP 8.0 specific code of Symfony polyfills
	 *
	 * @param Event $event Event context provided by Composer
	 */
	public static function remove( Event $event = null ) {
		$cleanup = new SymfonyPolyfillCleanup( $event );
		$cleanup->remove_bootstraps80();

	}

	/**
	 * Remove bootstrap80.php files and the conditional statement in bootstrap.php
	 */
	protected function remove_bootstraps80() {
		$this->output_text( 'Removing PHP 8.0 specific code for symfony polyfills' );

		$list_of_polyfills = $this->get_list_of_polyfills();

		foreach ( $list_of_polyfills as $polyfill ) {

			// Search for bootstrap.php
			$bootstraps = $this->find_library_file_pattern( 'bootstrap', $polyfill );
			$bootstrap  = reset( $bootstraps );

			// If the statement is not found, skip removing bootstrap80.php
			if ( ! $this->remove_statement_with_pattern( $bootstrap, 'require .*\/bootstrap80.php', 'if (\PHP_VERSION_ID >= 80000)' ) ) {
				continue;
			}

			// Search for bootstrap80.php
			$bootstraps80 = $this->find_library_file_pattern( 'bootstrap80', $polyfill );
			$bootstrap80  = reset( $bootstraps80 );
			$this->remove_file( $bootstrap80 );
		}
	}


	/**
	 * Remove a statement from a file.
	 *
	 * @param string $file
	 * @param string $inner_pattern The inner pattern for the  statement.
	 * @param string $statement_pattern The statement pattern.
	 * @return bool Returns true if the statement is found otherwise false.
	 */
	protected function remove_statement_with_pattern( string $file, string $inner_pattern, string $statement_pattern ) {
		if ( ! file_exists( $file ) ) {
			$this->warning( sprintf( 'File does not exist: %s', $file ) );
			return false;
		}

		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$pattern = '/' . $inner_pattern . '/';
		if ( ! preg_match( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			$this->warning( sprintf( 'Inner pattern %s not found in %s', $inner_pattern, $file ) );
			return false;
		}

		$offset  = $matches[0][1];
		$length  = strlen( $contents );
		$bracket = 0;

		// Parse until we find beginning of statement pattern.
		$start = strrpos( $contents, $statement_pattern, ( $length - $offset ) * -1 );

		// Parse until we encounter closing bracket.
		for ( $end = $offset; $end < $length; $end++ ) {
			if ( '{' === $contents[ $end ] ) {
				$bracket++;
			}
			if ( '}' === $contents[ $end ] ) {
				$bracket--;
				if ( 1 > $bracket ) {
					break;
				}
			}
		}

		if ( false === $start || 0 > $start || $end >= $length ) {
			$this->warning( sprintf( 'Statement %s not found in %s', $statement_pattern, $file ) );
			return false;
		}

		// Include whitespaces before start.
		while ( 0 < $start && ctype_space( $contents[ $start - 1 ] ) ) {
			$start--;
		}

		$new  = substr( $contents, 0, $start );
		$new .= substr( $contents, $end + 1 );

		if ( empty( $new ) ) {
			$this->warning( sprintf( 'Replace failed for statement %s in %s', $statement_pattern, $file ) );
			return false;
		}

		file_put_contents( $file, $new ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return true;
	}

	/**
	 * Remove a file.
	 *
	 * @param string $file
	 */
	protected function remove_file( string $file ) {
		if ( ! file_exists( $file ) ) {
			$this->warning( sprintf( 'File does not exist: %s', $file ) );
			return;
		}

		unlink( $file );
	}

	/**
	 * Find a specific filename pattern within the polyfill folder.
	 *
	 * @param string $pattern glob pattern to match.
	 * @param string $polyfill Polyfill folder
	 *
	 * @return array List of matched names.
	 */
	protected function find_library_file_pattern( string $pattern, string $polyfill ): array {
		$polyfill_path = $this->file_path( $polyfill );
		$output        = glob( "{$polyfill_path}/{$pattern}.php" );

		if ( empty( $output ) ) {
			return [];
		}

		return $output;
	}


	/**
	 * Return the full path name.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	protected function file_path( string $file ): string {
		return $this->path . $file;
	}

	/**
	 * Output warning text (only if debug is enabled).
	 *
	 * @param string $text
	 */
	protected function warning( string $text ) {
		if ( $this->debug ) {
			$this->output_text( $text );
		}
	}

	/**
	 * Output text.
	 *
	 * @param string $text
	 */
	protected function output_text( string $text ) {
		if ( $this->event ) {
			$this->event->getIO()->write( $text );
		} else {
			echo $text . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
