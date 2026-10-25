/**
 * Internal dependencies
 */
import ConnectAccountCard from './connect-account-card';
import LocationsErrorNotice from './locations-error-notice';

/**
 * Renders the not-yet-connected card for when the candidate locations couldn't be loaded: no
 * indicator, the error notice with its "Try again" refetch in the detail.
 *
 * @return {JSX.Element} The account card.
 */
export default function LocationsErrorCard() {
	return <ConnectAccountCard detail={ <LocationsErrorNotice /> } />;
}
