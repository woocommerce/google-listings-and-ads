#!/usr/bin/env node

/**
 * TEMPORARY CI diagnostic — not a permanent fix, remove once the root cause is confirmed.
 *
 * Debian's bullseye-security release metadata has been expiring, which makes
 * the wp-env tooling's generated "wordpress" Docker image build fail on its
 * `apt-get -qy update` step. The installed version already redirects older
 * stretch/buster releases to archive.debian.org, but has no equivalent fix
 * for bullseye yet. This script patches the freshly-installed copy in
 * node_modules to skip apt's release-metadata freshness check, so we can
 * confirm that really is the root cause before deciding on a durable fix.
 */

const fs = require( 'fs' );

const file = 'node_modules/@wordpress/env/lib/init-config.js';
const target = 'RUN apt-get -qy update';
const insertion =
	'RUN echo \'Acquire::Check-Valid-Until "false";\' > /etc/apt/apt.conf.d/99no-check-valid-until\n' +
	target;

const content = fs.readFileSync( file, 'utf8' );

if ( ! content.includes( target ) ) {
	// eslint-disable-next-line no-console
	console.error(
		`::error::Patch target not found in ${ file } — @wordpress/env internals may have changed since this script was written.`
	);
	process.exit( 1 );
}

fs.writeFileSync( file, content.replace( target, insertion ) );

// eslint-disable-next-line no-console
console.log(
	`Patched ${ file } to skip Debian's apt release-metadata freshness check.`
);
