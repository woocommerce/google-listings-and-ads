<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Util;

/**
 * Generate documentation for hooks in GLA.
 *
 * The code is copied from WooCommerce Code Reference repository:
 * https://github.com/woocommerce/code-reference/blob/trunk/generate-hook-docs.php
 * with slightly changes to fit GLA's code structure.
 *
 * Example: php bin/HooksDocsGenerator.php
 *
 * The markdown output will be generated in `src/Hooks/README.md`.
 */
class HooksDocsGenerator {

	/**
	 * Source path.
	 */
	protected const SOURCE_PATH = 'src/';


	/**
	 * GLA_GITHUB_PATH
	 */
	protected const GLA_GITHUB_PATH = 'https://github.com/woocommerce/google-listings-and-ads/blob/develop/';

	/**
	 * Hooks docs markdown output.
	 */
	protected const HOOKS_MARKDOWN_OUTPUT = './src/Hooks/README.md';

	/**
	 * List of files found.
	 *
	 * @var array
	 */
	protected static $found_files = [];

	/**
	 * Get files.
	 *
	 * @param string $pattern Search pattern.
	 * @param int    $flags   Glob flags.
	 * @param string $path    Directory path.
	 * @return array
	 */
	protected static function get_files( $pattern, $flags = 0, $path = '' ) {
		$dir = dirname( $pattern );
		if ( ! $path && $dir !== '.' ) {
			if ( '\\' === $dir || '/' === $dir ) {
					$dir = '';
			}

			return self::get_files( basename( $pattern ), $flags, $dir . '/' );
		}

		$paths = glob( $path . '*', GLOB_ONLYDIR | GLOB_NOSORT );
		$files = glob( $path . $pattern, $flags );

		if ( is_array( $paths ) ) {
			foreach ( $paths as $p ) {
				$found_files     = [];
				$retrieved_files = (array) self::get_files( $pattern, $flags, $p . '/' );

				foreach ( $retrieved_files as $file ) {
					if ( ! in_array( $file, self::$found_files, true ) ) {
						$found_files[] = $file;
					}
				}

				self::$found_files = array_merge( self::$found_files, $found_files );

				if ( is_array( $files ) && is_array( $found_files ) ) {
					$files = array_merge( $files, $found_files );
				}
			}
		}
		return $files;
	}

	/**
	 * Get files to scan.
	 *
	 * @return array
	 */
	protected static function get_files_to_scan(): array {
		$files = [];

		$files['Src'] = self::get_files( '*.php', GLOB_MARK, self::SOURCE_PATH );
		return array_filter( $files );
	}

