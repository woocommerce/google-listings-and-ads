/**
 * Internal dependencies
 */
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';
import MainTabNav from '../main-tab-nav';
import DisconnectAccounts from './disconnect-accounts';
import './index.scss';

const Settings = () => {
	const navigationEnabled = isWCNavigationEnabled();

	return (
		<div className="gla-settings">
			{ ! navigationEnabled && <MainTabNav /> }
			<DisconnectAccounts />
		</div>
	);
};

export default Settings;
