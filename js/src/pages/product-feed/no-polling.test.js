/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import ProductFeed from './';

jest.mock( '@wordpress/api-fetch', () => {
	const mock = jest.fn();
	mock.use = jest.fn();
	return mock;
} );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '~/hooks/useDataViewsScript', () => jest.fn( () => 'failed' ) );

jest.mock( '~/components/customer-effort-score-prompt', () => () => null );

// Show the experience rating banner, which reads product statistics.
jest.mock( '~/hooks/useGoogleAdsAccountReady', () =>
	jest.fn( () => ( { isGoogleAdsReady: true } ) )
);
jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn( () => ( { isReady: true } ) )
);
jest.mock( '~/hooks/usePreference', () => jest.fn( () => false ) );

/**
 * Response bodies by route. Product statistics are still being calculated,
 * which is when the statistics hook polls unless told not to.
 */
const BODIES = [
	[ /review-refresh/, { started: true, reason: 'started' } ],
	[
		/product-journey/,
		{
			total: 1,
			segments: {
				approved: 1,
				pending_sync: 0,
				pending_review: 0,
				disapproved: 0,
				not_synced: 0,
			},
		},
	],
	[
		/sync-health/,
		{
			generated_at: 1,
			state: 'synced',
			syncing: {},
			reasons: [],
			pillars: {
				products: { full_sync: {} },
				shipping: {},
				// Not fetching review results, so nothing should be watched.
				review: { loading: false },
			},
		},
	],
	[ /mc\/product-statistics/, { loading: true, statistics: null } ],
	[ /mc\/issues/, { issues: [], total: 0 } ],
	[ /mc\/product-feed/, { products: [], total: 0 } ],
	[ /ads\/campaigns/, [] ],
];

const bodyFor = ( path ) =>
	BODIES.find( ( [ pattern ] ) => pattern.test( path ) )?.[ 1 ] || {};

beforeAll( () => {
	// Used in the js/src/hooks/useMenuEffect.js dependency
	window.wpNavMenuClassChange = jest.fn();
} );

describe( 'ProductFeed page', () => {
	it( 'fetches nothing on a timer after the page has loaded, when no review results are being fetched', async () => {
		jest.useFakeTimers();
		apiFetch.mockImplementation( ( options ) => {
			const body = bodyFor( options.path || options.url || '' );
			// Hooks built on `useApiFetchCallback` ask for the raw response.
			return Promise.resolve(
				options.parse === false
					? {
							json: () => Promise.resolve( body ),
							clone() {
								return this;
							},
					  }
					: body
			);
		} );

		try {
			render( <ProductFeed /> );

			// Let the page-visit requests settle.
			await act( async () => {
				jest.advanceTimersByTime( 1000 );
			} );
			const paths = () =>
				apiFetch.mock.calls.map(
					( [ options ] ) => options.path || options.url
				);
			const loaded = paths();
			expect( loaded ).toEqual(
				expect.arrayContaining( [
					expect.stringMatching( /sync-health\?refresh=1$/ ),
					expect.stringMatching( /mc\/product-statistics/ ),
				] )
			);

			// Five minutes, a second at a time, so countdown timers can re-render
			// and act on each tick as they would in the browser.
			for ( let second = 0; second < 5 * 60; second++ ) {
				await act( async () => {
					jest.advanceTimersByTime( 1000 );
				} );
			}

			expect( paths() ).toEqual( loaded );
		} finally {
			jest.useRealTimers();
		}
	} );
} );
