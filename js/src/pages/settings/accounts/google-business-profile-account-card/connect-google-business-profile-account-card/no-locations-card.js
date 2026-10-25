/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import ConnectAccountCard from './connect-account-card';
import NoBusinessProfileAccountNotice from './no-business-profile-account-notice';

/**
 * Renders the not-yet-connected card for when the connected Google account has no Business
 * Profile location at all: an "Action needed" badge, the zero-locations notice in the detail.
 *
 * @return {JSX.Element} The account card.
 */
export default function NoLocationsCard() {
	return (
		<ConnectAccountCard
			indicator={
				<Badge intent="warning">
					{ __( 'Action needed', 'google-listings-and-ads' ) }
				</Badge>
			}
			detail={ <NoBusinessProfileAccountNotice /> }
		/>
	);
}
