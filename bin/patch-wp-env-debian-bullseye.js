#!/usr/bin/env node

/**
 * TEMPORARY CI diagnostic — not a permanent fix, remove once confirmed working upstream.
 *
 * The wp-env tooling's generated "wordpress"/"tests-wordpress" Docker images
 * already redirect older stretch/buster Debian releases to archive.debian.org
 * (see wordpressDockerFileContents() in the patched file below), but has no
 * equivalent redirect for bullseye yet. A live-CI run confirmed this is a real
 * archival, not a transient signature-freshness blip: `apt-get -qy install`
 * failed with a genuine 404 fetching a package from the live bullseye-security
 * mirror, not just an expired Release-file signature. This script extends the
 * existing redirect pattern to also cover bullseye, so we can confirm that's
 * the real, durable fix before deciding where it should permanently live
 * (patch-package, or upstream in wp-env itself).
 */

const fs = require( 'fs' );

const file = 'node_modules/@wordpress/env/lib/init-config.js';
const target = "RUN sed -i '/buster-updates/d' /etc/apt/sources.list";
const insertion =
	target +
	'\n\n' +
	'# bullseye\n' +
	"RUN sed -i 's|deb.debian.org/debian bullseye|archive.debian.org/debian bullseye|g' /etc/apt/sources.list\n" +
	"RUN sed -i 's|security.debian.org/debian-security bullseye-security|archive.debian.org/debian-security bullseye-security|g' /etc/apt/sources.list\n" +
	"RUN sed -i '/bullseye-updates/d' /etc/apt/sources.list";

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
	`Patched ${ file } to also redirect Debian bullseye to archive.debian.org.`
);
