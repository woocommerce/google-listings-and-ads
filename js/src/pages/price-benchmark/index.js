/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';
import AppSpinner from '~/components/app-spinner';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import Banner from './banner';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import MainTabNav from '~/components/main-tab-nav';
import PriceBenchmarkSuggestions from './price-benchmark-suggestions';
import ProductComparisonChart from './product-comparison-chart';
import './index.scss';

const PriceBenchmark = () => {
	const dataViewStatus = useDataViewsScript();

	return (
		<div className="gla-price-benchmark">
			<ExperienceRatingBanner />
			<MainTabNav />
			<Banner />
			<ProductComparisonChart />

			{ dataViewStatus === 'failed' && (
				<AppNotice
					status="warning"
					isDismissible={ false }
					className="gla-price-benchmark__error-message"
				>
					{ __(
						'There was an error loading the price benchmark suggestions.',
						'google-listings-and-ads'
					) }
				</AppNotice>
			) }

			{ dataViewStatus !== 'failed' && (
				<Card className="gla-price-benchmark__card">
					{ dataViewStatus === 'loading' && <AppSpinner /> }
					{ dataViewStatus === 'ready' && (
						<PriceBenchmarkSuggestions />
					) }
				</Card>
			) }
		</div>
	);
};

export default PriceBenchmark;
