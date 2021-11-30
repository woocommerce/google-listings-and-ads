/**
 * Internal dependencies
 */
import getConnectedJetpackInfo from '.~/utils/getConnectedJetpackInfo';
import WPComAccountCard from './wpcom-account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';

const ConnectedWPComAccountCard = ( { jetpack } ) => {
	return (
		<WPComAccountCard
			description={ getConnectedJetpackInfo( jetpack ) }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default ConnectedWPComAccountCard;
