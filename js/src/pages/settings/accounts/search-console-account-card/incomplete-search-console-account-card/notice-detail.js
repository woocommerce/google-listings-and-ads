/**
 * External dependencies
 */
import { Flex, Notice } from '@wordpress/components';
import { Icon, info, warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './notice-detail.scss';

const ICON_BY_STATUS = {
	info,
	warning,
	error: warning,
};

/**
 * Renders the colored notice used as the `Detail` content for every incomplete-flow step
 * (property selection, verification, action-needed, reconnect, connection-failed): a
 * status-derived icon, bold title, body copy, optional extra content (e.g. the property
 * selector), and zero or more actions.
 *
 * @param {Object} props Component props.
 * @param {'info'|'warning'|'error'} props.status Notice color; also selects the header icon.
 * @param {string} props.title Bold notice title.
 * @param {string} props.body Notice body copy.
 * @param {JSX.Element} [props.extraContent] Extra content rendered below the body (e.g. the property selector).
 * @param {JSX.Element[]} [props.actions] The step's action controls, each needing its own `key`.
 * @return {JSX.Element} The notice.
 */
export default function NoticeDetail( {
	status,
	title,
	body,
	extraContent,
	actions = [],
} ) {
	return (
		<Notice
			status={ status }
			isDismissible={ false }
			className="gla-search-console-account-card__notice"
		>
			<div className="gla-search-console-account-card__notice-header">
				<Icon icon={ ICON_BY_STATUS[ status ] } />
				<span className="gla-search-console-account-card__notice-title">
					{ title }
				</span>
			</div>
			<p className="gla-search-console-account-card__notice-body">
				{ body }
			</p>
			{ extraContent }
			{ actions.length > 0 && (
				<Flex
					justify="flex-start"
					className="gla-search-console-account-card__notice-actions"
					expanded={ false }
					wrap
				>
					{ actions }
				</Flex>
			) }
		</Notice>
	);
}
