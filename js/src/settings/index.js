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
import EditContactInformation from './edit-contact-information';
import './index.scss';
import { DemoVerifyPhoneNumberCard } from '.~/components/contact-information/phone-number-card/phone-number-card';

const Settings = () => {
	const { subpath } = getQuery();
	const { google } = useGoogleAccount();
	const isEditContactInformationPage =
		subpath === subpaths.editContactInformation;
	const isReconnectAccountsPage = subpath === subpaths.reconnectAccounts;

	// This page wouldn't get any 401 response when losing Google account access,
	// so we still need to detect it here.
	useEffect( () => {
		if ( ! isReconnectAccountsPage && google?.active === 'no' ) {
			getHistory().replace( getReconnectAccountsUrl() );
		}
	}, [ isReconnectAccountsPage, google ] );

	if ( isEditContactInformationPage ) {
		return <EditContactInformation />;
	}

	if ( isReconnectAccountsPage ) {
		return <ReconnectAccounts />;
	}

	return (
		<div className="gla-settings">
			<NavigationClassic />
			<DemoVerifyPhoneNumberCard />
			<DisconnectAccounts />
			<ContactInformationPreview />
		</div>
	);
};

export default Settings;
