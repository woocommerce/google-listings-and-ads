/**
 * External dependencies
 */
import { Fragment, useCallback, useState, useEffect } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';
import ClaimAccount from './claim-account';
import ClaimAccountModal from './claim-account-modal';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import usePrevious from '.~/hooks/usePrevious';
import SpinnerCard from '../spinner-card';

export default function GoogleAdsAccountCard() {
	const [ claimModalOpen, setClaimModalOpen ] = useState( false );
	const [ adsAccountID, setAdsAccountID ] = useState( null );
	const { google, scope } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess, hasFinishedResolution } = useGoogleAdsAccountStatus();
	const previousHasAccess = usePrevious( hasAccess );

	const [ fetchCreateAdsAccount, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
		data: { id: adsAccountID },
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

	useEffect( () => {
		if ( googleAdsAccount && googleAdsAccount.id !== 0 ) {
			setAdsAccountID( googleAdsAccount.id );
		}
	}, [ googleAdsAccount ] );

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

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
	if ( googleAdsAccount.id && hasAccess === false ) {
		return (
			<Fragment>
				{ claimModalOpen && (
					<ClaimAccountModal
						onRequestClose={ handleOnRequestClose }
					/>
				) }

				<ClaimAccount />
			</Fragment>
		);
	}

	return (
		<ConnectedGoogleAdsAccountCard
			googleAdsAccount={ googleAdsAccount }
			loading={ loading }
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
