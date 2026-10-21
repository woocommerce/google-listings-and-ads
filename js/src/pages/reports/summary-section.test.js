/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SummarySection from './summary-section';
import MetricNumber from './metric-number';

jest.mock( './metric-number', () =>
	jest.fn( () => null ).mockName( 'MetricNumber' )
);

jest.mock( '~/hooks/useUrlQuery', () => ( {
	__esModule: true,
	default: () => ( {} ),
} ) );

jest.mock( '@woocommerce/components', () => ( {
	SummaryList: ( { children } ) => children(),
	SummaryListPlaceholder: () => null,
} ) );

const totals = {
	clicks: { value: 10, prevValue: 5, delta: 100 },
	position: { value: 12, prevValue: 15, delta: -20 },
};

describe( 'SummarySection', () => {
	beforeEach( () => {
		MetricNumber.mockClear();
	} );

	it( 'renders each metric without a reversed trend or a tooltip by default', () => {
		render(
			<SummarySection
				loaded
				metrics={ [ { key: 'clicks', label: 'Clicks' } ] }
				totals={ totals }
				trackEventId="reports-programs"
			/>
		);

		const props = MetricNumber.mock.calls[ 0 ][ 0 ];
		expect( props.data ).toBe( totals.clicks );
		expect( props.reverseTrend ).toBeUndefined();
		expect( props.labelTooltipText ).toBeUndefined();
	} );

	it( 'passes a metric’s reversed trend and tooltip on to its card', () => {
		render(
			<SummarySection
				loaded
				metrics={ [
					{ key: 'clicks', label: 'Clicks' },
					{
						key: 'position',
						label: 'Average position',
						reverseTrend: true,
						labelTooltipText: 'Lower is better.',
					},
				] }
				totals={ totals }
				trackEventId="reports-search-console"
			/>
		);

		const positionProps = MetricNumber.mock.calls.find(
			( [ props ] ) => props.metric.key === 'position'
		)[ 0 ];
		expect( positionProps.reverseTrend ).toBe( true );
		expect( positionProps.labelTooltipText ).toBe( 'Lower is better.' );
	} );
} );
