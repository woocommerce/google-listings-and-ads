/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BillingSetupCard from './billing-setup-card';
import useWindowFocus from '.~/hooks/useWindowFocus';

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' ).mockReturnValue( {} )
);

jest.mock( '.~/hooks/useWindowFocus', () =>
	jest.fn().mockName( 'useWindowFocus' ).mockReturnValue( true )
);

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

describe( 'BillingSetupCard', () => {
	let fetchBillingStatus;

	beforeEach( () => {
		fetchBillingStatus = jest
			.fn()
			.mockResolvedValue( { status: 'approved' } );

		apiFetch.mockImplementation( ( { path } ) => {
			switch ( path ) {
				case '/wc/gla/ads/accounts':
					return Promise.resolve( {} );

				case '/wc/gla/ads/billing-status':
					return fetchBillingStatus();

				default:
					throw new Error( `No mocked apiFetch for path: ${ path }` );
			}
		} );
	} );

	it( 'Should only call back `onSetupComplete` once regardless of its reference change', async () => {
		const inspect = jest.fn();

		let onSetupComplete = () => inspect();
		let rerender;

		// Initial with a not yet set up billing.
		fetchBillingStatus.mockResolvedValueOnce( { status: 'unknown' } );
		await act( async () => {
			const result = render(
				<BillingSetupCard onSetupComplete={ onSetupComplete } />
			);
			rerender = result.rerender;
		} );

		expect( fetchBillingStatus ).toHaveBeenCalledTimes( 1 );
		expect( inspect ).toHaveBeenCalledTimes( 0 );

		// Simulate document regains focus to trigger billing status check again.
		useWindowFocus.mockReturnValueOnce( false );
		rerender( <BillingSetupCard onSetupComplete={ onSetupComplete } /> );
		await act( async () => {
			rerender(
				<BillingSetupCard onSetupComplete={ onSetupComplete } />
			);
		} );

		expect( fetchBillingStatus ).toHaveBeenCalledTimes( 2 );
		expect( inspect ).toHaveBeenCalledTimes( 1 );

		// Change the reference of callback.
		onSetupComplete = () => inspect();
		await act( async () => {
			rerender(
				<BillingSetupCard onSetupComplete={ onSetupComplete } />
			);
		} );

		expect( inspect ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Should not call back `onSetupComplete` again by the next interval after billing is already approved', async () => {
		const onSetupComplete = jest.fn();

		await act( async () => {
			render( <BillingSetupCard onSetupComplete={ onSetupComplete } /> );
		} );

		expect( fetchBillingStatus ).toHaveBeenCalledTimes( 1 );
		expect( onSetupComplete ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		expect( onSetupComplete ).toHaveBeenCalledTimes( 1 );
	} );
} );
