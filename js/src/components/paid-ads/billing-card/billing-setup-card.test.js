/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import BillingSetupCard from './billing-setup-card';
import useWindowFocus from '.~/hooks/useWindowFocus';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import expectComponentToRecordEventWithFilteredProperties from '.~/tests/expectComponentToRecordEventWithFilteredProperties';

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest
		.fn()
		.mockName( 'useGoogleAdsAccount' )
		.mockReturnValue( { googleAdsAccount: {} } )
);

jest.mock( '.~/hooks/useWindowFocus', () =>
	jest.fn().mockName( 'useWindowFocus' ).mockReturnValue( true )
);

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );

describe( 'BillingSetupCard', () => {
	let windowOpen;
	let fetchBillingStatus;

	beforeEach( () => {
		windowOpen = jest.spyOn( global, 'open' );

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

	afterEach( () => {
		windowOpen.mockReset();
		recordEvent.mockClear();
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
		jest.useFakeTimers();

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

		jest.useRealTimers();
		jest.clearAllTimers();
	} );

	it( 'should open the billing setup link in a pop-up window', async () => {
		render( <BillingSetupCard billingUrl="https://example.com" /> );

		expect( windowOpen ).toHaveBeenCalledTimes( 0 );

		await userEvent.click( screen.getByRole( 'button' ) );

		expect( windowOpen ).toHaveBeenCalledTimes( 1 );
		expect( windowOpen ).toHaveBeenCalledWith(
			'https://example.com',
			'_blank',
			// Ignore the value of the window features.
			expect.any( String )
		);
	} );

	it( 'should record click events and the `link_id` and `href` event properties for the billing setup button and link', async () => {
		render( <BillingSetupCard billingUrl="https://test.com" /> );

		expect( recordEvent ).toHaveBeenCalledTimes( 0 );

		await userEvent.click( screen.getByRole( 'button' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'gla_ads_set_up_billing_click',
			{ link_id: 'set-up-billing', href: 'https://test.com' }
		);

		await userEvent.click( screen.getByRole( 'link' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'gla_ads_set_up_billing_click',
			{ link_id: 'set-up-billing', href: 'https://test.com' }
		);
	} );

	it.each( [ 'button', 'link' ] )(
		'should record click events for the billing setup %s and be aware of extra event properties from filters',
		async ( role ) => {
			await expectComponentToRecordEventWithFilteredProperties(
				() => <BillingSetupCard billingUrl="https://test.com" />,
				FILTER_ONBOARDING,
				async () => await userEvent.click( screen.getByRole( role ) ),
				'gla_ads_set_up_billing_click',
				[
					{ context: 'setup-mc', step: '1' },
					{ context: 'setup-ads', step: '2' },
				]
			);
		}
	);
} );
