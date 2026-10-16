/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen } from '@testing-library/react';

/**
 * The `glaData` the post editor screens receive from PHP, rather than the full object the plugin's
 * own admin pages get, so the entry is loaded the way it is on the Classic Editor.
 */
const POST_SCREEN_GLA_DATA = {
	slug: 'gla',
	initialWpData: {
		version: '1.0.0',
		mcId: null,
		adsId: null,
	},
	businessProfileLocation: {
		title: 'Trailmark Athletics',
		address: '456 Riverside Ave, San Francisco, 94107',
	},
	businessProfileSummaryMaxLength: 1500,
	businessProfilePost: {
		status: 'draft',
		title: 'New Shoes Coming Soon',
		hasManualExcerpt: false,
		excerpt: '',
		isShareEnabled: false,
		summary: '',
		isShared: false,
		searchUrl: '',
	},
};

describe( 'Business Profile share meta box entry', () => {
	const originalGlaData = global.glaData;

	afterEach( () => {
		global.glaData = originalGlaData;
		document.body.innerHTML = '';
	} );

	it( 'mounts with the glaData the post editor screens receive', async () => {
		global.glaData = POST_SCREEN_GLA_DATA;
		document.body.innerHTML =
			'<div id="gla-business-profile-share-box" data-share-field-name="share" data-summary-field-name="summary"></div>';

		// Load the entry, and the React it renders with, in a fresh module registry, so it reads
		// this glaData rather than the one the test setup defines.
		let act;
		jest.isolateModules( () => {
			( { act } = require( 'react' ) );
			require( './index' );
		} );
		await act( async () => {
			document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
		} );

		expect(
			await screen.findByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).toBeInTheDocument();
	} );
} );
