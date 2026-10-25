/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from '../constants';

/**
 * Renders the not-yet-connected Google Business Profile `AccountCard`, with the appearance shared
 * by every locations-state card. `indicator` and `detail` (and, for the connection-error state,
 * `errorSlots`/`ErrorComponent`) are supplied by the calling state card.
 *
 * @param {Object} props Props forwarded to `AccountCard`.
 * @return {JSX.Element} The account card.
 */
export default function ConnectAccountCard( props ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			expandedDetail
			{ ...props }
		/>
	);
}
