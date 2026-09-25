/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import useGoogleTagManagerAccountAwareUrl from './hooks/useGoogleTagManagerAccountAwareUrl';

/**
 * Renders a Google Tag Manager account's name followed by its ID, linked out to that account in
 * Google Tag Manager itself. The link resolves to the connected Google account when its email is
 * known, so it doesn't open under whichever Google account happens to be active in the browser.
 *
 * @param {Object} props Component props.
 * @param {Object} props.account The account to display. Shape: `{ id, name }`.
 * @return {JSX.Element} The account name and linked ID.
 */
export default function AccountNameWithLink( { account } ) {
	const accountUrl = useGoogleTagManagerAccountAwareUrl(
		getGoogleTagManagerAccountUrl( account.id )
	);

	return createInterpolateElement(
		sprintf(
			/* translators: %1$s: account name, %2$s: account ID link */
			__( '%1$s %2$s', 'google-listings-and-ads' ),
			account.name,
			`<link>${ account.id }</link>`
		),
		{
			link: <ExternalLink href={ accountUrl } />,
		}
	);
}
