#!/usr/bin/env php
<?php
declare( strict_types=1 );

// Disable phpcs rules which are not relevant to a dev script.
// phpcs:disable WordPress.Security.EscapeOutput
// phpcs:disable WordPress.WP.AlternativeFunctions

/*
 * List of packages to prefix the namespace.
 *
 * namespace        = Namespace to search for.
 * extra_namespaces = Additional namespaces to search and prefix.
 * package          = Full name of package in the vendor folder.
 * strict           = When true it will search matching the full namespace, false will allow matching of a namespace prefix.
 * exclude_autoload = Array of autoload files which are not required to be included in the main composer file.
 */
$packages = [
	[
		'namespace' => 'League\\Container',
		'package'   => 'league/container',
		'strict'    => false,
	],
	[
		'namespace' => 'League\\ISO3166',
		'package'   => 'league/iso3166',
		'strict'    => false,
	],
	[
		'namespace'        => 'Google',
		'extra_namespaces' => [
			'Google\\AuthHandler',
			'Google\\AccessToken',
			'Google\\Http',
			'Google\\Service',
			'Google\\Task',
			'Google\\Utils',
		],
		'package'          => 'google/apiclient',
		'strict'           => true,
		'exclude_autoload' => [ 'src/aliases.php' ],
	],
	[
		'namespace' => 'Google\\Auth',
		'package'   => 'google/auth',
		'strict'    => false,
	],
	[
		'namespace' => 'GuzzleHttp',
		'package'   => 'guzzlehttp',
		'strict'    => false,
	],
];

$vendor_dir       = dirname( __DIR__ ) . '/vendor';
$namespace_prefix = 'Automattic\\WooCommerce\\GoogleListingsAndAds\\Vendor';

// Vendor libraries which are dependent on a library we are prefixing.
$dependencies = [
	'google/apiclient' => [
		'google/apiclient-services',
	],
	'google/auth'      => [
		'google/apiclient',
		'google/gax',
	],
	'guzzlehttp'       => [
		'google/apiclient',
		'google/auth',
		'google/gax',
	],
];

// Namespaces which are used directly within the code.
$direct_replacements = [
	'guzzlehttp' => [
		'GuzzleHttp\Client(',
		'GuzzleHttp\ClientInterface::MAJOR_VERSION',
		'GuzzleHttp\ClientInterface::VERSION',
		'GuzzleHttp\describe_type',
		'GuzzleHttp\HandlerStack::create',
		'GuzzleHttp\Message\ResponseInterface)',
		'GuzzleHttp\Promise\promise_for',
		'GuzzleHttp\Psr7\Message::bodySummary',
		'GuzzleHttp\Psr7\Utils::streamFor',
		'GuzzleHttp\Psr7\Utils::tryFopen',
	],
];

// Read our composer.json file into an array.
$composer_json = json_decode( file_get_contents( dirname( __DIR__ ) . '/composer.json' ), true );

foreach ( $packages as $package ) {
	// Process namespaces and uses in all package files.
	foreach ( find_files( $package['package'] ) as $file ) {
		process_file( $file, $package, true, true );
	}

	// Process only uses in dependent package files.
	foreach ( find_dependent_files( $package['package'] ) as $file ) {
		process_file( $file, $package, false, true );
	}

	// Prefix autoloads and remove file autoloads from vendor/composer/installed.json
	update_autoloads(
		"{$vendor_dir}/composer/installed.json",
		$composer_json['autoload']['files'] ?? [],
		$package
	);
}


/**
 * Process prefixing in a specific file.
 *
 * @since x.x.x
 *
 * @param string $file             File name.
 * @param string $package          Package prefix configuration.
 * @param bool   $prefix_namespace Whether to prefix namespaces or not.
 * @param bool   $prefix_uses      Whether to prefix uses of a namespace.
 */
