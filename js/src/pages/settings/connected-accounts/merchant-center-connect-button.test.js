/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MerchantCenterConnectButton from './merchant-center-connect-button';
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';

jest.mock( '~/components/app-button', () =>
	jest
		.fn()
		.mockName( 'AppButton' )
		.mockImplementation( () => null )
);
jest.mock( '~/utils/urls', () => ( {
	getOnboardingUrl: jest.fn().mockName( 'getOnboardingUrl' ),
} ) );

describe( 'MerchantCenterConnectButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		getOnboardingUrl.mockReturnValue( '/google/setup-mc' );
	} );

	it( 'tracks the legacy settings context for continuity', () => {
		render( <MerchantCenterConnectButton /> );

		expect( AppButton.mock.calls[ 0 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				href: '/google/setup-mc',
				eventName: 'gla_set_up_merchant_center_click',
				eventProps: {
					context: 'settings-linked-accounts',
				},
			} )
		);
	} );
} );
