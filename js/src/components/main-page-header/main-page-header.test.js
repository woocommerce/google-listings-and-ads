/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getPath, getHistory } from '@woocommerce/navigation';

jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '~/hooks/useMenuEffect' );
jest.mock( '@woocommerce/settings', () => ( {
	getSetting: ( name, fallback ) =>
		( { siteTitle: 'My Store &amp; Co' } )[ name ] ?? fallback,
} ) );
jest.mock( '@woocommerce/navigation', () => {
	const history = { push: jest.fn() };
	return {
		getNewPath: ( _query, path ) =>
			`admin.php?page=wc-admin&path=${ path }`,
		getPath: jest.fn().mockReturnValue( '/google/dashboard' ),
		getHistory: () => history,
	};
} );
// Use the real `Link`, which pushes wc-admin links onto the history, without loading
// the whole package and its data stores. The package's `exports` map blocks a deep
// import, so require the file by path.
jest.mock( '@woocommerce/components', () =>
	require(
		require( 'path' ).join(
			process.cwd(),
			'node_modules/@woocommerce/components/build/link'
		)
	)
);

/**
 * Internal dependencies
 */
import MainPageHeader from './main-page-header';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useMenuEffect from '~/hooks/useMenuEffect';

describe( 'MainPageHeader', () => {
	const sectionLinks = () =>
		within(
			screen.getByRole( 'navigation', {
				name: 'Google for WooCommerce sections',
			} )
		).getAllByRole( 'link' );

	beforeEach( () => {
		// `~/constants` keeps a reference to this object, so change it in place.
		Object.assign( window.glaData, {
			mcSetupComplete: true,
			adsSetupComplete: false,
			enableReports: true,
		} );

		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );
		getPath.mockReturnValue( '/google/dashboard' );
		getHistory().push.mockClear();
	} );

	it( 'renders the plugin name as the page heading', () => {
		render( <MainPageHeader /> );

		expect(
			screen.getByRole( 'heading', {
				level: 1,
				name: 'Google for WooCommerce',
			} )
		).toBeInTheDocument();
	} );

	it( 'renders every section link with its wc-admin href in the default state', () => {
		render( <MainPageHeader /> );

		expect(
			sectionLinks().map( ( link ) => [
				link.textContent,
				link.getAttribute( 'href' ),
			] )
		).toEqual( [
			[ 'Dashboard', 'admin.php?page=wc-admin&path=/google/dashboard' ],
			[ 'Reports', 'admin.php?page=wc-admin&path=/google/reports' ],
			[
				'Product Feed',
				'admin.php?page=wc-admin&path=/google/product-feed',
			],
			[
				'Price Benchmark',
				'admin.php?page=wc-admin&path=/google/price-benchmark',
			],
			[
				'Attributes',
				'admin.php?page=wc-admin&path=/google/attribute-mapping',
			],
			[ 'Markets', 'admin.php?page=wc-admin&path=/google/markets' ],
			[ 'Settings', 'admin.php?page=wc-admin&path=/google/settings' ],
		] );
	} );

	it( 'marks only the link matching the current path as the current page', () => {
		getPath.mockReturnValue( '/google/product-feed' );

		render( <MainPageHeader /> );

		const current = sectionLinks().filter(
			( link ) => link.getAttribute( 'aria-current' ) === 'page'
		);
		expect( current.map( ( link ) => link.textContent ) ).toEqual( [
			'Product Feed',
		] );
	} );

	it( 'shows only Dashboard and Settings without Merchant Center or Ads', () => {
		window.glaData.mcSetupComplete = false;

		render( <MainPageHeader /> );

		expect( sectionLinks().map( ( link ) => link.textContent ) ).toEqual( [
			'Dashboard',
			'Settings',
		] );
	} );

	it( 'hides Reports when the merchant cannot view reports', () => {
		window.glaData.enableReports = false;

		render( <MainPageHeader /> );

		expect(
			sectionLinks().map( ( link ) => link.textContent )
		).not.toContain( 'Reports' );
	} );

	it( 'navigates inside wc-admin without a full page load', async () => {
		render( <MainPageHeader /> );

		await userEvent.click(
			screen.getByRole( 'link', { name: 'Markets' } )
		);

		expect( getHistory().push ).toHaveBeenCalledWith(
			'admin.php?page=wc-admin&path=/google/markets'
		);
	} );

	it( 'renders the given page actions in the header', () => {
		render( <MainPageHeader actions={ <button>Add campaign</button> } /> );

		expect(
			screen.getByRole( 'button', { name: 'Add campaign' } )
		).toBeInTheDocument();
	} );

	it( 'sets the browser tab title for the current section', () => {
		getPath.mockReturnValue( '/google/settings' );

		render( <MainPageHeader /> );

		expect( document.title ).toBe(
			'Settings ‹ Google for WooCommerce ‹ Marketing ‹ WooCommerce ‹ My Store & Co — WooCommerce'
		);
	} );

	it( 'highlights the plugin menu entry', () => {
		render( <MainPageHeader /> );

		expect( useMenuEffect ).toHaveBeenCalled();
	} );
} );
