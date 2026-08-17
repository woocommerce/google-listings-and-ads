/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a bold, alert-colored label followed by the rest of the message, for the undesigned
 * error steps (reconnect, connection-failed). The label is baked into the translatable string
 * itself (via the `<alert>` tag) so translators can reposition it relative to the rest of the
 * sentence.
 *
 * @param {string} textWithAlertTag Translated string containing an `<alert>…</alert>` tag around the label.
 * @return {JSX.Element} The interpolated message.
 */
export function errorDescription( textWithAlertTag ) {
	return createInterpolateElement( textWithAlertTag, {
		alert: (
			<strong className="gla-search-console-account-card__error-text" />
		),
	} );
}
