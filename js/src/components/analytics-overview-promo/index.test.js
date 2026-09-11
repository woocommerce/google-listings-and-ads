/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import usePreference from '~/hooks/usePreference';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import { recordGlaEvent } from '~/utils/tracks';
import { ANALYTICS_OVERVIEW_PROMO_CONTEXT } from './constants';
import AnalyticsOverviewPromo from './index';

jest.mock( '@wordpress/components', () => ( {
	Card: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
	CardBody: ( { children } ) => <div>{ children }</div>,
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexBlock: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '~/hooks/useGoogleAdsAccountReady', () =>
	jest.fn().mockName( 'useGoogleAdsAccountReady' )
);

jest.mock( '~/hooks/useHasRecentAdSpend', () =>
	jest.fn().mockName( 'useHasRecentAdSpend' )
);

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useProductRevenueMetricsDown', () => jest.fn() );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( () => ( {
		woocommerce_default_date_range: 'period=month&compare=previous_period',
	} ) ),
} ) );

jest.mock( './promo-text', () => ( { metricsCase, isGoogleAdsReady } ) => (
	<div data-testid="promo-text">
		{ metricsCase }:{ String( isGoogleAdsReady ) }
	</div>
) );

jest.mock( './promo-actions', () => ( { isGoogleAdsReady, trackingCase } ) => (
	<div data-testid="promo-actions">
		{ String( isGoogleAdsReady ) }:{ trackingCase }
	</div>
) );

describe( 'AnalyticsOverviewPromo', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		usePreference.mockReturnValue( false );
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: false } );
		useHasRecentAdSpend.mockReturnValue( {
			hasFinishedResolution: true,
			hasAdSpend: false,
		} );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'revenue',
		} );
	} );

	test( 'renders nothing while the Google Ads readiness state is still resolving', () => {
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: null } );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing while the recent ad spend state is still resolving', () => {
		useHasRecentAdSpend.mockReturnValue( {
			hasFinishedResolution: false,
			hasAdSpend: false,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when there has been recent ad spend', () => {
		useHasRecentAdSpend.mockReturnValue( {
			hasFinishedResolution: true,
			hasAdSpend: true,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing while the metrics are still resolving', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: false,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when the promo has been dismissed', () => {
		usePreference.mockReturnValue( true );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when metrics are not trending down', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders the card with PromoText and PromoActions once every condition is met', () => {
		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect(
			container.querySelector( '.gla-analytics-overview-promo' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'promo-text' ) ).toHaveTextContent(
			'revenue:false'
		);
		expect( screen.getByTestId( 'promo-actions' ) ).toHaveTextContent(
			'false:sales_orders'
		);
	} );

	test( 'fires the view event once when the promo is shown', () => {
		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_view',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'sales_orders',
			}
		);
	} );

	test( 'fires the view event again when the shown case changes without hiding', () => {
		const { rerender } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );

		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'products',
		} );
		rerender( <AnalyticsOverviewPromo query={ {} } /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordGlaEvent ).toHaveBeenNthCalledWith(
			2,
			'gla_analytics_in_product_placements_view',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'products_sold',
			}
		);
	} );

	test( 'does not fire the view event again when rerendered with the same case', () => {
		const { rerender } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );

		rerender( <AnalyticsOverviewPromo query={ {} } /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'does not fire the view event when the promo is not shown', () => {
		usePreference.mockReturnValue( true );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( recordGlaEvent ).not.toHaveBeenCalled();
	} );
} );
