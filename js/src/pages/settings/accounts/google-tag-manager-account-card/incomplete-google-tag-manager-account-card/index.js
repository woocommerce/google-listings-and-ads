/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import Badge from '~/components/badge';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import ContainerSelection from './container-selection';

/**
 * Renders the Google Tag Manager account card for the incomplete state: an account has already
 * been connected, but its container hasn't been picked yet. Always shows an "Action needed"
 * badge — the "Save" action lives inline in the container-selection detail itself, not in the
 * indicator.
 *
 * @return {JSX.Element} The account card.
 */
const IncompleteGoogleTagManagerAccountCard = () => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<Badge intent="warning">
					{ __( 'Action needed', 'google-listings-and-ads' ) }
				</Badge>
			}
			detail={ <ContainerSelection /> }
			expandedDetail
		/>
	);
};

export default IncompleteGoogleTagManagerAccountCard;
