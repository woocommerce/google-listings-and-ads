/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import { ANALYTICS_OVERVIEW_PROMO_CONTEXT } from './constants';
import PromoActions from './promo-actions';

const REFERRER_QUERY_STRING = `referrer_type=in_product_placements&referrer_id=${ ANALYTICS_OVERVIEW_PROMO_CONTEXT }`;

jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/utils/urls', () => ( {
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
	getSetupAdsUrl: jest.fn( () => '/setup-ads' ),
} ) );

describe( 'PromoActions', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'renders the not-ready CTA', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="sales_orders"
				onDismiss={ jest.fn() }
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
				onDismiss={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute(
			'href',
			`/create-campaign?${ REFERRER_QUERY_STRING }`
		);
	} );

	test( 'calls onDismiss when the Dismiss button is clicked', () => {
		const onDismiss = jest.fn();
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="sales_orders"
				onDismiss={ onDismiss }
			/>
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( onDismiss ).toHaveBeenCalled();
	} );

	test( 'fires the get started click event when not ready', () => {
		render(
			<PromoActions
				isGoogleAdsReady={ false }
				trackingCase="products_sold"
				onDismiss={ jest.fn() }
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
				onDismiss={ jest.fn() }
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
				onDismiss={ jest.fn() }
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
