jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );
jest.mock( './setup-accounts', () => jest.fn().mockName( 'SetupAccounts' ) );
jest.mock( '.~/components/paid-ads/ads-campaign', () =>
	jest.fn().mockName( 'AdsCampaign' )
);

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
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'tracks', () => {
		it( 'Should record events after calling back to `onContinue`', async () => {
			render( <AdsStepper /> );

			await continueToStep2();

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenNthCalledWith( 1, 'gla_setup_ads', {
				action: 'go-to-step2',
				triggered_by: 'step1-continue-button',
			} );
		} );

		it( 'Should record events after clicking step navigation buttons', async () => {
			const user = userEvent.setup();

			render( <AdsStepper /> );

			const step1 = screen.getByRole( 'button', { name: /accounts/ } );

			// Step 2 -> Step 1
			await continueToStep2();
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
