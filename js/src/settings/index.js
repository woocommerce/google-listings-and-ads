/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useLegacyMenuEffect from '.~/hooks/useLegacyMenuEffect';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import { subpaths, getReconnectGoogleAccountUrl } from '.~/utils/urls';
import NavigationClassic from '.~/components/navigation-classic';
import { ContactInformationPreview } from '.~/components/contact-information';
import LinkedAccounts from './linked-accounts';
import ReconnectWPComAccount from './reconnect-wpcom-account';
import ReconnectGoogleAccount from './reconnect-google-account';
import EditStoreAddress from './edit-store-address';
import EditPhoneNumber from './edit-phone-number';
import './index.scss';

const Settings = () => {
	const { subpath } = getQuery();
	// Make the component highlight GLA entry in the WC legacy menu.
	useLegacyMenuEffect();

	const { google } = useGoogleAccount();
	const isReconnectWPComPage = subpath === subpaths.reconnectWPComAccount;
	const isReconnectGooglePage = subpath === subpaths.reconnectGoogleAccount;

	// This page wouldn't get any 401 response when losing Google account access,
	// so we still need to detect it here.
	useEffect( () => {
		if ( ! isReconnectGooglePage && google?.active === 'no' ) {
			getHistory().replace( getReconnectGoogleAccountUrl() );
		}
	}, [ isReconnectGooglePage, google ] );

	// Navigate to subpath is any.
	switch ( subpath ) {
		case subpaths.reconnectGoogleAccount:
			return <ReconnectGoogleAccount />;
		case subpaths.editPhoneNumber:
			return <EditPhoneNumber />;
		case subpaths.editStoreAddress:
			return <EditStoreAddress />;
		default:
	}

	return (
		<div className="gla-settings">
			<NavigationClassic />
			{ isReconnectWPComPage ? (
				<ReconnectWPComAccount />
			) : (
				<>
					<ContactInformationPreview />
					<LinkedAccounts />
				</>
			) }
		</div>
	);
};

export default Settings;
