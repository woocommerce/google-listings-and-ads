/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import ConnectAccountCard from './connect-account-card';
import ConnectionErrorNotice from './connection-error-notice';
import { CONNECTION_ERROR_SLOTS } from '../hooks/useConnectGoogleBusinessProfileLocation';

/**
 * Renders the not-yet-connected card for when the last connect attempt failed: a "Not connected"
 * badge, with the location-picking detail hidden in favor of the connection-error notice.
 *
 * @return {JSX.Element} The account card.
 */
export default function ConnectionErrorCard() {
	return (
		<ConnectAccountCard
			indicator={
				<Badge intent="error">
					{ __( 'Not connected', 'google-listings-and-ads' ) }
				</Badge>
			}
			errorSlots={ CONNECTION_ERROR_SLOTS }
			ErrorComponent={ ConnectionErrorNotice }
		/>
	);
}
