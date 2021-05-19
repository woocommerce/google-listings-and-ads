/**
 * Internal dependencies
 */
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';
import TabNav from '../tab-nav';
import DisconnectAccounts from './disconnect-accounts';
import './index.scss';

const Settings = () => {
	const navigationEnabled = isWCNavigationEnabled();

	return (
		<div className="gla-settings">
			{ ! navigationEnabled && <TabNav /> }
			<DisconnectAccounts />
		</div>
	);
};

export default Settings;
