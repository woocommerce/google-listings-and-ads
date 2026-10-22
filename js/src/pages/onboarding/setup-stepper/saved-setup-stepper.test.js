jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );

jest.mock( './useTargetAudienceWithSuggestions', () =>
	jest
		.fn()
		.mockName( 'useTargetAudienceWithSuggestions' )
		.mockReturnValue( {} )
);
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest
		.fn()
		.mockName( 'useTargetAudienceFinalCountryCodes' )
		.mockReturnValue( {} )
);
jest.mock( '~/hooks/useSettings', () =>
	jest.fn().mockName( 'useSettings' ).mockReturnValue( {} )
);
jest.mock( '~/hooks/useShippingRates', () =>
	jest.fn().mockName( 'useShippingRates' ).mockReturnValue( {} )
);
jest.mock( '~/hooks/useShippingTimes', () =>
	jest.fn().mockName( 'useShippingTimes' ).mockReturnValue( {} )
);

jest.mock( './setup-accounts', () => jest.fn().mockName( 'SetupAccounts' ) );
jest.mock( '~/components/free-listings/setup-free-listings', () =>
	jest.fn().mockName( 'SetupFreeListings' )
);
jest.mock( './setup-paid-ads', () => jest.fn().mockName( 'SetupPaidAds' ) );

/**
 * External dependencies
 */
import { screen, render, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import SavedSetupStepper from './saved-setup-stepper';
import SetupAccounts from './setup-accounts';
import SetupFreeListings from '~/components/free-listings/setup-free-listings';
import SetupPaidAds from './setup-paid-ads';

describe( 'SavedSetupStepper', () => {
	let continueToStep2;
	let continueToStep3;

	beforeEach( () => {
		SetupAccounts.mockImplementation( ( { onContinue } ) => {
			continueToStep2 = onContinue;
			return null;
		} );

		SetupFreeListings.mockImplementation( ( { onContinue } ) => {
			continueToStep3 = onContinue;
			return null;
		} );

		SetupFreeListings.SubmitButton = jest
			.fn( () => null )
			.mockName( 'SetupFreeListings.SubmitButton' );

		SetupPaidAds.mockReturnValue( null );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	async function continueUntilStep3() {
		await act( async () => continueToStep2() );

		// Wait for stepper content to be rendered.
		await waitFor( () => {
			expect( continueToStep3 ).toBeDefined();
		} );

		await act( async () => continueToStep3() );
	}

	describe( 'tracks', () => {
		it( 'Should record events after calling back to `onContinue`', async () => {
			render( <SavedSetupStepper savedStep="1" /> );

			await continueUntilStep3();

			expect( recordEvent ).toHaveBeenCalledTimes( 2 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_mc', {
				action: 'go-to-step2',
				triggered_by: 'step1-continue-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 2, 'gla_setup_mc', {
				action: 'go-to-step3',
				triggered_by: 'step2-continue-button',
			} );
		} );

		it( 'Should record events after clicking step navigation buttons', async () => {
			const user = userEvent.setup();

			render( <SavedSetupStepper savedStep="3" /> );

			const step1 = screen.getByRole( 'button', { name: /accounts/ } );
			const step2 = screen.getByRole( 'button', { name: /listings/ } );

			// Step 3 -> Step 2 -> Step 1
			await user.click( step2 );
			await user.click( step1 );

			expect( recordEvent ).toHaveBeenCalledTimes( 2 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_mc', {
				action: 'go-to-step2',
				triggered_by: 'stepper-step2-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 2, 'gla_setup_mc', {
				action: 'go-to-step1',
				triggered_by: 'stepper-step1-button',
			} );

			// Step 3 -> Step 1
			await continueUntilStep3();
			recordEvent.mockClear();
			expect( recordEvent ).toHaveBeenCalledTimes( 0 );

			await user.click( step1 );

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_mc', {
				action: 'go-to-step1',
				triggered_by: 'stepper-step1-button',
			} );
		} );
	} );
} );