function process_file( $file, $package, $prefix_namespace = true, $prefix_uses = false ) {
	global $direct_replacements, $namespace_prefix;

	$contents     = file_get_contents( $file );
	$content_hash = md5( $contents );

	if ( $prefix_namespace ) {
		prefix_namespace( $contents, $package );
	}

	if ( $prefix_uses ) {
		prefix_imports( $contents, $package );

		if ( ! empty( $direct_replacements[ $package['package'] ] ) ) {
			foreach ( $direct_replacements[ $package['package'] ] as $search ) {
				prefix_string( $contents, $search );
			}
		}
	}

	// Only overwrite file if the contents have changed.
	if ( $content_hash !== md5( $contents ) ) {
		file_put_contents( $file, $contents );
	}
}

/**
 * Prefix the namespace.
 *
 * @since x.x.x
 *
 * @param string $contents File contents.
 * @param string $package  Package prefix configuration.
 */
function prefix_namespace( &$contents, $package ) {
	global $namespace_prefix;

	$quoted = preg_quote( $package['namespace'], '#' );

	// Match only the full namespace when strict is enabled.
	if ( $package['strict'] ) {
		$regex = "#^(\s*)(namespace)\s*({$quoted};)#m";
	} else {
		$regex = "#^(\s*)(namespace)\s*({$quoted}[\\\\|;])#m";
	}

	$contents = preg_replace(
		$regex,
		"\$1\$2 {$namespace_prefix}\\\\\$3",
		$contents
	);

	if ( $package['strict'] && ! empty( $package['extra_namespaces'] ) ) {
		foreach ( $package['extra_namespaces'] as $namespace ) {
			prefix_namespace(
				$contents,
				[
					'namespace' => $namespace,
					'package'   => $package['package'],
					'strict'    => true,
				]
			);
		}
	}
}

/**
 * Prefix any import statements.
 *
 * @since x.x.x
 *
 * @param string $contents File contents.
 * @param string $package  Package prefix configuration.
 */
function prefix_imports( &$contents, $package ) {
	global $namespace_prefix;

	$quoted = preg_quote( $package['namespace'], '#' );

	// Match only the full namespace when strict is enabled.
	if ( $package['strict'] ) {
		$regex = "#^(\s*)(use)\s*({$quoted}\\\\[a-zA-Z0-9_]+[;| ])#m";
	} else {
		$regex = "#^(\s*)(use)\s*({$quoted}\\\\)#m";
	}

	$contents = preg_replace(
		$regex,
		"\$1\$2 {$namespace_prefix}\\\\\$3",
		$contents
	);

	// Replace direct class extends.
	$contents = preg_replace(
		"#(\s*)(class .* extends)\s*(\\\\{$quoted}\\\\[a-zA-Z0-9_]+\s*\{?)$#m",
		"\$1\$2 \\\\{$namespace_prefix}\$3",
		$contents
	);

	if ( $package['strict'] && ! empty( $package['extra_namespaces'] ) ) {
		foreach ( $package['extra_namespaces'] as $namespace ) {
			prefix_imports(
				$contents,
				[
					'namespace' => $namespace,
					'package'   => $package['package'],
					'strict'    => true,
				]
			);
		}
	}
}

/**
 * Prefix any direct string.
 *
 * @since x.x.x
 *
 * @param string $contents File contents.
 * @param string $search   String to search for.
 */
function prefix_string( &$contents, $search ) {
	global $namespace_prefix;

	$quoted   = preg_quote( $search, '#' );
	$contents = preg_replace(
		"#({$quoted})#m",
		"{$namespace_prefix}\\\\\$1",
		$contents
	);
}

/**
 * Find a list of files in a path matching a pattern.
 *
 * @since 2.2.2
 *
 * @param string $path Package path
 * @param string $match Regex pattern to match
 * @return array Matching files
 */
function get_dir_contents( $path, $match ) {
	try {
		$rdi = new RecursiveDirectoryIterator( $path );
	} catch ( UnexpectedValueException  $e ) {
		printf(
			'Expected directory "%s" was not found' . PHP_EOL,
			$path
		);
		exit( 1 );
	}

	$rii   = new RecursiveIteratorIterator( $rdi );
	$rri   = new RegexIterator( $rii, $match );
	$files = [];
	foreach ( $rri as $file ) {
		$files[] = $file->getPathname();
	}

	return $files;
}

