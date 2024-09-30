/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import DisconnectAccount from './disconnect-account';
import { useAppDispatch } from '.~/data';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import expectComponentToRecordEventWithFilteredProperties from '.~/tests/expectComponentToRecordEventWithFilteredProperties';

jest.mock( '.~/data', () => ( {
	...jest.requireActual( '.~/data' ),
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );

describe( 'DisconnectAccount', () => {
	let disconnectGoogleAdsAccount;

	beforeEach( () => {
		disconnectGoogleAdsAccount = jest
			.fn()
			.mockName( 'disconnectGoogleAdsAccount' );
		useAppDispatch.mockReturnValue( { disconnectGoogleAdsAccount } );
	} );

	afterEach( () => {
		recordEvent.mockClear();
	} );

	it( 'should disable the button after clicking it', async () => {
		const user = userEvent.setup();
		let resolve;

		disconnectGoogleAdsAccount.mockReturnValue(
			new Promise( ( _resolve ) => {
				resolve = _resolve;
			} )
		);

		render( <DisconnectAccount /> );
		const button = screen.getByRole( 'button' );

		expect( button ).toBeEnabled();

		await user.click( button );

		expect( button ).toBeDisabled();

		await act( async () => resolve() );

		expect( button ).toBeDisabled();
	} );

	it( 'should enable the button after a failed disconnection', async () => {
		const user = userEvent.setup();
		let reject;

		disconnectGoogleAdsAccount.mockReturnValue(
			new Promise( ( _, _reject ) => {
				reject = _reject;
			} )
		);

		render( <DisconnectAccount /> );
		const button = screen.getByRole( 'button' );

		await user.click( button );

		expect( button ).toBeDisabled();

		await act( async () => reject() );

		expect( button ).toBeEnabled();
	} );

	it( 'should record click events and be aware of extra event properties from filters', async () => {
		const user = userEvent.setup();

		// Prevent the component from locking in the disconnecting state
		disconnectGoogleAdsAccount.mockRejectedValue();

		await expectComponentToRecordEventWithFilteredProperties(
			DisconnectAccount,
			FILTER_ONBOARDING,
			async () => await user.click( screen.getByRole( 'button' ) ),
			'gla_ads_account_disconnect_button_click',
			[
				{ context: 'setup-mc', step: '1' },
				{ context: 'setup-ads', step: '2' },
			]
		);
	} );
} );
