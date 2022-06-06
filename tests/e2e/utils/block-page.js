/**
 * External dependencies
 */
import { cleanForSlug } from '@wordpress/url';
const { dirname } = require( 'path' );
const { readJson } = require( 'fs-extra' ); // eslint-disable-line import/no-extraneous-dependencies
const axios = require( 'axios' ).default; // eslint-disable-line import/no-extraneous-dependencies
const config = require( 'config' ); // eslint-disable-line import/no-extraneous-dependencies

const WPAPI = `${ config.url }wp-json/wp/v2/pages`;

/**
 * Check if a page exists from a title.
 *
 * @param {string} title
 * @return {Promise<number>} Existing page ID.
 */
export async function pageExistsByTitle( title ) {
	const slug = cleanForSlug( title );

	return await axios
		.get( WPAPI + '?slug=' + slug )
		.then( ( response ) => response.data[ 0 ]?.id );
}

/**
 * Creates a WP page with content and title.
 *
 * @param {string} title
 * @param {string} content
 * @return {Promise<number>} Created page ID.
 */
export async function createPage( title, content ) {
	return await axios
		.post(
			WPAPI,
			{
				title,
				content,
				status: 'publish',
			},
			{
				auth: {
					username: config.users.admin.username,
					password: config.users.admin.password,
				},
			}
		)
		.then( ( response ) => response.data.id );
}

/**
 * Creates a shop page using blocks.
 */
export async function createBlockShopPage() {
	const filePath = `${ dirname(
		__filename
	) }/__fixtures__/all-products.fixture.json`;
	const file = await readJson( filePath );
	const { title, pageContent: content } = file;

	if ( ! await pageExistsByTitle( title ) ) {
		await createPage( title, content );
	}
}
