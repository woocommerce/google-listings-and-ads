/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PerformanceCard from './performance-card';

jest.mock( '~/components/raise-budget-recommendation-banner', () =>
	jest.fn().mockName( 'RaiseBudgetRecommendationBanner' )
);

describe( 'Performance Card', () => {
	it( 'Renders given no data message', () => {
		const { queryByText } = render(
			<PerformanceCard
				data={ false }
				loaded={ true }
				noDataMessage={ {
					body: 'Body Text',
					link: 'https://example.com/link',
					eventName: 'tracking_event',
					buttonLabel: 'Click Me!',
				} }
			/>
		);

		expect( queryByText( 'Body Text' ) ).toBeTruthy();

		const link = queryByText( 'Click Me!' );

		expect( link ).toBeTruthy();
		expect( link.href ).toBe( 'https://example.com/link' );
	} );
} );
