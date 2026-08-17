/**
 * External dependencies
 */
import { Flex, Notice } from '@wordpress/components';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import Badge from '~/components/badge';

/**
 * Renders the account card shell for the designed connect-flow sub-states (connecting/setting-up,
 * property selection, verification, action-needed): the account title/description, a status
 * badge, and a colored notice with its own icon, bold title, body copy, optional extra content
 * (e.g. the property selector), and one or two actions.
 *
 * @param {Object} props Component props.
 * @param {string} props.description Account description shown above the notice.
 * @param {'info'|'warning'} props.status Notice/badge color.
 * @param {import('react').ComponentType} props.icon Icon shown at the top of the notice.
 * @param {string} props.badgeLabel Status badge label.
 * @param {string} props.title Bold notice title.
 * @param {string} props.body Notice body copy.
 * @param {import('react').ReactNode} [props.extraContent] Extra content rendered below the body (e.g. the property selector).
 * @param {import('react').ReactNode} props.action The step's primary action control.
 * @param {import('react').ReactNode} [props.secondaryAction] An optional second action control (e.g. a "Learn more" link).
 * @return {JSX.Element} The account card.
 */
export default function SearchConsoleNoticeAccountCard( {
	description,
	status,
	icon,
	badgeLabel,
	title,
	body,
	extraContent,
	action,
	secondaryAction,
} ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ description }
			alignIcon="top"
			alignIndicator="top"
			expandedDetail
			indicator={ <Badge intent={ status }>{ badgeLabel }</Badge> }
			detail={
				<Notice
					status={ status }
					isDismissible={ false }
					className="gla-search-console-account-card__notice"
				>
					<div className="gla-search-console-account-card__notice-header">
						<Icon icon={ icon } />
						<span className="gla-search-console-account-card__notice-title">
							{ title }
						</span>
					</div>
					<p className="gla-search-console-account-card__notice-body">
						{ body }
					</p>
					{ extraContent }
					<Flex
						gap={ 3 }
						justify="flex-start"
						expanded={ false }
						wrap
						className="gla-search-console-account-card__notice-actions"
					>
						{ action }
						{ secondaryAction }
					</Flex>
				</Notice>
			}
		/>
	);
}
