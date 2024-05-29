/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ClaimAccountButton from './claim-account-button';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

jest.mock( '.~/hooks/useGoogleAdsAccountStatus', () =>
	jest.fn().mockName( 'useGoogleAdsAccountStatus' )
);

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

	it( 'should forward onClick callback', () => {
		const onClick = jest.fn();
		render( <ClaimAccountButton onClick={ onClick } /> );

		expect( onClick ).toHaveBeenCalledTimes( 0 );

		userEvent.click( screen.getByRole( 'button' ) );

		expect( onClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should open the invitation link in a pop-up window', () => {
		render( <ClaimAccountButton /> );

		expect( windowOpen ).toHaveBeenCalledTimes( 0 );

		userEvent.click( screen.getByRole( 'button' ) );

		expect( windowOpen ).toHaveBeenCalledTimes( 1 );
		expect( windowOpen ).toHaveBeenCalledWith(
			'https://example.com',
			'_blank',
			// Ignore the value of the window features.
			expect.any( String )
		);
	} );
} );