/**
 * Find a list of PHP files for this package.
 *
 * @since 1.1.0
 *
 * @param string $package Package name.
 * @return array List of files.
 */
function find_files( string $package ): array {
	global $vendor_dir;

	return get_dir_contents( "{$vendor_dir}/{$package}", '/\.php$/i' );
}

/**
 * Find a list of dependent PHP files for this package.
 *
 * @since x.x.x
 *
 * @param string $package Package name.
 * @return array Merged list of files.
 */
function find_dependent_files( string $package ): array {
	global $vendor_dir, $dependencies;

	if ( empty( $dependencies[ $package ] ) ) {
		return [];
	}

	$files = [];
	foreach ( $dependencies[ $package ] as $dependency ) {
		$dependent_files = get_dir_contents( "{$vendor_dir}/{$dependency}", '/\.php$/i' );
		$files           = array_merge( $files, $dependent_files );
	}

	return $files;
}

/**
 * Removes any autoload files from a package, and confirms they are loaded from the main composer.json file.
 * This ensures that the generated file vendor/composer/autoload_files.php will only autoload the files once,
 * using the new namespace. Autoloading the files from our main composer.json ensures we use a unique hash so
 * we don't conflict with other extensions autoloading the same files.
 *
 * The second task of this function is to prefix any autoloads with our custom namespace prefix.
 *
 * @since x.x.x
 *
 * @param string $file              Generated file containing information about all the installed packages
 * @param array  $composer_autoload List of autoloaded files in composer.json
 * @param array  $package           Package prefix configuration.
 */
function update_autoloads( string $file, array $composer_autoload, array $package ) {
	global $namespace_prefix;

	if ( ! file_exists( $file ) ) {
		return;
	}

	$json = json_decode( file_get_contents( $file ), true );
	if ( empty( $json['packages'] ) ) {
		return;
	}

	$modified = false;
	foreach ( $json['packages'] as $key => $dep_package ) {

		// If strict confirm that full package name matches.
		if ( $package['strict'] && $dep_package['name'] !== $package['package'] ) {
			continue;
		}

		// Check if start of package name matches.
		if ( 0 !== stripos( $dep_package['name'], $package['package'] ) ) {
			continue;
		}

		if ( empty( $dep_package['autoload'] ) ) {
			continue;
		}

		// Remove any file autoloads and ensure they are included in the main composer file
		if ( ! empty( $dep_package['autoload']['files'] ) ) {
			$modified = true;

			foreach ( $dep_package['autoload']['files'] as $autoload_file ) {

				// Confirm we already include this autoload in the main composer file.
				$filename = "vendor/{$dep_package['name']}/{$autoload_file}";
				if ( in_array( $filename, $composer_autoload, true ) ) {
					continue;
				}

				// Confirm this file isn't being excluded.
				if ( in_array( $autoload_file, $package['exclude_autoload'], true ) ) {
					continue;
				}

				printf(
					'Autoloaded file "%s" should be included in composer.json' . PHP_EOL,
					$filename
				);
				exit( 1 );
			}

			$json['packages'][ $key ]['autoload']['files'] = [];
		}

		// Prefix any of the autoloads for this specific package.
		foreach ( $json['packages'][ $key ]['autoload'] as $type => $mappings ) {
			foreach ( $mappings as $namespace => $path ) {
				if ( 0 === stripos( (string) $namespace, $package['namespace'] ) ) {
					$modified = true;
					unset( $json['packages'][ $key ]['autoload'][ $type ][ $namespace ] );
					$json['packages'][ $key ]['autoload'][ $type ][ "{$namespace_prefix}\\{$namespace}" ] = $path;
				}
			}
		}
	}

	if ( $modified ) {
		file_put_contents(
			$file,
			json_encode( $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}
}
