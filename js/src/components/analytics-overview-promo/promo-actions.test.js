/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
} from './constants';

const REFERRER_QUERY_STRING = `referrer_type=analytics_in_product_placements&referrer_id=${ ANALYTICS_OVERVIEW_PROMO_CONTEXT }`;
const PREFERENCES_STORE_NAMESPACE = 'google-listings-and-ads';

// Mocks are defined outside their factories so the copy of `PromoActions` loaded by
// `jest.isolateModules` and the assertions below share the same instances.
let mockGlaData;
const mockSetPreference = jest.fn();
const mockRecordGlaEvent = jest.fn();

jest.mock( '~/constants', () => ( {
	PREFERENCES_STORE_NAMESPACE: 'google-listings-and-ads',
	get glaData() {
		return mockGlaData;
	},
} ) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: () => ( { set: mockSetPreference } ),
} ) );

jest.mock( '@wordpress/preferences', () => ( {
	__esModule: true,
	store: 'preferences',
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( { href, onClick, children } ) =>
		href ? (
			<a href={ href } onClick={ onClick }>
				{ children }
			</a>
		) : (
			<button onClick={ onClick }>{ children }</button>
		),
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '@woocommerce/components', () => ( {
	Spinner: () => null,
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: ( ...args ) => mockRecordGlaEvent( ...args ),
	REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS:
		'analytics_in_product_placements',
} ) );

jest.mock( '~/utils/urls', () => ( {
	getCreateCampaignUrl: () => '/create-campaign',
	getOnboardingUrl: () => '/onboarding',
	addReferrerParams: ( href, referrerType, referrerId ) =>
		`${ href }?referrer_type=${ referrerType }&referrer_id=${ referrerId }`,
} ) );

/**
 * Loads a fresh copy of `PromoActions`, since it reads `glaData.onboardingComplete` at module load.
 *
 * @param {boolean} onboardingComplete The onboarding state to load the component with.
 * @return {Function} The `PromoActions` component.
 */
const loadPromoActions = ( onboardingComplete ) => {
	mockGlaData = { onboardingComplete };

	let PromoActions;
	jest.isolateModules( () => {
		PromoActions = require( './promo-actions' ).default;
	} );
	return PromoActions;
};

describe( 'PromoActions', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'renders the not-onboarded CTA', () => {
		const PromoActions = loadPromoActions( false );
		render( <PromoActions metricsCase="revenue" /> );

		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toHaveAttribute( 'href', `/onboarding?${ REFERRER_QUERY_STRING }` );
	} );

	test( 'renders the onboarded CTA', () => {
		const PromoActions = loadPromoActions( true );
		render( <PromoActions metricsCase="revenue" /> );

		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute(
			'href',
			`/create-campaign?${ REFERRER_QUERY_STRING }`
		);
	} );

	test( 'persists dismissal when the Dismiss button is clicked', () => {
		const PromoActions = loadPromoActions( false );
		render( <PromoActions metricsCase="revenue" /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( mockSetPreference ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	} );

	test( 'fires the get started click event when not onboarded', () => {
		const PromoActions = loadPromoActions( false );
		render( <PromoActions metricsCase="products" /> );

		fireEvent.click( screen.getByRole( 'link', { name: 'Get started' } ) );

		expect( mockRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_get_started_click',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'products',
			}
		);
	} );

	test( 'fires the launch campaign click event when onboarded', () => {
		const PromoActions = loadPromoActions( true );
		render( <PromoActions metricsCase="products" /> );

		fireEvent.click(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		);

		expect( mockRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_launch_campaign_click',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'products',
			}
		);
	} );

	test( 'fires the dismiss event when the Dismiss button is clicked', () => {
		const PromoActions = loadPromoActions( false );
		render( <PromoActions metricsCase="revenue" /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( mockRecordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_dismiss',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'revenue',
			}
		);
	} );
} );
