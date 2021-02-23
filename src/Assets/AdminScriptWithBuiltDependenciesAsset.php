<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray as DependencyArray;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminScriptWithBuiltDependenciesAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
class AdminScriptWithBuiltDependenciesAsset extends AdminScriptAsset {

	/**
	 * ScriptHelper constructor.
	 *
	 * @param string          $handle The script handle.
	 * @param string          $uri    The URI for the script.
	 * @param string          $build_dependency_path
	 * @param DependencyArray $fallback_dependency_data
	 * @param bool            $in_footer
	 */
	public function __construct(
		string $handle,
		string $uri,
		string $build_dependency_path,
		DependencyArray $fallback_dependency_data,
		bool $in_footer = true
	) {
		$dependency_data = $this->get_dependency_data( $build_dependency_path, $fallback_dependency_data );
		parent::__construct(
			$handle,
			$uri,
			$dependency_data->get_dependencies(),
			$dependency_data->get_version(),
			$in_footer
		);
	}

	/**
	 * Get usable dependency data from an asset path or from the fallback.
	 *
	 * @param string          $build_dependency_path
	 * @param DependencyArray $fallback
	 *
	 * @return DependencyArray
	 */
	protected function get_dependency_data(
		string $build_dependency_path,
		DependencyArray $fallback
	): DependencyArray {
		try {
			if ( ! is_readable( $build_dependency_path ) ) {
				return $fallback;
			}

			return new DependencyArray( include $build_dependency_path );
		} catch ( Throwable $e ) {
			do_action( 'gla_exception', $e, __METHOD__ );
			return $fallback;
		}
	}
}
