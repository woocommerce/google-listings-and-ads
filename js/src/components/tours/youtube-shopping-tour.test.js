/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import { TourKit } from '@woocommerce/components';

jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '~/hooks/useMenuEffect' );
jest.mock( '~/hooks/useTour', () =>
	jest.fn().mockReturnValue( {
		tourChecked: false,
		setTourChecked: jest.fn(),
	} )
);
// Build hrefs the way `@woocommerce/navigation` does at runtime, URL-encoded by
// `addQueryArgs`, e.g. `path=%2Fgoogle%2Fsettings`.
jest.mock( '@woocommerce/navigation', () => {
	const { addQueryArgs } = jest.requireActual( '@wordpress/url' );
	return {
		getNewPath: ( query, path ) =>
			addQueryArgs( 'admin.php', { page: 'wc-admin', ...query, path } ),
		getPath: () => '/google/dashboard',
		getHistory: () => ( { push: jest.fn() } ),
	};
} );
// Use the real `Link` of the header. The package's `exports` map blocks a deep
// import, so require the file by path. `TourKit` only needs to report its config.
jest.mock( '@woocommerce/components', () => ( {
	...require(
		require( 'path' ).join(
			process.cwd(),
			'node_modules/@woocommerce/components/build/link'
		)
	),
	TourKit: jest.fn( () => null ),
} ) );

/**
 * Internal dependencies
 */
import YouTubeShoppingTour from './youtube-shopping-tour';
import MainPageHeader from '~/components/main-page-header';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

describe( 'YouTubeShoppingTour', () => {
	beforeEach( () => {
		Object.assign( window.glaData, {
			mcSetupComplete: true,
			adsSetupComplete: false,
			enableReports: true,
		} );
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
	} );

	it( 'anchors to the Settings link of the main page header', () => {
		render(
			<>
				<MainPageHeader />
				<YouTubeShoppingTour />
			</>
		);

		const [ { config } ] = TourKit.mock.calls.at( -1 );
		const selector = config.steps[ 0 ].referenceElements.desktop;

		expect( document.querySelector( selector ) ).toBe(
			screen.getByRole( 'link', { name: 'Settings' } )
		);
	} );
} );
