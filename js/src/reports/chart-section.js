/**
 * External dependencies
 */
import { Chart } from '@woocommerce/components';

export default function ChartSection( { chartData } ) {
	return (
		<Chart
			data={ chartData }
			title="Conversions"
			layout="time-comparison"
			interactiveLegend="false"
			showHeaderControls="false"
		/>
	);
}
