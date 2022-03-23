/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PerformanceCard from '.~/dashboard/summary-section/performance-card';

describe( 'Performance Card', () => {
	it( 'Shows Campaign No Data for Free Campaigns', () => {
		const { queryByText } = render(
			<PerformanceCard
				loaded={ true }
				data={ false }
				campaignType="free"
			/>
		);

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

	it( 'Shows Campaign No Data for Paid Campaigns', () => {
		const { queryByText } = render(
			<PerformanceCard
				loaded={ true }
				data={ false }
				campaignType="paid"
			/>
		);

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
