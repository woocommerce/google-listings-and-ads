/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SummarySection from '.~/dashboard/summary-section';

// Mimic no data loaded.
jest.mock( './usePerformance', () =>
	jest
		.fn()
		.mockName( 'usePerformance' )
		.mockReturnValue( { data: false, loaded: true } )
);
// Mock currency hooks not to require WooCommerce settings.
jest.mock( '.~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: jest.fn().mockName( 'formatAmount' ),
	} )
);
jest.mock( '.~/hooks/useCurrencyFormat', () => jest.fn() );

describe( 'SummarySection when no data is loaded', () => {
	it( 'Shows no data message for Free Campaigns', async () => {
		const { queryByText } = render( <SummarySection /> );

		expect(
			queryByText(
				"We're having trouble loading this data. Try again later, or track your performance in Google Merchant Center."
			)
		).toBeTruthy();

		const link = queryByText( 'Open Google Merchant Center' );

		expect( link ).toBeTruthy();
		expect( link.href ).toBe(
			'https://merchants.google.com/mc/reporting/dashboard'
		);
	} );
	it( 'Shows no data message for Paid Campaigns', () => {
		const { queryByText } = render( <SummarySection /> );

		expect(
			queryByText(
				"We're having trouble loading this data. Try again later, or track your performance in Google Ads."
			)
		).toBeTruthy();

		const link = queryByText( 'Open Google Ads' );

		expect( link ).toBeTruthy();
		expect( link.href ).toBe( 'https://ads.google.com/' );
	} );
} );
