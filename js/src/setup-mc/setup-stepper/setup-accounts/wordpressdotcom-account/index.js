/**
 * Internal dependencies
 */
import ConnectedWPComAccountCard from './connected-wpcom-account-card';
import ConnectWPComAccountCard from './connect-wpcom-account-card';

const WordPressDotComAccount = ( { jetpack } ) => {
	if ( jetpack.active === 'yes' ) {
		return <ConnectedWPComAccountCard jetpack={ jetpack } />;
	}

	return <ConnectWPComAccountCard />;
};

export default WordPressDotComAccount;
