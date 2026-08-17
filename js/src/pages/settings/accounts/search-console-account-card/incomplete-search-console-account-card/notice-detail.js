/**
 * External dependencies
 */
import { Flex, Notice } from '@wordpress/components';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders the colored notice used as the `Detail` content for the designed incomplete-flow
 * steps (property selection, verification, action-needed): an icon, bold title, body copy,
 * optional extra content (e.g. the property selector), and one or two actions.
 *
 * @param {Object} props Component props.
 * @param {'info'|'warning'} props.status Notice color.
 * @param {import('react').ComponentType} props.icon Icon shown at the top of the notice.
 * @param {string} props.title Bold notice title.
 * @param {string} props.body Notice body copy.
 * @param {import('react').ReactNode} [props.extraContent] Extra content rendered below the body (e.g. the property selector).
 * @param {import('react').ReactNode} props.action The step's primary action control.
 * @param {import('react').ReactNode} [props.secondaryAction] An optional second action control (e.g. a "Learn more" link).
 * @return {JSX.Element} The notice.
 */
export default function NoticeDetail( {
	status,
	icon,
	title,
	body,
	extraContent,
	action,
	secondaryAction,
} ) {
	return (
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
	);
}
