/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../account-card-text-detail';
import { recordGlaEvent } from '~/utils/tracks';

const GOOGLE_MERCHANT_CENTER_OVERVIEW_URL =
	'https://merchants.google.com/mc/overview?a=';

/**
 * Renders the connected Merchant Center account ID, linked to its overview page.
 *
 * @fires gla_google_mc_link_click with `{ context: 'settings-linked-accounts', href }`
 *
 * @param {Object} props Component props.
 * @param {number} props.id Merchant Center account ID.
 * @return {JSX.Element} Connected account detail.
 */
const ConnectedAccountDetail = ( { id } ) => {
	const href = `${ GOOGLE_MERCHANT_CENTER_OVERVIEW_URL }${ id }`;

	const handleClick = () => {
		recordGlaEvent( 'gla_google_mc_link_click', {
			context: 'settings-linked-accounts',
			href,
		} );
	};

	return (
		<AccountCardTextDetail>
			<ExternalLink href={ href } onClick={ handleClick }>
				{ id }
			</ExternalLink>
		</AccountCardTextDetail>
	);
};

export default ConnectedAccountDetail;
