jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
		queueRecordEvent: jest.fn().mockName( 'queueRecordEvent' ),
	};
} );

/**
 * External dependencies
 */
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { recordGlaEvent, queueRecordGlaEvent } from '.~/utils/tracks';
import { STORE_KEY } from '.~/data';

function updateMcId( mcId ) {
	dispatch( STORE_KEY ).hydratePrefetchedData( { mcId } );
}

function updateAdsId( adsId ) {
	dispatch( STORE_KEY ).hydratePrefetchedData( { adsId } );
}

describe( 'tracks', () => {
	beforeAll( () => {
		// Simulate that the version has been hydrated via the initialization of .~/data/index.js
		dispatch( STORE_KEY ).hydratePrefetchedData( { version: '1.2.3' } );
	} );

	beforeEach( () => {
		recordEvent.mockClear();
		queueRecordEvent.mockClear();
	} );

	afterEach( () => {
		updateMcId( null );
		updateAdsId( null );
	} );

	describe.each( [
		[ recordGlaEvent.name, recordGlaEvent, recordEvent ],
		[ queueRecordGlaEvent.name, queueRecordGlaEvent, queueRecordEvent ],
	] )( '%s', ( trackingFnName, trackingFn, mockedFn ) => {
		it( `should forward event name and event properties to ${ mockedFn.getMockName() }`, () => {
			trackingFn( 'closed' );
			trackingFn( 'closed', { by: 'Press ESC' } );
			trackingFn( 'closed', { by: 'Press ESC', page: 'Onboarding' } );

			expect( mockedFn ).toHaveBeenCalledTimes( 3 );
			expect( mockedFn ).toHaveBeenNthCalledWith( 1, 'closed', {
				gla_version: '1.2.3',
			} );
			expect( mockedFn ).toHaveBeenNthCalledWith( 2, 'closed', {
				gla_version: '1.2.3',
				by: 'Press ESC',
			} );
			expect( mockedFn ).toHaveBeenNthCalledWith( 3, 'closed', {
				gla_version: '1.2.3',
				by: 'Press ESC',
				page: 'Onboarding',
			} );
		} );

		it( 'should attatch base tracking properties: version, Goggle Merchant Center account ID, and Google Ads account ID', () => {
			trackingFn( 'clicked', { button: 'confirm' } );

			updateMcId( 123 );
			trackingFn( 'clicked', { button: 'confirm' } );

			updateAdsId( 456 );
			trackingFn( 'clicked', { button: 'confirm' } );

			updateMcId( null );
			trackingFn( 'clicked', { button: 'confirm' } );

			expect( mockedFn ).toHaveBeenCalledTimes( 4 );
			expect( mockedFn ).toHaveBeenNthCalledWith( 1, 'clicked', {
				button: 'confirm',
				gla_version: '1.2.3',
			} );
			expect( mockedFn ).toHaveBeenNthCalledWith( 2, 'clicked', {
				button: 'confirm',
				gla_version: '1.2.3',
				gla_mc_id: 123,
			} );
			expect( mockedFn ).toHaveBeenNthCalledWith( 3, 'clicked', {
				button: 'confirm',
				gla_version: '1.2.3',
				gla_mc_id: 123,
				gla_ads_id: 456,
			} );
			expect( mockedFn ).toHaveBeenNthCalledWith( 4, 'clicked', {
				button: 'confirm',
				gla_version: '1.2.3',
				gla_ads_id: 456,
			} );
		} );

		it( 'should not allow to overwrite base tracking properties', () => {
			updateMcId( 123 );
			updateAdsId( 456 );

			trackingFn( 'connected', {
				gla_version: '9.9.9',
				gla_mc_id: 123456789,
				gla_ads_id: 987654321,
			} );

			expect( mockedFn ).toHaveBeenCalledTimes( 1 );
			expect( mockedFn ).toHaveBeenCalledWith( 'connected', {
				gla_version: '1.2.3',
				gla_mc_id: 123,
				gla_ads_id: 456,
			} );
		} );
	} );
} );
