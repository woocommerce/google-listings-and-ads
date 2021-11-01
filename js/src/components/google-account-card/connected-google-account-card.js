/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';

export default function ConnectedGoogleAccountCard( { googleAccount } ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ googleAccount.email }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
}
