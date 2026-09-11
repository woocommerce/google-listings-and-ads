/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import { ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY } from './constants';
import PromoActions from './promo-actions';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/preferences', () => ( {
	__esModule: true,
	store: 'preferences',
} ) );

jest.mock( '@wordpress/components', () => ( {
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock(
	'~/components/app-button',
	() =>
		( { children, href, onClick } ) =>
			href ? (
				<a href={ href } onClick={ onClick }>
					{ children }
				</a>
			) : (
				<button onClick={ onClick }>{ children }</button>
			)
);

jest.mock( '~/utils/urls', () => ( {
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
	getSetupAdsUrl: jest.fn( () => '/setup-ads' ),
} ) );

describe( 'PromoActions', () => {
	const setPreference = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		useDispatch.mockReturnValue( { set: setPreference } );
	} );

	test( 'renders the not-ready CTA', () => {
		render( <PromoActions isGoogleAdsReady={ false } /> );

		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toHaveAttribute( 'href', '/setup-ads' );
	} );

	test( 'renders the ready CTA', () => {
		render( <PromoActions isGoogleAdsReady={ true } /> );

		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute( 'href', '/create-campaign' );
	} );

	test( 'persists dismissal when the Dismiss button is clicked', () => {
		render( <PromoActions isGoogleAdsReady={ false } /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( setPreference ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	} );
} );
