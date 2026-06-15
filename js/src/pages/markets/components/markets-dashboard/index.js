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
import useSettings from '~/hooks/useSettings';
import MarketsHeader from '../markets-header';
import MarketDataViews from '../market-data-views';
import MultilingualFlatShippingNotice from './multilingual-flat-shipping-notice';
import './index.scss';

const MarketsDashboard = () => {
	const { settings } = useSettings();
	const shippingRate = settings?.shipping_rate;

	const dataViewStatus = useDataViewsScript();

	return (
		<div className="gla-markets-dashboard">
			<MultilingualFlatShippingNotice />

			<MarketsHeader shippingRate={ shippingRate } />

			{ dataViewStatus === 'failed' && (
				<AppNotice
					status="warning"
					isDismissible={ false }
					className="gla-markets-dashboard__error-message"
				>
					{ __(
						'There was an error loading the markets dashboard.',
						'google-listings-and-ads'
					) }
				</AppNotice>
			) }

			{ dataViewStatus !== 'failed' && (
				<Card className="gla-markets-dashboard__card">
					{ dataViewStatus === 'loading' && <AppSpinner /> }
					{ dataViewStatus === 'ready' && <MarketDataViews /> }
				</Card>
			) }
		</div>
	);
};

export default MarketsDashboard;
