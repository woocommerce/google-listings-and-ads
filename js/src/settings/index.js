/**
 * Internal dependencies
 */
import NavigationClassic from '.~/components/navigation-classic';
import DisconnectAccounts from './disconnect-accounts';
import './index.scss';

const Settings = () => {
	return (
		<div className="gla-settings">
			<NavigationClassic />
			<DisconnectAccounts />
		</div>
	);
};

export default Settings;
