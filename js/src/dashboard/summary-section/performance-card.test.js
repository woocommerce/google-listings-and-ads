/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PerformanceCard from '.~/dashboard/summary-section/performance-card';

describe( 'Performance Card', () => {
	it( 'Renders given no data message', () => {
		const { queryByText } = render(
			<PerformanceCard
				loaded={ true }
				data={ false }
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
