/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import DisconnectAccounts from './disconnect-accounts';
import './index.scss';

const Settings = () => {
	return (
		<div className="gla-settings">
			<TabNav initialName="settings" />
			<DisconnectAccounts />
		</div>
	);
};

export default Settings;
