/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

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

/**
 * Settings page component.
 *
 * @param {Object} [props] React props
 * @param {Object} [props.params = {}] `path` params extracted by React `<Router>`.
 * @param {string} [props.params.subpath] Sub path of a nested settings page.
 * @return {JSX.Element} Settings page component.
 */
const Settings = ( { params: { subpath } = {} } ) => {
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
	switch ( '/' + subpath ) {
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
