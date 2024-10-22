/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectedIconLabel from '.~/components/connected-icon-label';
import LoadingLabel from '.~/components/loading-label/loading-label';
import useAccountCreationData from '.~/hooks/useAccountCreationData';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';

const Indicator = () => {
	const { creatingAccounts } = useAccountCreationData();
	const { hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { googleMCAccount, isPreconditionReady } = useGoogleMCAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();

	const isGoogleAdsConnected =
		hasGoogleAdsConnection &&
		hasAccess &&
		[ '', 'billing', 'link_merchant' ].includes( step );

	const isGoogleMCConnected =
		isPreconditionReady &&
		( googleMCAccount?.status === 'connected' ||
			( googleMCAccount?.status === 'incomplete' &&
				googleMCAccount?.step === 'link_ads' ) );

	if ( creatingAccounts ) {
		return (
			<LoadingLabel
				text={ __( 'Creating…', 'google-listings-and-ads' ) }
			/>
		);
	}

	if ( isGoogleAdsConnected && isGoogleMCConnected ) {
		return <ConnectedIconLabel />;
	}

	return null;
};

export default Indicator;
