jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );
jest.mock( './setup-accounts', () => jest.fn().mockName( 'SetupAccounts' ) );
jest.mock( '.~/components/paid-ads/ads-campaign', () =>
	jest.fn().mockName( 'AdsCampaign' )
);
jest.mock( './setup-billing', () => jest.fn().mockName( 'SetupBilling' ) );

/**
 * External dependencies
 */
import { screen, render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AdsStepper from './';
import SetupAccounts from './setup-accounts';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import SetupBilling from './setup-billing';

describe( 'AdsStepper', () => {
	let continueToStep2;
	let continueToStep3;

	beforeEach( () => {
		SetupAccounts.mockImplementation( ( { onContinue } ) => {
			continueToStep2 = onContinue;
			return null;
		} );

		AdsCampaign.mockImplementation( ( { continueButton } ) => {
			// Mock the rendering of the ContinueButton
			const mockContinueButton = continueButton( {} );
			continueToStep3 = mockContinueButton.props.onClick;
			return null;
		} );

		SetupBilling.mockReturnValue( null );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	async function continueUntilStep3() {
		continueToStep2();

		// Wait for stepper content to be rendered.
		await waitFor( () => {
			expect( continueToStep3 ).toBeDefined();
		} );

		continueToStep3();
	}

	describe( 'tracks', () => {
		it( 'Should record events after calling back to `onContinue`', async () => {
			render( <AdsStepper /> );

			await continueUntilStep3();

			expect( recordEvent ).toHaveBeenCalledTimes( 2 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_ads', {
				action: 'go-to-step2',
				triggered_by: 'step1-continue-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 2, 'gla_setup_ads', {
				action: 'go-to-step3',
				triggered_by: 'step2-continue-button',
			} );
		} );

		it( 'Should record events after clicking step navigation buttons', async () => {
			const user = userEvent.setup();

			render( <AdsStepper /> );

			const step1 = screen.getByRole( 'button', { name: /accounts/ } );
			const step2 = screen.getByRole( 'button', { name: /campaign/ } );

			// Step 3 -> Step 2 -> Step 1
			await continueUntilStep3();
			recordEvent.mockClear();
			expect( recordEvent ).toHaveBeenCalledTimes( 0 );

			await user.click( step2 );
			await user.click( step1 );

			expect( recordEvent ).toHaveBeenCalledTimes( 2 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_ads', {
				action: 'go-to-step2',
				triggered_by: 'stepper-step2-button',
			} );
			expect( recordEvent ).toHaveBeenNthCalledWith( 2, 'gla_setup_ads', {
				action: 'go-to-step1',
				triggered_by: 'stepper-step1-button',
			} );

			// Step 3 -> Step 1
			await continueUntilStep3();
			recordEvent.mockClear();
			expect( recordEvent ).toHaveBeenCalledTimes( 0 );

			await user.click( step1 );

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_ads', {
				action: 'go-to-step1',
				triggered_by: 'stepper-step1-button',
			} );
		} );
	} );
} );
