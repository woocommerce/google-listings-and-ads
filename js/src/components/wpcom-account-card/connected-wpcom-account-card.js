/**
 * Internal dependencies
 */
import getConnectedJetpackInfo from '.~/utils/getConnectedJetpackInfo';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';

const ConnectedWPComAccountCard = ( { jetpack } ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.WPCOM }
			description={ getConnectedJetpackInfo( jetpack ) }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default ConnectedWPComAccountCard;
