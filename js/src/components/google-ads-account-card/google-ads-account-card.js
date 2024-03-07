/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	GOOGLE_ADS_ACCOUNT_STATUS,
	GOOGLE_ADS_BILLING_STATUS,
} from '.~/constants';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import SpinnerCard from '../spinner-card';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import usePrevious from '.~/hooks/usePrevious';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useShouldClaimGoogleAdsAccount from '.~/hooks/useShouldClaimGoogleAdsAccount';

export default function GoogleAdsAccountCard() {
	const { google, scope } = useGoogleAccount();
	const {
		googleAdsAccount,
		refetchGoogleAdsAccount,
		isResolving: isResolvingGoogleAdsAccount,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();
	const { hasAccess } = useGoogleAdsAccountStatus();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();
	const { createNotice } = useDispatchCoreNotices();
	const previousHasAccess = usePrevious( hasAccess );
	const {
		shouldClaimGoogleAdsAccount,
		isResolving: isResolvingShouldClaimGoogleAdsAccount,
	} = useShouldClaimGoogleAdsAccount();

	useEffect( () => {
		const checkAccessChange = async () => {
			// Access has changed, continue setup process
			if ( hasAccess === true && previousHasAccess === false ) {
				try {
					await upsertAdsAccount();
				} catch ( error ) {
					if (
						error.status !== 428 &&
						error?.billing_status !==
							GOOGLE_ADS_BILLING_STATUS.PENDING
					) {
						createNotice(
							'error',
							__(
								'Unable to connect Google Ads account. Please try again later.',
								'google-listings-and-ads'
							)
						);
					}
				}

				await refetchGoogleAdsAccount();
			}
		};

		checkAccessChange();
	} );

	if (
		isResolvingGoogleAdsAccount ||
		isResolvingShouldClaimGoogleAdsAccount
	) {
		return <SpinnerCard />;
	}

	if (
		! google ||
		google.active === 'no' ||
		! googleAdsAccount ||
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED ||
		shouldClaimGoogleAdsAccount
	) {
		return <NonConnected />;
	}

	if ( ! scope.adsRequired ) {
		return <AuthorizeAds additionalScopeEmail={ google.email } />;
	}

	return (
		<ConnectedGoogleAdsAccountCard
			googleAdsAccount={ googleAdsAccount }
			loading={ loading || ! hasResolvedGoogleAdsAccount }
		>
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
