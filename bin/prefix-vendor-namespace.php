#!/usr/bin/env php
<?php
declare( strict_types=1 );

// phpcs:ignoreFile

$replacements  = [
	'League\\Container' => 'league/container',
	'League\\ISO3166'   => 'league/iso3166',
];
$vendor_dir    = dirname( __DIR__ ) . '/vendor';
$new_namespace = 'Automattic\\WooCommerce\\GoogleListingsAndAds\\Vendor';

foreach ( $replacements as $namespace => $path ) {
	$files = array_filter(
		explode(
			"\n",
			`find {$vendor_dir}/{$path} -iname '*.php'`
		)
	);

	$quoted = preg_quote( $namespace, '#' );
	foreach ( $files as $file ) {
		$contents = file_get_contents( $file );

		// Check to see whether a replacement has already run. Just in case.
		if ( false !== strpos( $contents, $new_namespace ) ) {
			continue 2;
		}

		file_put_contents(
			$file,
			preg_replace(
				"#^(\s*)(use|namespace)\s*({$quoted})#m",
				"\$1\$2 {$new_namespace}\\\\\$3",
				$contents
			)
		);
	}

	// Update the namespace in the composer.json file.
	$composer_file     = "{$vendor_dir}/{$path}/composer.json";
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
