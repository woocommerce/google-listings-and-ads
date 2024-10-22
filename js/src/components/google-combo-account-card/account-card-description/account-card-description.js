/**
 * Internal dependencies
 */
import AccountDetails from './account-details';
import CreatingAccounts from './creating-accounts';
import { useAccountCreationContext } from '../account-creation-context';

const AccountCardDescription = () => {
	const creatingAccounts = useAccountCreationContext();

	return (
		<>{ creatingAccounts ? <CreatingAccounts /> : <AccountDetails /> }</>
	);
};

export default AccountCardDescription;
