/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

const useGoogleAdsAccountReady = () => {
	const { hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();

	// Ads is ready when we have a connection and verified and verified access.
	// Billing is not required, and the 'link_merchant' step will be resolved
	// when the MC the account is connected.
	return (
		hasGoogleAdsConnection &&
		hasAccess &&
		[ '', 'billing', 'link_merchant' ].includes( step )
	);
};

export default useGoogleAdsAccountReady;
