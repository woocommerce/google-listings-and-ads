#!/usr/bin/env php
<?php
declare( strict_types=1 );

// phpcs:ignoreFile

$replacements  = [
	'League\\Container' => 'league/container',
	'League\\ISO3166'   => 'league/iso3166',
	'Google\\Auth'      => 'google/auth',
	'GuzzleHttp'        => 'guzzlehttp',
];
$vendor_dir    = dirname( __DIR__ ) . '/vendor';
$new_namespace = 'Automattic\\WooCommerce\\GoogleListingsAndAds\\Vendor';

// Vendor libraries which are dependent on a library we are prefixing.
$dependencies = [
	'google/auth' => [
		'google/apiclient',
		'google/gax',
	],
	'guzzlehttp'  => [
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

// Flag modified files that maybe should have been modified more.
$file_notices = [];

foreach ( $replacements as $namespace => $path ) {
	$files = find_files( $path );

	$quoted = preg_quote( $namespace, '#' );
	foreach ( $files as $file ) {
		$contents = file_get_contents( $file );

		// Check to see whether a replacement has already run for this namespace. Just in case.
		if ( false !== strpos( $contents, "{$new_namespace}\\{$namespace}" ) ) {
			continue 2;
		}

		$namespace_change = 0;
		$uses_change      = 0;

		$contents =	preg_replace(
			"#^(\s*)(namespace)\s*({$quoted}[\\\\|;])#m",
			"\$1\$2 {$new_namespace}\\\\\$3",
			$contents,
			-1,
			$namespace_change
		);

		$contents =	preg_replace(
			"#^(\s*)(use)\s*({$quoted}\\\\)#m",
			"\$1\$2 {$new_namespace}\\\\\$3",
			$contents,
			-1,
			$uses_change
		);

		if ( ! empty( $direct_replacements[ $path ] ) ) {
			foreach ( $direct_replacements[ $path ] as $replace ) {
				$direct_change = 0;
				$replace       = preg_quote( $replace, '#' );
				$contents      = preg_replace(
					"#({$replace})#m",
					"{$new_namespace}\\\\\$1",
					$contents,
					-1,
					$direct_change
				);
				if ( $direct_change ) {
					$uses_change += $direct_change;
				}
			}
		}
		if ( ! $namespace_change && $uses_change ) {
			$file_notices[] = $file;
		}

		file_put_contents( $file, $contents );
	}

	// Update the namespace in the composer.json files, recursively finding all files named explicitly "composer.json".
	$composer_files = get_dir_contents(
		"{$vendor_dir}/{$path}",
		'/' . preg_quote( DIRECTORY_SEPARATOR, '/') . 'composer.json$/'
	);

	array_map(
		function( $file ) use ( $namespace, $new_namespace ) {
			return replace_in_json_file( $file, $namespace, $new_namespace );
		},
		$composer_files
	);

	// Update the namespace in vendor/composer/installed.json
	// This file is used to generate the classmaps.
	replace_in_json_file( "{$vendor_dir}/composer/installed.json", $namespace, $new_namespace );

	// Remove file autoloads from vendor/composer/installed.json
	remove_file_autoloads(
		"{$vendor_dir}/composer/installed.json",
		$composer_json['autoload']['files'] ?? [],
		$path
	);
}

if ( count( $file_notices ) ) {
	printf(
		'Several files were modified without changes to namespace: %s' . PHP_EOL,
		implode('; ', $file_notices)
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

	$rii = new RecursiveIteratorIterator( $rdi );
	$rri = new RegexIterator( $rii, $match );
	$files = [];
	foreach ( $rri as $file ) {
		$files[] = $file->getPathname();
	}

	return $files;
}

/**
 * Find a list of PHP files for this package, and append a list of dependent
 * files that use the package.
 *
 * @since 1.1.0
 *
 * @param string $path Package path
 * @return array Merged list of files
 */
function find_files( string $path ): array {
	global $vendor_dir, $dependencies;

	$files = get_dir_contents( "{$vendor_dir}/{$path}", '/\.php$/i' );

	if ( ! empty( $dependencies[ $path ] ) ) {
		foreach ( $dependencies[ $path ] as $dependency ) {
			$dependent_files = get_dir_contents( "{$vendor_dir}/{$dependency}", '/\.php$/i' );
			$files = array_merge( $files, $dependent_files );
		}
	}

	return $files;
}

/**
 * Replace namespace strings with a JSON file.
 *
 * @since 1.2.0
 *
 * @param string $file          Filename to replace the strings
 * @param string $namespace     Namespace to search for
 * @param string $new_namespace Namespace to replace with
 */
function replace_in_json_file( string $file, string $namespace, string $new_namespace ) {
	if ( ! file_exists( $file ) ) {
		return;
	}

	$contents = file_get_contents( $file );
	file_put_contents(
		$file,
		str_replace(
			addslashes( "{$namespace}\\" ),
			addslashes( "{$new_namespace}\\{$namespace}\\" ),
			$contents
		)
	);
}

/**
 * Removes any autoload files from a package, and confirms they are loaded from the main composer.json file.
 * This ensures that the generated file vendor/composer/autoload_files.php will only autoload the files once,
 * using the new namespace. Autoloading the files from our main composer.json ensures we use a unique hash so
 * we don't conflict with other extensions autoloading the same files.
 *
 * @since 1.4.2
 *
 * @param string $file              Generated file containing information about all the installed packages
 * @param array  $composer_autoload List of autoloaded files in composer.json
 * @param string $package_name      Name of the package we are replacing
 */
function remove_file_autoloads( string $file, array $composer_autoload, string $package_name ) {
	if ( ! file_exists( $file ) ) {
		return;
	}

	$json = json_decode( file_get_contents( $file ), true );
	if ( empty( $json['packages'] ) ) {
		return;
	}

	$modified = false;
	foreach ( $json['packages'] as $key => $package ) {
		if ( 0 !== strpos( $package['name'], $package_name ) ) {
			continue;
		}

		if ( empty( $package['autoload']['files'] ) ) {
			continue;
		}

		foreach ( $package['autoload']['files'] as $autoload_file ) {

			// Confirm we already include this autoload in the main composer file.
			$filename = "vendor/{$package['name']}/{$autoload_file}";
			if ( in_array( $filename, $composer_autoload, true ) ) {
				continue;
			}

			printf(
				'Autoloaded file "%s" should be included in composer.json' . PHP_EOL,
				$filename
			);
			exit( 1 );
		}

		$json['packages'][ $key ]['autoload']['files'] = [];
		$modified = true;
	}

	if ( $modified ) {
		file_put_contents(
			$file,
			json_encode( $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}
}
