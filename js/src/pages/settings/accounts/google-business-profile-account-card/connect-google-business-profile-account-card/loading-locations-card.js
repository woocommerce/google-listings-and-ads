/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import ConnectAccountCard from './connect-account-card';

/**
 * Renders the not-yet-connected card while the candidate locations are still loading: no
 * indicator, a loading spinner in the detail.
 *
 * @return {JSX.Element} The account card.
 */
export default function LoadingLocationsCard() {
	return <ConnectAccountCard detail={ <AppSpinner /> } />;
}
