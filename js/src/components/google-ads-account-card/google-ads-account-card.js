/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

export default function GoogleAdsAccountCard() {
	const {
		google,
		scope,
		isResolving: isResolvingGoogleAccount,
	} = useGoogleAccount();
	const { googleAdsAccount, isResolving: isResolvingGoogleAdsAccount } =
		useGoogleAdsAccount();

	if ( isResolvingGoogleAccount || isResolvingGoogleAdsAccount ) {
		return <SpinnerCard />;
	}

	if ( ! scope.adsRequired ) {
		return <AuthorizeAds additionalScopeEmail={ google.email } />;
	}

	if ( googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED ) {
		return <NonConnected />;
	}

	return (
		<ConnectedGoogleAdsAccountCard googleAdsAccount={ googleAdsAccount }>
			{ googleAdsAccount.status ===
				GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED && (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'Conversion measurement has been set up. You can create a campaign later.',
						'google-listings-and-ads'
					) }
				</Notice>
			) }
		</ConnectedGoogleAdsAccountCard>
	);
}
