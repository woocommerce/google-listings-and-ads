/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRICE_BENCHMARK_CHART_COLORS } from '~/constants';
import usePriceBenchmarkSummary from '~/hooks/usePriceBenchmarkSummary';
import HorizontalStackedBar from '~/components/horizontal-stacked-bar';

/**
 * ProductComparisonChart component.
 *
 * This component renders a horizontal stacked bar chart that compares the pricing
 * of the user's products to competitors. It uses data from the price benchmark summary
 * to display segments representing different price categories.
 *
 * @return {JSX.Element|null} The rendered HorizontalStackedBar component or null if
 * the data is not yet resolved or unavailable.
 */
const ProductComparisonChart = () => {
	const { summary, hasFinishedResolution } = usePriceBenchmarkSummary();

	if ( ! hasFinishedResolution || ! summary ) {
		return null;
	}

	const segments = [
		{
			id: 'similar',
			label: __( 'Similar', 'google-listings-and-ads' ),
			value: summary.price_similar,
			color: PRICE_BENCHMARK_CHART_COLORS.SIMILAR,
		},
		{
			id: 'no-price-benchmark',
			label: __( 'No price benchmark', 'google-listings-and-ads' ),
			color: PRICE_BENCHMARK_CHART_COLORS.UNKNOWN,
			value: summary.price_unknown,
		},
		{
			id: 'cheaper',
			label: __( 'Cheaper', 'google-listings-and-ads' ),
			color: PRICE_BENCHMARK_CHART_COLORS.LOWER,
			value: summary.price_lower,
		},
		{
			id: 'more-expensive',
			label: __( 'More Expensive', 'google-listings-and-ads' ),
			color: PRICE_BENCHMARK_CHART_COLORS.HIGHER,
			value: summary.price_higher,
		},
	];

	return (
		<HorizontalStackedBar
			title={ __(
				'How Your Products Compare to Competitors',
				'google-listings-and-ads'
			) }
			segments={ segments }
			className="gla-price-benchmark__comparison-chart"
		/>
	);
};

export default ProductComparisonChart;
