/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Settings from './';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn().mockReturnValue( {} ),
} ) );

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockReturnValue( { google: { active: 'yes' } } )
);

jest.mock( '~/hooks/useGoogleMCAccount', () => jest.fn() );

jest.mock( '~/hooks/useUpdateRestAPIAuthorizeStatusByUrlQuery', () =>
	jest.fn()
);

jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockReturnValue( {
		targetAudience: undefined,
		getFinalCountries: jest.fn(),
	} )
);

jest.mock( '~/data', () => ( {
	useAppDispatch: jest
		.fn()
		.mockReturnValue( { saveTargetAudience: jest.fn() } ),
} ) );

jest.mock( '~/components/main-tab-nav', () => () => null );
jest.mock( '~/components/tours/rebranding-tour', () => () => null );
jest.mock( '~/components/experience-rating-banner', () => () => null );
jest.mock( '~/components/target-audience-section', () => () => null );
jest.mock( '~/components/contact-information', () => ( {
	ContactInformationPreview: () => null,
} ) );
jest.mock( './setup-tax-rate', () => () => null );
jest.mock( './shipping-rate-settings', () => () => null );
jest.mock( './linked-accounts', () => () => null );
jest.mock( './reconnect-wpcom-account', () => () => null );
jest.mock( './reconnect-google-account', () => () => null );
jest.mock( './edit-store-address', () => () => null );
jest.mock(
	'./enhanced-conversions/setup-enhanced-conversions',
	() => () => null
);
jest.mock( './reviews', () => ( {
	GoogleCustomerReviewsSettings: () => <div data-testid="reviews-settings" />,
} ) );

beforeAll( () => {
	// Used in the js/src/hooks/useMenuEffect.js dependency.
	window.wpNavMenuClassChange = jest.fn();
} );

afterEach( () => {
	useGoogleMCAccount.mockReset();
} );

describe( 'Settings page — reviews settings visibility gating', () => {
	it( 'renders the reviews settings when Merchant Center is connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );

		render( <Settings /> );

		expect( screen.getByTestId( 'reviews-settings' ) ).toBeInTheDocument();
	} );

	it( 'hides the reviews settings when Merchant Center is not connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );

		render( <Settings /> );

		expect(
			screen.queryByTestId( 'reviews-settings' )
		).not.toBeInTheDocument();
	} );

	it( 'hides the reviews settings while Merchant Center connection is still resolving', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: false,
			hasGoogleMCConnection: false,
		} );

		render( <Settings /> );

		expect(
			screen.queryByTestId( 'reviews-settings' )
		).not.toBeInTheDocument();
	} );
} );
