/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MerchantCenterAccountCard from './index';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);
jest.mock( '~/hooks/useServiceBasedMerchant', () =>
	jest.fn().mockName( 'useServiceBasedMerchant' )
);
jest.mock( '~/components/account-card', () => ( {
	__esModule: true,
	APPEARANCE: { GOOGLE_MERCHANT_CENTER: 'merchant-center' },
	default: function MockAccountCard( { detail, indicator, expandedDetail } ) {
		return (
			<div data-expanded={ String( expandedDetail ) }>
				{ indicator }
				{ detail }
			</div>
		);
	},
} ) );
jest.mock(
	'../account-card-text-detail',
	() =>
		( { children } ) =>
			children
);
jest.mock( '../connected-badge', () => () => <div>Connected badge</div> );
jest.mock( './connect-button', () => () => <div>Connect action</div> );
jest.mock( './service-based-content', () => () => (
	<div>Supported products confirmation</div>
) );

describe( 'MerchantCenterAccountCard', () => {
	beforeEach( () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: {},
			hasGoogleMCConnection: false,
		} );
		useServiceBasedMerchant.mockReturnValue( false );
	} );

	it( 'renders the Connect action for a disconnected supported-products store', () => {
		render( <MerchantCenterAccountCard /> );

		expect( screen.getByText( 'Connect action' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Supported products confirmation' )
		).not.toBeInTheDocument();
	} );

	it( 'renders the expanded confirmation flow for a service-based store', () => {
		useServiceBasedMerchant.mockReturnValue( true );

		const { container } = render( <MerchantCenterAccountCard /> );

		expect(
			screen.getByText( 'Supported products confirmation' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'Connect action' )
		).not.toBeInTheDocument();
		expect( container.firstChild ).toHaveAttribute(
			'data-expanded',
			'true'
		);
	} );

	it( 'renders the connected account when Merchant Center is already connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			googleMCAccount: { id: 123456 },
			hasGoogleMCConnection: true,
		} );
		useServiceBasedMerchant.mockReturnValue( true );

		const { container } = render( <MerchantCenterAccountCard /> );

		expect( screen.getByText( 'Connected badge' ) ).toBeInTheDocument();
		expect( screen.getByText( '123456' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Supported products confirmation' )
		).not.toBeInTheDocument();
		expect( container.firstChild ).toHaveAttribute(
			'data-expanded',
			'false'
		);
	} );
} );