	/**
	 * Get hooks.
	 *
	 * @param array $files_to_scan Files to scan.
	 * @return array
	 */
	protected static function get_hooks( array $files_to_scan ): array {
		$scanned = [];
		$results = [];

		foreach ( $files_to_scan as $heading => $files ) {
			$hooks_found = [];

			foreach ( $files as $f ) {
				$current_file     = $f;
				$tokens           = token_get_all( file_get_contents( $f ) );
				$token_type       = false;
				$current_class    = '';
				$current_function = '';

				if ( in_array( $current_file, $scanned, true ) ) {
					continue;
				}

				$scanned[] = $current_file;

				foreach ( $tokens as $index => $token ) {
					if ( is_array( $token ) ) {
						$trimmed_token_1 = trim( $token[1] );
						if ( T_CLASS === $token[0] ) {
							$token_type = 'class';
						} elseif ( T_FUNCTION === $token[0] ) {
							$token_type = 'function';
						} elseif ( 'do_action' === $token[1] ) {
							$token_type = 'action';
						} elseif ( 'apply_filters' === $token[1] ) {
							$token_type = 'filter';
						} elseif ( $token_type && ! empty( $trimmed_token_1 ) ) {
							switch ( $token_type ) {
								case 'class':
									$current_class = $token[1];
									break;
								case 'function':
									$current_function = $token[1];
									break;
								case 'filter':
								case 'action':
									$hook = trim( $token[1], "'" );
									$hook = str_replace( '_FUNCTION_', strtoupper( $current_function ), $hook );
									$hook = str_replace( '_CLASS_', strtoupper( $current_class ), $hook );
									$hook = str_replace( '$this', strtoupper( $current_class ), $hook );
									$hook = str_replace( [ '.', '{', '}', '"', "'", ' ', ')', '(' ], '', $hook );
									$hook = preg_replace( '/\/\/phpcs:(.*)(\n)/', '', $hook );
									$loop = 0;

									// Keep adding to hook until we find a comma or colon.
									while ( 1 ) {
										$loop++;
										$prev_hook = is_string( $tokens[ $index + $loop - 1 ] ) ? $tokens[ $index + $loop - 1 ] : $tokens[ $index + $loop - 1 ][1];
										$next_hook = is_string( $tokens[ $index + $loop ] ) ? $tokens[ $index + $loop ] : $tokens[ $index + $loop ][1];

										if ( in_array( $next_hook, [ '.', '{', '}', '"', "'", ' ', ')', '(' ], true ) ) {
											continue;
										}

										if ( in_array( $next_hook, [ ',', ';' ], true ) ) {
											break;
										}

										$hook_first = substr( $next_hook, 0, 1 );
										$hook_last  = substr( $next_hook, -1, 1 );

										if ( '{' === $hook_first || '}' === $hook_last || '$' === $hook_first || ')' === $hook_last || '>' === substr( $prev_hook, -1, 1 ) ) {
											$next_hook = strtoupper( $next_hook );
										}

										$next_hook = str_replace( [ '.', '{', '}', '"', "'", ' ', ')', '(' ], '', $next_hook );

										$hook .= $next_hook;
									}

									$hook = trim( $hook );

									if ( isset( $hooks_found[ $hook ] ) ) {
										$hooks_found[ $hook ]['files'][] = [
											'path' => $current_file,
											'line' => $token[2],
										];
									} else {
										$hooks_found[ $hook ] = [
											'files'    => [
												[
													'path' => $current_file,
													'line' => $token[2],
												],
											],
											'class'    => $current_class,
											'function' => $current_function,
											'type'     => $token_type,
										];
									}
									break;
							}
							$token_type = false;
						}
					}
				}
			}

			ksort( $hooks_found );

			if ( ! empty( $hooks_found ) ) {
					$results[ $heading ] = $hooks_found;
			}
		}

		return $results;
	}

	/**
	 * Get file URL.
	 *
	 * @param array $file File data.
	 * @return string
	 */
	protected static function get_file_url( array $file ): string {
		$url = str_replace( '.php', '.php#L' . $file['line'], $file['path'] );
		return $url;
	}

	/**
	 * Get file link.
	 *
	 * @param array $file File data.
	 * @return string
	 */
	protected static function get_file_link( array $file ): string {
		return '<a href="' . self::GLA_GITHUB_PATH . self::get_file_url( $file ) . '">' . basename( $file['path'] ) . "#L{$file['line']}" . '</a>';
	}

	/**
	 * Get delimited list output.
	 *
	 * @param array $hook_list List of hooks.
	 * @param array $files_to_scan List of files to scan.
	 */
	protected static function get_delimited_list_output( array $hook_list, array $files_to_scan ): string {
		$output  = "# Hooks Reference\n\n";
		$output .= "A list of hooks, i.e `actions` and `filters`, that are defined or used in this project.\n\n";

		foreach ( $hook_list as $heading => $hooks ) {
			foreach ( $hooks as $hook => $details ) {
				$output   .= "## {$hook}\n\n";
				$output   .= "**Type**: {$details['type']}\n\n";
				$output   .= "**Used in**:\n\n";
				$link_list = [];
				foreach ( $details['files'] as $file ) {
					$link_list[] = '- ' . self::get_file_link( $file );
				}
				$output .= implode( "\n", $link_list );
				$output .= "\n\n";
			}
		}

		return $output;
	}

	/**
	 * Generate hooks documentation.
	 */
	public static function generate_hooks_docs() {
		$files_to_scan = self::get_files_to_scan();
		$hook_list     = self::get_hooks( $files_to_scan );

		if ( empty( $hook_list ) ) {
			return;
		}

		// Add hooks reference content.
		$output = self::get_delimited_list_output( $hook_list, $files_to_scan );

		file_put_contents( self::HOOKS_MARKDOWN_OUTPUT, $output );
	}
}

HooksDocsGenerator::generate_hooks_docs();
