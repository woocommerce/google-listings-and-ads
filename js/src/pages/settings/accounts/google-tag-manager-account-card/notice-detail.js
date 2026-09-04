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
 * Renders the colored notice used as this card's detail content: a status-derived icon, optional
 * bold title, body copy, and zero or more actions.
 *
 * @param {Object} props Component props.
 * @param {'info'|'warning'|'error'} props.status Notice color; also selects the header icon.
 * @param {string} [props.title] Bold notice title, shown alongside the icon when provided.
 * @param {JSX.Element} props.body Notice body content.
 * @param {JSX.Element[]} [props.actions] The step's action controls, each needing its own `key`.
 * @return {JSX.Element} The notice.
 */
export default function NoticeDetail( { status, title, body, actions = [] } ) {
	return (
		<Notice
			status={ status }
			isDismissible={ false }
			className="gla-google-tag-manager-account-card__notice"
		>
			{ title && (
				<div className="gla-google-tag-manager-account-card__notice-header">
					<Icon icon={ ICON_BY_STATUS[ status ] } />
					<span className="gla-google-tag-manager-account-card__notice-title">
						{ title }
					</span>
				</div>
			) }
			<div className="gla-google-tag-manager-account-card__notice-body">
				{ body }
			</div>
			{ actions.length > 0 && (
				<Flex
					justify="flex-start"
					className="gla-google-tag-manager-account-card__notice-actions"
					wrap
				>
					{ actions }
				</Flex>
			) }
		</Notice>
	);
}
