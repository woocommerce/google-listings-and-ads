/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import AppNotice from '~/components/app-notice';
import AppSpinner from '~/components/app-spinner';
import Banner from './banner';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import MainTabNav from '~/components/main-tab-nav';
import PriceBenchmarkSuggestions from './price-benchmark-suggestions';
import ProductComparisonChart from './product-comparison-chart';
import './index.scss';

const PriceBenchmark = () => {
	const [ dataViewLoaded, setDataViewLoaded ] = useState(
		window.wp?.dataviews
	);
	const { dataViewsScriptUrl } = glaData;

	useEffect( () => {
		if ( dataViewLoaded === undefined && dataViewsScriptUrl ) {
			const script = document.createElement( 'script' );
			script.src = dataViewsScriptUrl;
			script.async = true;

			script.onload = () => {
				setDataViewLoaded(
					typeof window.wp?.dataviews?.filterSortAndPaginate ===
						'function'
				);
			};

			script.onerror = () => {
				setDataViewLoaded( false );
			};

			document.head.appendChild( script );
		}

		return () => {
			if ( dataViewLoaded === false ) {
				setDataViewLoaded( undefined );
			}
		};
	}, [ dataViewLoaded, dataViewsScriptUrl ] );

	return (
		<div className="gla-price-benchmark">
			<ExperienceRatingBanner />
			<MainTabNav />
			<Banner />
			<ProductComparisonChart />

			{ dataViewLoaded === false && (
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

			{ ( dataViewLoaded || dataViewLoaded === undefined ) && (
				<Card className="gla-price-benchmark__card">
					{ dataViewLoaded === undefined && <AppSpinner /> }

					{ dataViewLoaded && <PriceBenchmarkSuggestions /> }
				</Card>
			) }
		</div>
	);
};

export default PriceBenchmark;
