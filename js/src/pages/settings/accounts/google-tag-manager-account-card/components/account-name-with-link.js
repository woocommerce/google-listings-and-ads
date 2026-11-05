/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';

/**
 * Renders a Google Tag Manager account's name followed by its ID, linked out to that account in
 * Google Tag Manager itself. The link resolves to the connected Google account when its email is
 * known, so it doesn't open under whichever Google account happens to be active in the browser.
 *
 * @param {Object} props Component props.
 * @param {Object} props.account The account to display, either connected or a candidate during selection. Shape: `{ id, name }`.
 * @return {JSX.Element} The account name and linked ID.
 */
export default function AccountNameWithLink( { account } ) {
	const accountUrl = getGoogleTagManagerAccountUrl( account.id );
	const accountAwareUrl = useGoogleAccountAwareUrl( accountUrl );

	return (
		<>
			{ account.name }{ ' ' }
			<ExternalLink href={ accountAwareUrl }>{ account.id }</ExternalLink>
		</>
	);
}
