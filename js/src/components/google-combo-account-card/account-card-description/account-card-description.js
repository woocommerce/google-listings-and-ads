/**
 * Internal dependencies
 */
import AccountDetails from './account-details';
import CreatingAccounts from './creating-accounts';

/**
 * AccountCardDescription component.
 * @param {Object} props Component props.
 * @param {string|null} props.creatingWhich Whether the accounts are being created. Possible values are: 'both', 'ads', 'mc'.
 * @return {JSX.Element} AccountCardDescription component.
 */
const AccountCardDescription = ( { creatingWhich } ) => {
	if ( creatingWhich ) {
		return <CreatingAccounts creatingAccounts={ creatingWhich } />;
	}

	return <AccountDetails />;
};

export default AccountCardDescription;
