#!/usr/bin/env node
/* eslint-disable @woocommerce/dependency-group */
/* global fetch */
'use strict';
import * as fs from 'node:fs';

const externalizedListPath = '.externalized.json';
const wooPackage = ( tag, packageName ) =>
	`https://raw.githubusercontent.com/woocommerce/woocommerce/${ tag }/packages/js/${ packageName }/package.json`;

const myArgs = process.argv.slice( 2 );
const wcTag = myArgs[ 0 ];
if ( ! wcTag || ! wcTag.match( /(\d+).(\d+).(\d+).*/ ) ) {
	// eslint-disable-next-line no-console
	console.warn( "Warning: It seems you didn't provide a valid WooCommerce tag." );
}

const externalizedList = JSON.parse( fs.readFileSync( externalizedListPath ) );
// Filter only packages externalized by `@woocommerce/dependency-extraction-webpack-plugin`.
const wooDependencies = externalizedList.filter( ( dependency ) =>
	dependency.startsWith( '@woocommerce/' )
);
/**
 * Tries to fetch the version of `@woocommerce/` package used by the given WooCommerce version.
 *
 * @param {string} tag Full tag name of released version, `'x.y.z'`.
 * @param {string} packageName Package named to be looked up in `/packages/js/*`.
 * @return {string|undefined} `'a.b.c'` version of the package, or `undefined` if not found.
 */
async function fetchVersion( tag, packageName ) {
	// Strip just the name used as folder name of `/packages/js/*`.
	packageName = packageName.match( /@woocommerce\/(.*)/ )[ 1 ];

	// Read its `package.json`.
	return fetch( wooPackage( tag, packageName ) )
		.then( ( response ) => response.json() )
		.then( ( packageJson ) => {
			return packageJson.version;
		} )
		.catch( () => {
			// Most problably there is no package of that name.
			return undefined;
		} );
}
// Build a Map-like array of package versions.
const versions = await Promise.all(
	wooDependencies.map( async ( packageName ) => {
		return [ packageName, await fetchVersion( wcTag, packageName ) ];
	} )
);
// eslint-disable-next-line no-console -- We want to actually return something to stdout.
console.log( versions );

export default versions;
