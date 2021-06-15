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
		'GuzzleHttp\Psr7\stream_for',
		'GuzzleHttp\Psr7\Message::bodySummary',
		'GuzzleHttp\ClientInterface::MAJOR_VERSION',
		'GuzzleHttp\ClientInterface::VERSION',
		'GuzzleHttp\Message\ResponseInterface)',
	],
];

foreach ( $replacements as $namespace => $path ) {
	$files = find_files( $path );

	$quoted = preg_quote( $namespace, '#' );
	foreach ( $files as $file ) {
		$contents = file_get_contents( $file );

		// Check to see whether a replacement has already run for this namespace. Just in case.
		if ( false !== strpos( $contents, "{$new_namespace}\\{$namespace}" ) ) {
			continue 2;
		}

		$contents =	preg_replace(
			"#^(\s*)(use|namespace)\s*({$quoted})#m",
			"\$1\$2 {$new_namespace}\\\\\$3",
			$contents
		);

		if ( ! empty( $direct_replacements[ $path ] ) ) {
			foreach( $direct_replacements[ $path ] as $replace ) {
				$replace = preg_quote( $replace, '#' );
				$contents =	preg_replace(
					"#({$replace})#m",
					"{$new_namespace}\\\\\$1",
					$contents
				);
			}
		}

		file_put_contents( $file, $contents );
	}

	// Update the namespace in the composer.json file.
	$composer_file = "{$vendor_dir}/{$path}/composer.json";
	if ( ! file_exists( $composer_file ) ) {
		continue;
	}

	$composer_contents = file_get_contents( $composer_file );
	file_put_contents(
		$composer_file,
		str_replace(
			addslashes( "{$namespace}\\" ),
			addslashes( "{$new_namespace}\\{$namespace}\\" ),
			$composer_contents
		)
	);
}

function find_files( string $path ): array {
	global $vendor_dir, $dependencies;

	$files = array_filter(
		explode(
			"\n",
			`find {$vendor_dir}/{$path} -iname '*.php'`
		)
	);

	if ( ! empty( $dependencies[ $path ] ) ) {
		foreach( $dependencies[ $path ] as $dependency ) {
			$dependent_files = array_filter(
				explode(
					"\n",
					`find {$vendor_dir}/{$dependency} -iname '*.php'`
				)
			);
			$files = array_merge( $files, $dependent_files );
		}
	}

	return $files;
}
