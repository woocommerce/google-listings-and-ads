/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';

const ConnectedCard = ( props ) => {
	const { googleMCAccount } = props;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ toAccountText( googleMCAccount.id ) }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default ConnectedCard;
