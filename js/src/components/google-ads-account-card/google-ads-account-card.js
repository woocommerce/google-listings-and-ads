/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import DisabledCard from './disabled-card';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

export default function GoogleAdsAccountCard() {
	const {
		google,
		scope,
		hasFinishedResolution: hasResolvedGoogleAccount,
	} = useGoogleAccount();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();

	const {
		hasAccess,
		step,
		hasFinishedResolution: hasResolvedAdsAccountStatus,
	} = useGoogleAdsAccountStatus();

	const showSuccessNotice =
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ||
		googleAdsAccount?.step === 'link_merchant';

	if (
		! hasResolvedGoogleAccount ||
		! hasResolvedGoogleAdsAccount ||
		! hasResolvedAdsAccountStatus
	) {
		return <SpinnerCard />;
	}

	if ( ! google || google.active === 'no' ) {
		return <DisabledCard />;
	}

	if ( ! scope.adsRequired ) {
		return <AuthorizeAds additionalScopeEmail={ google.email } />;
	}

	if (
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED ||
		hasAccess !== true ||
		( hasAccess === true && step === 'conversion_action' )
	) {
		return <NonConnected />;
	}

	return (
		<ConnectedGoogleAdsAccountCard googleAdsAccount={ googleAdsAccount }>
			{ showSuccessNotice ? (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'Conversion measurement has been set up. You can create a campaign later.',
						'google-listings-and-ads'
					) }
				</Notice>
			) : null }
		</ConnectedGoogleAdsAccountCard>
	);
}
