/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import AppSpinner from '~/components/app-spinner';
import useSettings from '~/hooks/useSettings';
import MarketsHeader from './markets-header';
import MarketDataViews from './market-data-views';
import './index.scss';

const MarketsDashboard = () => {
	const { settings } = useSettings();
	const shippingRate = settings?.shipping_rate;

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

	const isLoading = dataViewLoaded === undefined;
	const hasFailed = dataViewLoaded === false;

	return (
		<div className="gla-markets-dashboard">
			<MarketsHeader shippingRate={ shippingRate } />

			{ ! hasFailed && (
				<Card className="gla-markets-dashboard__card">
					{ isLoading && <AppSpinner /> }
					{ dataViewLoaded && (
						<MarketDataViews shippingRate={ shippingRate } />
					) }
				</Card>
			) }
		</div>
	);
};

export default MarketsDashboard;
