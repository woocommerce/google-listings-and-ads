/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import NavigationClassic from '.~/components/navigation-classic';
import DisconnectAccounts from './disconnect-accounts';
import ReconnectAccounts from './reconnect-accounts';

import { subpaths } from '.~/utils/urls';

import './index.scss';

const Settings = () => {
	const { subpath } = getQuery();

	if ( subpath === subpaths.reconnectAccounts ) {
		return <ReconnectAccounts />;
	}

	return (
		<div className="gla-settings">
			<NavigationClassic />
			<DisconnectAccounts />
		</div>
	);
};

export default Settings;
