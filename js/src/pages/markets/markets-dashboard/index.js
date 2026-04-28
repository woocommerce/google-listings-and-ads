/**
 * External dependencies
 */
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import useSettings from '~/hooks/useSettings';
import MarketsHeader from './markets-header';
import MarketDataViews from './market-data-views';
import './index.scss';

const MarketsDashboard = () => {
	const { settings } = useSettings();
	const shippingRate = settings?.shipping_rate;

	const { dataViewIsLoading, dataViewHasFailed, dataViewIsReady } =
		useDataViewsScript();

	return (
		<div className="gla-markets-dashboard">
			<MarketsHeader shippingRate={ shippingRate } />

			{ ! dataViewHasFailed && (
				<Card className="gla-markets-dashboard__card">
					{ dataViewIsLoading && <AppSpinner /> }
					{ dataViewIsReady && (
						<MarketDataViews shippingRate={ shippingRate } />
					) }
				</Card>
			) }
		</div>
	);
};

export default MarketsDashboard;
