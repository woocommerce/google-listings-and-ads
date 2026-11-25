/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import ConnectAccountCard from '../connect-account-card';
import ConnectLocationForm from './connect-location-form';

/**
 * Renders the not-yet-connected card for when multiple candidate locations were found: an "Action
 * needed" badge, with the form to pick and connect a location in the detail.
 *
 * @return {JSX.Element} The account card.
 */
export default function MultipleLocationsCard() {
	return (
		<ConnectAccountCard
			indicator={
				<Badge intent="warning">
					{ __( 'Action needed', 'google-listings-and-ads' ) }
				</Badge>
			}
			detail={ <ConnectLocationForm /> }
		/>
	);
}
