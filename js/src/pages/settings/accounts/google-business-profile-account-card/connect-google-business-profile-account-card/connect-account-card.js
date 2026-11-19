/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from '../constants';

/**
 * Renders the not-yet-connected Google Business Profile `AccountCard`, with the appearance shared
 * by every locations-state card.
 *
 * @param {Object} props Component props.
 * @param {JSX.Element} [props.indicator] Indicator of actions or status on the right side of the card.
 * @param {JSX.Element} [props.detail] Detail content below the card description.
 * @param {Array<string>} [props.errorSlots] Error slots whose error the card shows, for the connection-error state.
 * @param {Function} [props.ErrorComponent] Component rendering that error, for the connection-error state.
 * @return {JSX.Element} The account card.
 */
export default function ConnectAccountCard( {
	indicator,
	detail,
	errorSlots,
	ErrorComponent,
} ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			expandedDetail
			indicator={ indicator }
			detail={ detail }
			errorSlots={ errorSlots }
			ErrorComponent={ ErrorComponent }
		/>
	);
}
