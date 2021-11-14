/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import AccountSelectControl from '../account-select-control';

const MerchantCenterSelectControl = ( props ) => {
	const { data: existingAccounts } = useExistingGoogleMCAccounts();

	const accounts =
		existingAccounts && existingAccounts.map( ( acc ) => acc.id );

	return <AccountSelectControl accounts={ accounts } { ...props } />;
};

export default MerchantCenterSelectControl;
