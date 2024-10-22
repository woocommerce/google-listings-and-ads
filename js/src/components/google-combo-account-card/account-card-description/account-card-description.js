/**
 * Internal dependencies
 */
import AccountDetails from './account-details';
import CreatingAccounts from './creating-accounts';
import useAccountCreationData from '.~/hooks/useAccountCreationData';

const AccountCardDescription = () => {
	const { creatingAccounts, email, googleAdsAccount, googleMCAccount } =
		useAccountCreationData();

	return (
		<>
			{ creatingAccounts ? (
				<CreatingAccounts creatingAccounts={ creatingAccounts } />
			) : (
				<AccountDetails
					email={ email }
					googleAdsID={ googleAdsAccount.id }
					googleMerchantCenterID={ googleMCAccount.id }
				/>
			) }
		</>
	);
};

export default AccountCardDescription;
