/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ClaimAccountButton from './claim-account-button';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import expectEventWithPropertiesFilter from '.~/tests/expectEventWithPropertiesFilter';

jest.mock( '.~/hooks/useGoogleAdsAccountStatus', () =>
	jest.fn().mockName( 'useGoogleAdsAccountStatus' )
);

jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );

describe( 'ClaimAccountButton', () => {
	let windowOpen;

	beforeEach( () => {
		windowOpen = jest.spyOn( global, 'open' );

		useGoogleAdsAccountStatus.mockReturnValue( {
			inviteLink: 'https://example.com',
		} );
	} );

	afterEach( () => {
		windowOpen.mockReset();
		recordEvent.mockClear();
	} );

	it( 'should render the specified text in the button', () => {
		render(
			<ClaimAccountButton>
				Claim account in example.com
			</ClaimAccountButton>
		);

		expect( screen.getByRole( 'button' ) ).toHaveTextContent(
			'Claim account in example.com'
		);
	} );

	it( 'should forward onClick callback', async () => {
		const onClick = jest.fn();
		render( <ClaimAccountButton onClick={ onClick } /> );

		expect( onClick ).toHaveBeenCalledTimes( 0 );

		await userEvent.click( screen.getByRole( 'button' ) );

		expect( onClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should open the invitation link in a pop-up window', async () => {
		render( <ClaimAccountButton /> );

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

	it( 'should record click events and be aware of extra event properties from filters', async () => {
		await expectEventWithPropertiesFilter(
			ClaimAccountButton,
			FILTER_ONBOARDING,
			() => screen.getByRole( 'button' ),
			'gla_open_ads_account_claim_invitation_button_click',
			[
				{ context: 'setup-mc', step: '1' },
				{ context: 'setup-ads', step: '2' },
			]
		);
	} );
} );
