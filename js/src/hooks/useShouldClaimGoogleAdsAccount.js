/**
 * Internal dependencies
 */
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

const useShouldClaimGoogleAdsAccount = () => {
	const { googleAdsAccount, isResolving: isResolvingGoogleAdsAccount } =
		useGoogleAdsAccount();
	const { hasAccess, isResolving: isResolvingGoogleAdsAccountStatus } =
		useGoogleAdsAccountStatus();

	return {
		shouldClaimGoogleAdsAccount:
			googleAdsAccount && googleAdsAccount.id && hasAccess === false,
		isResolving:
			isResolvingGoogleAdsAccount || isResolvingGoogleAdsAccountStatus,
	};
};

export default useShouldClaimGoogleAdsAccount;
