/**
 * Internal dependencies
 */
import getPromoContent from './get-promo-content'; // eslint-disable-line import/no-unresolved

/**
 * `getPromoContent( metricsCase, isConnected )` selects the copy variant for the Analytics
 * Overview promo and returns `{ headline, body, ctaLabel, ctaPath, referrerId }`. `metricsCase`
 * is the value returned by `useProductRevenueMetricsDown` ('revenue' | 'products').
 */

const REVENUE = 'revenue';
const PRODUCTS = 'products';

describe( 'getPromoContent', () => {
	it( 'returns the Case 1 (revenue), not-onboarded variant', () => {
		expect( getPromoContent( REVENUE, false ) ).toEqual(
			expect.objectContaining( {
				headline: 'Sales a bit slow? Reach more shoppers with Google.',
				body: 'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
				ctaLabel: 'Get started',
				ctaPath: 'setup-mc',
				referrerId: 'analytics-overview-promo-get-started',
			} )
		);
	} );

	it( 'returns the Case 1 (revenue), connected variant', () => {
		expect( getPromoContent( REVENUE, true ) ).toEqual(
			expect.objectContaining( {
				headline:
					'Sales a bit slow? Give your products a boost with Google.',
				body: 'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
				ctaLabel: 'Launch a campaign',
				ctaPath: 'setup-ads',
				referrerId: 'analytics-overview-promo-launch-campaign',
			} )
		);
	} );

	it( 'returns the Case 2 (products), not-onboarded variant', () => {
		expect( getPromoContent( PRODUCTS, false ) ).toEqual(
			expect.objectContaining( {
				headline:
					'Selling fewer items than usual? Reach more shoppers with Google.',
				body: 'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
				ctaLabel: 'Get started',
				ctaPath: 'setup-mc',
				referrerId: 'analytics-overview-promo-get-started',
			} )
		);
	} );

	it( 'returns the Case 2 (products), connected variant', () => {
		expect( getPromoContent( PRODUCTS, true ) ).toEqual(
			expect.objectContaining( {
				headline:
					'Selling fewer items than usual? Give your products a boost with Google.',
				body: 'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
				ctaLabel: 'Launch a campaign',
				ctaPath: 'setup-ads',
				referrerId: 'analytics-overview-promo-launch-campaign',
			} )
		);
	} );
} );
