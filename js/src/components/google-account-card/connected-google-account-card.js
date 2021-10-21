/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';

export default function ConnectedGoogleAccountCard( { googleAccount } ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			hideIcon
			description={ googleAccount.email }
		/>
	);
}
