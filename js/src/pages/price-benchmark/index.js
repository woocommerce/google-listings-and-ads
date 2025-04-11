/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import TableTypeNavigation from './table-type-navigation';
import { TABLE_TYPE_ADJUSTMENTS, TABLE_TYPE_SUGGESTIONS } from './constants';
import PriceBenchmarkAdjustments from './price-benchmark-adjustments';
import PriceBenchmarkSuggestions from './price-benchmark-suggestions';
import ProductComparisonChart from './product-comparison-chart';
import './index.scss';

const PriceBenchmark = () => {
	const tableType = getQuery()?.tableType || TABLE_TYPE_SUGGESTIONS;

	return (
		<div className="gla-price-benchmark">
			<MainTabNav />

			<ProductComparisonChart />

			<Card className="gla-price-benchmark__card">
				<TableTypeNavigation tableType={ tableType } />
				{ tableType === TABLE_TYPE_ADJUSTMENTS && (
					<PriceBenchmarkAdjustments />
				) }
				{ tableType === TABLE_TYPE_SUGGESTIONS && (
					<PriceBenchmarkSuggestions />
				) }
			</Card>
		</div>
	);
};

export default PriceBenchmark;
