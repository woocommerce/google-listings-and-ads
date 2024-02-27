/**
 * External dependencies
 */
import { Fragment, useCallback, useState, useEffect } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import ClaimAccount from './claim-account';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import ClaimAccountModal from './claim-account-modal';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import usePrevious from '.~/hooks/usePrevious';

export default function GoogleAdsAccountCard() {
	const [ claimModalOpen, setClaimModalOpen ] = useState( false );
	const { google, scope } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess, hasFinishedResolution } = useGoogleAdsAccountStatus();
	const previousHasAccess = usePrevious( hasAccess );
	const [ fetchCreateAdsAccount ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
	} );

	const handleOnCreateAccount = useCallback( () => {
		setClaimModalOpen( true );
	}, [] );

	const handleOnRequestClose = useCallback( () => {
		setClaimModalOpen( false );
	}, [] );

	useEffect( () => {
		const checkAccessChange = async () => {
			// Access has changed, continue setup process
			if ( hasAccess === true && previousHasAccess === false ) {
				await fetchCreateAdsAccount();
			}
		};

		checkAccessChange();
	} );

	if ( ! google || ! googleAdsAccount ) {
		return <NonConnected onCreateAccount={ handleOnCreateAccount } />;
	}

	if ( ! scope.adsRequired ) {
		return <AuthorizeAds additionalScopeEmail={ google.email } />;
	}

	if ( googleAdsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED ) {
		return <NonConnected onCreateAccount={ handleOnCreateAccount } />;
	}

	// Ads account has been created but we don't have access yet.
	if ( googleAdsAccount.id && hasAccess === false && hasFinishedResolution ) {
		return (
			<Fragment>
				{ claimModalOpen && (
					<ClaimAccountModal
						onRequestClose={ handleOnRequestClose }
					/>
				) }

				<ClaimAccount claimModalOpen={ claimModalOpen } />
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
