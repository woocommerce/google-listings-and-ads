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

/**
 * Renders a Google Tag Manager account's name followed by its ID, linked out to that account in
 * Google Tag Manager itself.
 *
 * @param {Object} props Component props.
 * @param {Object} props.account The account to display. Shape: `{ id, name }`.
 * @return {JSX.Element} The account name and linked ID.
 */
export default function AccountNameWithLink( { account } ) {
	return createInterpolateElement(
		sprintf(
			/* translators: %1$s: account name, %2$s: account ID link */
			__( '%1$s %2$s', 'google-listings-and-ads' ),
			account.name,
			`<link>${ account.id }</link>`
		),
		{
			link: (
				<ExternalLink
					href={ getGoogleTagManagerAccountUrl( account.id ) }
				/>
			),
		}
	);
}
