/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS } from '~/constants';

jest.mock( '~/hooks/useGoogleBusinessProfileAccount', () =>
	jest.fn().mockName( 'useGoogleBusinessProfileAccount' )
);

/**
 * The `glaBusinessProfileData` the post editor screens receive from PHP, rather than the full
 * object the plugin's own admin pages get, so the entry is loaded the way it is on the Classic
 * Editor.
 */
const POST_SCREEN_GLA_BUSINESS_PROFILE_DATA = {
	businessProfileSummaryMaxLength: 1500,
	businessProfilePost: {
		isShareEnabled: false,
		summary: '',
		isShared: false,
		searchUrl: '',
	},
};

describe( 'Business Profile share meta box entry', () => {
	const originalGlaBusinessProfileData = global.glaBusinessProfileData;

	afterEach( () => {
		global.glaBusinessProfileData = originalGlaBusinessProfileData;
		document.body.innerHTML = '';
	} );

	it( 'mounts with the glaBusinessProfileData the post editor screens receive', async () => {
		global.glaBusinessProfileData = POST_SCREEN_GLA_BUSINESS_PROFILE_DATA;
		document.body.innerHTML =
			'<div id="gla-business-profile-share-box" data-share-field-name="share" data-summary-field-name="summary"></div>';

		// Load the entry, and the React and hook it renders with, in a fresh module registry, so
		// it reads this glaBusinessProfileData rather than the one the test setup defines. A fresh
		// registry re-runs jest.mock()'s factory too, so the mocked hook needs reconfiguring here
		// -- the outer import above is a different jest.fn() instance from this isolated one.
		let act;
		jest.isolateModules( () => {
			( { act } = require( 'react' ) );
			require( '~/hooks/useGoogleBusinessProfileAccount' ).mockReturnValue(
				{
					account: {
						status: GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS.CONNECTED,
						title: 'Trailmark Athletics',
						address: '456 Riverside Ave, San Francisco, 94107',
					},
					hasFinishedResolution: true,
				}
			);
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
