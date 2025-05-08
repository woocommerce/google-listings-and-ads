/* global glaData */

/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import PriceBenchmarkSuggestions from './price-benchmark-suggestions';
import ProductComparisonChart from './product-comparison-chart';
import Banner from './banner';
import './index.scss';

const PriceBenchmark = () => {
	const [ dataViewLoaded, setDataViewLoaded ] = useState();

	useEffect( () => {
		if ( dataViewLoaded === undefined ) {
			const script = document.createElement( 'script' );
			script.src = glaData.dataViewsScriptUrl;
			script.async = true;

			script.onload = () => {
				if (
					window.wp.dataviews &&
					typeof window.wp.dataviews?.filterSortAndPaginate ===
						'function'
				) {
					setDataViewLoaded( true );
				} else {
					setDataViewLoaded( false );
				}
			};

			script.onerror = () => {
				setDataViewLoaded( false );
			};

			document.head.appendChild( script );
		}
	}, [ dataViewLoaded ] );

	return (
		<div className="gla-price-benchmark">
			<MainTabNav />

			<Banner />
			<ProductComparisonChart />

			<Card className="gla-price-benchmark__card">
			{dataViewLoaded && (
				<PriceBenchmarkSuggestions isDataViewReady={ dataViewLoaded } />
			)}
			</Card>
		</div>
	);
};

export default PriceBenchmark;
