/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ClaimAdsAccount from './claim-ads-account';
import './connected-ads-account-detail.scss';

/**
 * Renders details related to a connected Google Ads account, including the option to claim the account and a notice indicating whether conversion measurement has been set up.
 * @param {Object} props Component props.
 * @param {boolean} props.claimGoogleAdsAccount Whether the user should claim the Google Ads account.
 * @param {boolean} props.showConversionMeasurementNotice Whether to show the conversion measurement notice.
 */
const ConnectedAdsAccountDetail = ( {
	claimGoogleAdsAccount,
	showConversionMeasurementNotice,
} ) => {
	if ( ! claimGoogleAdsAccount && ! showConversionMeasurementNotice ) {
		return null;
	}

	return (
		<div className="gla-connected-ads-account-detail">
			{ claimGoogleAdsAccount && <ClaimAdsAccount /> }

			{ showConversionMeasurementNotice && (
				<Notice isDismissible={ false } status="success">
					{ __(
						'Google Ads conversion measurement has been set up for your store.',
						'google-listings-and-ads'
					) }
				</Notice>
			) }
		</div>
	);
};

export default ConnectedAdsAccountDetail;
