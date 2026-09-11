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
import { recordGlaEvent } from '~/utils/tracks';
import {
	ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
} from './constants';
import PromoActions from './promo-actions';

const REFERRER_QUERY_STRING = `referrer_type=analytics_in_product_placements&referrer_id=${ ANALYTICS_OVERVIEW_PROMO_CONTEXT }`;

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
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
	recordGlaEvent: jest.fn(),
	REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS:
		'analytics_in_product_placements',
} ) );

jest.mock( '~/utils/urls', () => ( {
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
	getSetupAdsUrl: jest.fn( () => '/setup-ads' ),
	addReferrerParams: jest.fn(
		( href, referrerType, referrerId ) =>
			`${ href }?referrer_type=${ referrerType }&referrer_id=${ referrerId }`
	),
} ) );

describe( 'PromoActions', () => {
	const setPreference = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		useDispatch.mockReturnValue( { set: setPreference } );
	} );

	test( 'renders the not-ready CTA', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="sales_orders"
			/>
		);

		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toHaveAttribute( 'href', `/setup-ads?${ REFERRER_QUERY_STRING }` );
	} );

	test( 'renders the ready CTA', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ true }
				trackingCase="sales_orders"
			/>
		);

		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute(
			'href',
			`/create-campaign?${ REFERRER_QUERY_STRING }`
		);
	} );

	test( 'persists dismissal when the Dismiss button is clicked', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="sales_orders"
			/>
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( setPreference ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	} );

	test( 'fires the get started click event when not ready', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="products_sold"
			/>
		);

		fireEvent.click( screen.getByRole( 'link', { name: 'Get started' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_get_started_click',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'products_sold',
			}
		);
	} );

	test( 'fires the launch campaign click event when ready', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ true }
				trackingCase="products_sold"
			/>
		);

		fireEvent.click(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_launch_campaign_click',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'products_sold',
			}
		);
	} );

	test( 'fires the dismiss event when the Dismiss button is clicked', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="sales_orders"
			/>
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_dismiss',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'sales_orders',
			}
		);
	} );
} );
