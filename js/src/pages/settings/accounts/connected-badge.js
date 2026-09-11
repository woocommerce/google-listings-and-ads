/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';

/**
 * Renders a "Connected" badge for connected accounts.
 *
 * @return {JSX.Element} The connected badge.
 */
const ConnectedBadge = () => {
	return (
		<Badge intent="success">
			{ __( 'Connected', 'google-listings-and-ads' ) }
		</Badge>
	);
};

export default ConnectedBadge;
