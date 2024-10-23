/**
 * Internal dependencies
 */
import AccountDetails from './account-details';
import CreatingAccounts from './creating-accounts';

/**
 * AccountCardDescription component.
 * @return {JSX.Element} AccountCardDescription component.
 */
const AccountCardDescription = ( { creatingAccounts } ) => {
	if ( creatingAccounts ) {
		return <CreatingAccounts creatingAccounts={ creatingAccounts } />;
	}

	return <AccountDetails />;
};

export default AccountCardDescription;
