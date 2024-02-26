/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useShowAdsClaimAccountModal from '.~/hooks/useShowAdsClaimAccountModal';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import ClaimAccount from './claim-account';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import ClaimAccountModal from './claim-account-modal';

export default function GoogleAdsAccountCard() {
	const isClaimAccountModalOpen = useShowAdsClaimAccountModal();
	const { google, scope } = useGoogleAccount();
	const {
		googleAdsAccount,
		googleAdsAccountStatus,
		hasFinishedResolutionGoogleAdsAccountStatus,
	} = useGoogleAdsAccount();

	if ( ! google || ! googleAdsAccount ) {
		return <NonConnected />;
	}

	if ( ! scope.adsRequired ) {
		return <AuthorizeAds additionalScopeEmail={ google.email } />;
	}

	if ( googleAdsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED ) {
		return <NonConnected />;
	}

	// Ads account has been created but we don't have access yet.
	if (
		googleAdsAccount.id &&
		hasFinishedResolutionGoogleAdsAccountStatus &&
		! googleAdsAccountStatus.hasAccess
	) {
		return (
			<Fragment>
				{ isClaimAccountModalOpen && <ClaimAccountModal /> }
				<ClaimAccount />
			</Fragment>
		);
	}

	return (
		<ConnectedGoogleAdsAccountCard googleAdsAccount={ googleAdsAccount }>
			{ googleAdsAccount.status ===
				GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED && (
				<Notice status="success" isDismissible={ false }>
					<p>
						{ __(
							'Conversion measurement has been set up. You can create a campaign later.',
							'google-listings-and-ads'
						) }
					</p>
				</Notice>
			) }
		</ConnectedGoogleAdsAccountCard>
	);
}
