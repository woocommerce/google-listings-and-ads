/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectedIconLabel from '.~/components/connected-icon-label';
import LoadingLabel from '.~/components/loading-label/loading-label';
import useAccountsData from '.~/hooks/useAccountsData';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';

/**
 * Account creation indicator.
 * Displays a loading indicator when accounts are being created or a connected icon when accounts are connected.
 * @return {JSX.Element|null} Indicator component.
 */
const Indicator = () => {
	const { creatingWhich } = useAccountsData();
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

	if ( creatingWhich ) {
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
