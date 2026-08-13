/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import {
	Flex,
	FlexBlock,
	FlexItem,
	Notice,
	__experimentalItem as Item,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { appearanceDict } from '~/components/account-card';

/**
 * Renders a bold, alert-colored label followed by the rest of the description, for the
 * undesigned error states (reconnect, connection-failed, generic incomplete) that fall back to
 * {@link SearchConsoleErrorRow}'s plain treatment. The label is baked into the translatable
 * string itself (via the `<alert>` tag) so translators can reposition it relative to the rest of
 * the sentence.
 *
 * @param {string} textWithAlertTag Translated string containing an `<alert>…</alert>` tag around the label.
 * @return {JSX.Element} The interpolated description.
 */
export function errorDescription( textWithAlertTag ) {
	return createInterpolateElement( textWithAlertTag, {
		alert: (
			<strong className="gla-search-console-account-row__error-text" />
		),
	} );
}

/**
 * Renders an account row shell: account title and description, plus the step's action control.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {string|JSX.Element} props.description Row description — plain text for the generic
 *   resume fallback, or an {@link errorDescription} result for the two actual error states.
 * @param {boolean} [props.isError] Whether to render the description inside a red error notice.
 * @param {import('react').ReactNode} props.action The step's action control, rendered on the right.
 * @return {JSX.Element} The row.
 */
export default function SearchConsoleErrorRow( {
	account,
	description,
	isError,
	action,
} ) {
	const icon = appearanceDict[ account.appearance ]?.icon;

	return (
		<Item className="gla-search-console-account-row">
			<Flex align="flex-start" gap={ 4 } wrap>
				<FlexItem>{ icon }</FlexItem>
				<FlexBlock>
					<div className="gla-search-console-account-row__title">
						{ account.title }
					</div>
					{ isError ? (
						<Notice
							status="error"
							isDismissible={ false }
							className="gla-search-console-account-row__notice"
						>
							{ description }
						</Notice>
					) : (
						<div className="gla-search-console-account-row__description">
							{ description }
						</div>
					) }
				</FlexBlock>
				<FlexItem className="gla-search-console-account-row__status-action">
					{ action }
				</FlexItem>
			</Flex>
		</Item>
	);
}
