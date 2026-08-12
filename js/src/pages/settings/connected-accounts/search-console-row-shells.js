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
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
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
	return (
		<p>
			<em>
				{ createInterpolateElement( textWithAlertTag, {
					alert: (
						<span className="gla-search-console-account-row__error-text" />
					),
				} ) }
			</em>
		</p>
	);
}

/**
 * Renders the plain row shell used by the undesigned reconnect/connection-failed/generic-resume
 * states: the icon/title/description on the left, a plain description or (for the two actual
 * error cases) a red error notice, and the step's action on the right.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {string|JSX.Element} props.description Row description — plain text for the generic
 *   resume fallback, or an {@link errorDescription} result for the two actual error states.
 * @param {boolean} [props.isError] Whether to render the description inside a red error notice.
 * @param {import('react').ReactNode} props.action The step's action control, rendered on the right.
 * @return {JSX.Element} The row.
 */
export function SearchConsoleErrorRow( {
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

/**
 * Renders the row shell for the designed connect-flow sub-states (connecting/setting-up,
 * property selection, verification, action-needed): the icon/title/description on the left, a
 * status badge on the right, and — below the description — a colored notice with its own icon,
 * bold title, body copy, optional extra detail content (e.g. the property selector), and one or
 * two actions.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {'info'|'warning'} props.status Notice/badge color.
 * @param {import('react').ComponentType} props.icon Icon shown at the top of the notice.
 * @param {string} props.badgeLabel Status badge label.
 * @param {string} props.title Bold notice title.
 * @param {string} props.body Notice body copy.
 * @param {import('react').ReactNode} [props.detail] Extra content rendered below the body (e.g. the property selector).
 * @param {import('react').ReactNode} props.action The step's primary action control.
 * @param {import('react').ReactNode} [props.secondaryAction] An optional second action control (e.g. a "Learn more" link).
 * @return {JSX.Element} The row.
 */
export function SearchConsoleNoticeRow( {
	account,
	status,
	icon,
	badgeLabel,
	title,
	body,
	detail,
	action,
	secondaryAction,
} ) {
	const accountIcon = appearanceDict[ account.appearance ]?.icon;

	return (
		<Item className="gla-search-console-account-row">
			<Flex align="flex-start" gap={ 4 } wrap>
				<FlexItem>{ accountIcon }</FlexItem>
				<FlexBlock>
					<div className="gla-search-console-account-row__title">
						{ account.title }
					</div>
					<div className="gla-search-console-account-row__description">
						{ account.description }
					</div>
					<Notice
						status={ status }
						isDismissible={ false }
						className="gla-search-console-account-row__notice"
					>
						<div className="gla-search-console-account-row__notice-header">
							<Icon icon={ icon } />
							<span className="gla-search-console-account-row__notice-title">
								{ title }
							</span>
						</div>
						<p className="gla-search-console-account-row__notice-body">
							{ body }
						</p>
						{ detail }
						<Flex
							gap={ 3 }
							justify="flex-start"
							expanded={ false }
							wrap
							className="gla-search-console-account-row__notice-actions"
						>
							{ action }
							{ secondaryAction }
						</Flex>
					</Notice>
				</FlexBlock>
				<FlexItem className="gla-search-console-account-row__status-action">
					<Badge intent={ status }>{ badgeLabel }</Badge>
				</FlexItem>
			</Flex>
		</Item>
	);
}
