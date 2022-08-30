/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { API_RESPONSE_CODES } from '.~/constants';
import useLegacyMenuEffect from '.~/hooks/useLegacyMenuEffect';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import { subpaths, getReconnectAccountUrl } from '.~/utils/urls';
import { ContactInformationPreview } from '.~/components/contact-information';
import LinkedAccounts from './linked-accounts';
import ReconnectWPComAccount from './reconnect-wpcom-account';
import ReconnectGoogleAccount from './reconnect-google-account';
import EditStoreAddress from './edit-store-address';
import EditPhoneNumber from './edit-phone-number';
import './index.scss';
import SettingsHeader from '.~/settings/settings-header';
import AttributeMapping from '.~/settings/attribute-mapping';

const pageClassName = 'gla-settings';

const Settings = () => {
	const { subpath } = getQuery();
	// Make the component highlight GLA entry in the WC legacy menu.
	useLegacyMenuEffect();

	const { google } = useGoogleAccount();
	const isReconnectGooglePage = subpath === subpaths.reconnectGoogleAccount;

	// This page wouldn't get any 401 response when losing Google account access,
	// so we still need to detect it here.
	useEffect( () => {
		if ( ! isReconnectGooglePage && google?.active === 'no' ) {
			getHistory().replace(
				getReconnectAccountUrl( API_RESPONSE_CODES.GOOGLE_DISCONNECTED )
			);
		}
	}, [ isReconnectGooglePage, google ] );

	// Navigate to subpath is any.
	switch ( subpath ) {
		case subpaths.reconnectWPComAccount:
			return (
				<div className={ pageClassName }>
					<ReconnectWPComAccount />
				</div>
			);
		case subpaths.reconnectGoogleAccount:
			return <ReconnectGoogleAccount />;
		case subpaths.editPhoneNumber:
			return <EditPhoneNumber />;
		case subpaths.editStoreAddress:
			return <EditStoreAddress />;
		case subpaths.attributeMapping:
			return <AttributeMapping />;
		default:
	}

	return (
		<div className={ pageClassName }>
			<SettingsHeader />
			<ContactInformationPreview />
			<LinkedAccounts />
		</div>
	);
};

export default Settings;
