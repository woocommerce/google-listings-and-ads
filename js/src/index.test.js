/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { pagePaths } from './';
import { STORE_KEY } from '.~/data';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn().mockName( 'getSetting' ),
} ) );

describe( 'index', () => {
	describe( 'Filter woocommerce_admin_pages_list', () => {
		const filter = 'woocommerce_admin_pages_list';

		it( 'should initialize `pagePaths`', () => {
			expect( pagePaths.size ).toBe( 0 );

			const pages = applyFilters( filter, [] );

			expect( pages.length ).toBeGreaterThan( 0 );
			expect( pagePaths.size ).toBe( pages.length );

			pages.forEach( ( page ) => {
				const path = page.path.replace( /./, '' ).replace( /\//g, '_' );

				expect( pagePaths ).toContain( path );
			} );
		} );

		it( 'should keep the pages that already exist before applying the filter', () => {
			const page = {};
			const pages = applyFilters( filter, [ page ] );

			expect( pages ).toContain( page );
		} );

		it( "should add this plugin's pages", () => {
			const pages = applyFilters( filter, [] );

			expect( pages.length ).toBeGreaterThan( 0 );

			pages.forEach( ( page ) => {
				expect( page ).toMatchObject( {
					breadcrumbs: expect.arrayContaining( [
						[ '', 'WooCommerce' ],
						[ '/marketing', 'Marketing' ],
						'Google for WooCommerce',
						expect.any( String ),
					] ),
					container: expect.any( Function ),
					path: expect.stringMatching( /^\/google\/[a-z\-]+/ ),
				} );
			} );
		} );
	} );

	describe( 'Filter woocommerce_tracks_client_event_properties', () => {
		function getProperties( path, eventName = 'wcadmin_page_view' ) {
			return applyFilters(
				'woocommerce_tracks_client_event_properties',
				{ path },
				eventName
			);
		}

		function updateAccountIds( mcId, adsId ) {
			dispatch( STORE_KEY ).hydratePrefetchedData( { mcId, adsId } );
		}

		beforeEach( () => {
			// To initialize `pagePaths`.
			applyFilters( 'woocommerce_admin_pages_list', [] );
		} );

		afterEach( () => {
			updateAccountIds( null, null );
		} );

		it( "When the `path` of `wcadmin_page_view` tracking event is one of this plugin's route paths, it should add base properties", () => {
			expect( pagePaths.size ).toBeGreaterThan( 0 );

			pagePaths.forEach( ( path ) => {
				const properties = getProperties( path );

				expect( properties ).toEqual( { path, gla_version: '1.2.3' } );
			} );

			updateAccountIds( 123, 456 );

			pagePaths.forEach( ( path ) => {
				const properties = getProperties( path );

				expect( properties ).toEqual( {
					path,
					gla_version: '1.2.3',
					gla_mc_id: 123,
					gla_ads_id: 456,
				} );
			} );
		} );

		it( "When the `path` of `wcadmin_page_view` tracking event is not one of this plugin's route paths, it should not add base properties", () => {
			const path = 'google_other_plugin_page';

			expect( pagePaths.size ).toBeGreaterThan( 0 );
			expect( getProperties( path ) ).toEqual( { path } );
		} );

		it( 'When the tracking event is not `wcadmin_page_view`, it should not add base properties', () => {
			expect( pagePaths.size ).toBeGreaterThan( 0 );

			pagePaths.forEach( ( path ) => {
				const properties = getProperties(
					path,
					'wcadmin_marketplace_view'
				);

				expect( properties ).toEqual( { path } );
			} );
		} );
	} );
} );
