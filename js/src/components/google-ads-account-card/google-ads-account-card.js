/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_ACCOUNT_STATUS } from '~/constants';
import SpinnerCard from '~/components/spinner-card';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import DisabledCard from './disabled-card';
import useGoogleAdsAccountStatus from '~/hooks/useGoogleAdsAccountStatus';
import showAdsConversionNotice from '~/utils/showAdsConversionNotice';

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

	if (
		! hasResolvedGoogleAccount ||
		! hasResolvedGoogleAdsAccount ||
		! hasResolvedAdsAccountStatus ||
		googleAdsAccount === null // Catch errors retrieving accounts.
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
		googleAdsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED ||
		hasAccess !== true ||
		( hasAccess === true && step === 'conversion_action' )
	) {
		return <NonConnected />;
	}

	const showSuccessNotice = showAdsConversionNotice( googleAdsAccount );

	return (
		<ConnectedGoogleAdsAccountCard googleAdsAccount={ googleAdsAccount }>
			{ showSuccessNotice ? (
				<Notice isDismissible={ false } status="success">
					{ __(
						'Conversion measurement has been set up. You can create a campaign later.',
						'google-listings-and-ads'
					) }
				</Notice>
			) : null }
		</ConnectedGoogleAdsAccountCard>
	);
}
