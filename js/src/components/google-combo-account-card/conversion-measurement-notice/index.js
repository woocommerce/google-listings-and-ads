/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppNotice from '.~/components/app-notice';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

const ConversionMeasurementNotice = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	const showSuccessNotice =
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ||
		googleAdsAccount?.step === 'link_merchant';

	if ( ! showSuccessNotice ) {
		return null;
	}

	return (
		<AppNotice status="success" isDismissible={ false }>
			{ __(
				'Google Ads conversion measurement has been set up for your store.',
				'google-listings-and-ads'
			) }
		</AppNotice>
	);
};

export default ConversionMeasurementNotice;
