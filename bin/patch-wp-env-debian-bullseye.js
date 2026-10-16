/**
 * wp-env already redirects stretch and buster to archive.debian.org once they
 * go EOL — bullseye needs the same now. Runs automatically before `wp-env:up`.
 *
 * compatibility-code "@wordpress/env <= 10.29.0" -- remove once wp-env ships its own bullseye redirect
 */

const fs = require( 'fs' );

const file = 'node_modules/@wordpress/env/lib/init-config.js';
const target = "RUN sed -i '/buster-updates/d' /etc/apt/sources.list";
const insertion =
	target +
	'\n\n' +
	'# bullseye\n' +
	"RUN sed -i 's|deb.debian.org/debian bullseye|archive.debian.org/debian bullseye|g' /etc/apt/sources.list\n" +
	"RUN sed -i '/bullseye-security/d' /etc/apt/sources.list\n" +
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
