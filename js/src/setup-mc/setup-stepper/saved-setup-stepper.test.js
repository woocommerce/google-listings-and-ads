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
jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest
		.fn()
		.mockName( 'useTargetAudienceFinalCountryCodes' )
		.mockReturnValue( {} )
);
jest.mock(
	'.~/components/free-listings/configure-product-listings/useSettings',
	() => jest.fn().mockName( 'useSettings' ).mockReturnValue( {} )
);
jest.mock( '.~/hooks/useShippingRates', () =>
	jest.fn().mockName( 'useShippingRates' ).mockReturnValue( {} )
);
jest.mock( '.~/hooks/useShippingTimes', () =>
	jest.fn().mockName( 'useShippingTimes' ).mockReturnValue( {} )
);

jest.mock( './setup-accounts', () => jest.fn().mockName( 'SetupAccounts' ) );
jest.mock( '.~/components/free-listings/setup-free-listings', () =>
	jest.fn().mockName( 'SetupFreeListings' )
);
jest.mock( './store-requirements', () =>
	jest.fn().mockName( 'StoreRequirements' )
);
jest.mock( './setup-paid-ads', () => jest.fn().mockName( 'SetupPaidAds' ) );

/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import SavedSetupStepper from './saved-setup-stepper';
import SetupAccounts from './setup-accounts';
import SetupFreeListings from '.~/components/free-listings/setup-free-listings';
import StoreRequirements from './store-requirements';
import SetupPaidAds from './setup-paid-ads';

describe( 'SavedSetupStepper', () => {
	let continueToStep2;
	let continueToStep3;
	let continueToStep4;

	beforeEach( () => {
		SetupAccounts.mockImplementation( ( { onContinue } ) => {
			continueToStep2 = onContinue;
			return null;
		} );

		SetupFreeListings.mockImplementation( ( { onContinue } ) => {
			continueToStep3 = onContinue;
			return null;
		} );

		StoreRequirements.mockImplementation( ( { onContinue } ) => {
			continueToStep4 = onContinue;
			return null;
		} );

		SetupPaidAds.mockReturnValue( null );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	function continueUntilStep4() {
		continueToStep2();
		continueToStep3();
		continueToStep4();
	}

	describe( 'tracks', () => {
		it( 'Should record events after calling back to `onContinue`', () => {
			render( <SavedSetupStepper savedStep="1" /> );

			continueUntilStep4();

			expect( recordEvent ).toHaveBeenCalledTimes( 3 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_mc', {
				action: 'go-to-step2',
				triggered_by: 'step1-continue-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 2, 'gla_setup_mc', {
				action: 'go-to-step3',
				triggered_by: 'step2-continue-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 3, 'gla_setup_mc', {
				action: 'go-to-step4',
				triggered_by: 'step3-continue-button',
			} );
		} );

		it( 'Should record events after clicking step navigation buttons', async () => {
			const user = userEvent.setup();

			render( <SavedSetupStepper savedStep="4" /> );

			const step1 = screen.getByRole( 'button', { name: /accounts/ } );
			const step2 = screen.getByRole( 'button', { name: /listings/ } );
			const step3 = screen.getByRole( 'button', { name: /store/ } );

			// Step 4 -> Step 3 -> Step 2 -> Step 1
			await user.click( step3 );
			await user.click( step2 );
			await user.click( step1 );

			expect( recordEvent ).toHaveBeenCalledTimes( 3 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_mc', {
				action: 'go-to-step3',
				triggered_by: 'stepper-step3-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 2, 'gla_setup_mc', {
				action: 'go-to-step2',
				triggered_by: 'stepper-step2-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 3, 'gla_setup_mc', {
				action: 'go-to-step1',
				triggered_by: 'stepper-step1-button',
			} );

			// Step 4 -> Step 2
			continueUntilStep4();
			recordEvent.mockClear();
			expect( recordEvent ).toHaveBeenCalledTimes( 0 );

			await user.click( step2 );

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_mc', {
				action: 'go-to-step2',
				triggered_by: 'stepper-step2-button',
			} );
		} );
	} );
} );
