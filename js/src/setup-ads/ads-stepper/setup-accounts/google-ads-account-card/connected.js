/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';

const Connected = ( props ) => {
	const { googleAdsAccount } = props;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			description={ toAccountText( googleAdsAccount.id ) }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default Connected;
