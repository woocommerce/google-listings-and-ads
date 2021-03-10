/**
 * Internal dependencies
 */
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import AccountSelectControl from '../account-select-control';

const AdsAccountSelectControl = ( props ) => {
	const { existingAccounts } = useExistingGoogleAdsAccounts();

	return <AccountSelectControl accounts={ existingAccounts } { ...props } />;
};

export default AdsAccountSelectControl;
