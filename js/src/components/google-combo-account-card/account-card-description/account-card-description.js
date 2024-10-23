/**
 * Internal dependencies
 */
import AccountDetails from './account-details';
import CreatingAccounts from './creating-accounts';
import useAccountsData from '.~/hooks/useAccountsData';

/**
 * AccountCardDescription component.
 * @return {JSX.Element} AccountCardDescription component.
 */
const AccountCardDescription = () => {
	const { creatingWhich, google, googleAdsAccount, googleMCAccount } =
		useAccountsData();

	if ( creatingWhich ) {
		return <CreatingAccounts creatingAccounts={ creatingWhich } />;
	}

	return (
		<AccountDetails
			email={ google.email }
			googleAdsID={ googleAdsAccount.id }
			googleMerchantCenterID={ googleMCAccount.id }
		/>
	);
};

export default AccountCardDescription;
