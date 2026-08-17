/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';

/**
 * Renders a bold, alert-colored label followed by the rest of the description, for the
 * undesigned error states (reconnect, connection-failed) that fall back to
 * {@link SearchConsoleErrorAccountCard}'s plain treatment. The label is baked into the
 * translatable string itself (via the `<alert>` tag) so translators can reposition it relative
 * to the rest of the sentence.
 *
 * @param {string} textWithAlertTag Translated string containing an `<alert>…</alert>` tag around the label.
 * @return {JSX.Element} The interpolated description.
 */
export function errorDescription( textWithAlertTag ) {
	return createInterpolateElement( textWithAlertTag, {
		alert: (
			<strong className="gla-search-console-account-card__error-text" />
		),
	} );
}

/**
 * Renders the account card shell used by the undesigned reconnect/connection-failed/generic-
 * resume states: the account title/description, a plain description or (for the two actual
 * error cases) a red error notice, and the step's action.
 *
 * @param {Object} props Component props.
 * @param {string|JSX.Element} props.description Row description — plain text for the generic
 *   resume fallback, or an {@link errorDescription} result for the two actual error states.
 * @param {boolean} [props.isError] Whether to render the description inside a red error notice.
 * @param {import('react').ReactNode} props.action The step's action control.
 * @return {JSX.Element} The account card.
 */
export default function SearchConsoleErrorAccountCard( {
	description,
	isError,
	action,
} ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ isError ? undefined : description }
			alignIcon="top"
			alignIndicator="top"
			expandedDetail
			detail={
				isError ? (
					<Notice
						status="error"
						isDismissible={ false }
						className="gla-search-console-account-card__notice"
					>
						{ description }
					</Notice>
				) : undefined
			}
			indicator={ action }
		/>
	);
}
