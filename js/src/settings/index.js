/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import { subpaths, getReconnectAccountsUrl } from '.~/utils/urls';
import NavigationClassic from '.~/components/navigation-classic';
import { ContactInformationPreview } from '.~/components/contact-information';
import DisconnectAccounts from './disconnect-accounts';
import ReconnectAccounts from './reconnect-accounts';
import EditStoreAddress from './edit-store-address';
import EditPhoneNumber from './edit-phone-number';
import './index.scss';

const Settings = () => {
	const { subpath } = getQuery();
	const { google } = useGoogleAccount();
	const isReconnectAccountsPage = subpath === subpaths.reconnectAccounts;

	// This page wouldn't get any 401 response when losing Google account access,
	// so we still need to detect it here.
	useEffect( () => {
		if ( ! isReconnectAccountsPage && google?.active === 'no' ) {
			getHistory().replace( getReconnectAccountsUrl() );
		}
	}, [ isReconnectAccountsPage, google ] );

	// Navigate to subpath is any.
	switch ( subpath ) {
		case subpaths.reconnectAccounts:
			return <ReconnectAccounts />;
		case subpaths.editPhoneNumber:
			return <EditPhoneNumber />;
		case subpaths.editStoreAddress:
			return <EditStoreAddress />;
		default:
	}

	return (
		<div className="gla-settings">
			<NavigationClassic />
			<DisconnectAccounts />
			<ContactInformationPreview />
		</div>
	);
};

export default Settings;
