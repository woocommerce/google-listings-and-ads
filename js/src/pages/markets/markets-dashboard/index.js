/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';
import AppSpinner from '~/components/app-spinner';
import TrackableLink from '~/components/trackable-link';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import useSettings from '~/hooks/useSettings';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { getSettingsUrl } from '~/utils/urls';
import MarketsHeader from '../markets-header';
import MarketDataViews from '../market-data-views';
import './index.scss';

const MarketsDashboard = () => {
	const { settings } = useSettings();
	const shippingRate = settings?.shipping_rate;

	const dataViewStatus = useDataViewsScript();

	return (
		<div className="gla-markets-dashboard">
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

			{ glaData.isMultiLingualStore &&
				shippingRate === SHIPPING_RATE_METHOD.FLAT && (
					<Notice
						status="warning"
						isDismissible={ false }
						className="gla-markets-dashboard__multilingual-notice"
					>
						{ createInterpolateElement(
							__(
								'Your current shipping setup is not compatible with multilingual feeds. You have "I will manually enter my shipping rates" selected. To use multilingual feeds, switch to a different shipping setup in <link>Settings</link>.',
								'google-listings-and-ads'
							),
							{
								link: (
									<TrackableLink
										type="wc-admin"
										href={ getSettingsUrl() }
										eventName="gla_multilingual_flat_notice_settings_link_click"
									/>
								),
							}
						) }
					</Notice>
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
