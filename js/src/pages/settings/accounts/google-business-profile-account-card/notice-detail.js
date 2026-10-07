/**
 * External dependencies
 */
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './notice-detail.scss';

/**
 * Renders the colored notice used as the Google Business Profile card's detail content.
 *
 * @param {Object} props Component props.
 * @param {'info'|'warning'|'error'} props.status Notice color.
 * @param {JSX.Element} props.body Notice body content.
 * @param {JSX.Element[]} [props.actions] Action controls shown below the body, each needing its own `key`.
 * @return {JSX.Element} The notice.
 */
export default function NoticeDetail( { status, body, actions = [] } ) {
	return (
		<Notice
			status={ status }
			isDismissible={ false }
			className="gla-google-business-profile-account-card__notice"
		>
			<div className="gla-google-business-profile-account-card__notice-body">
				{ body }
			</div>
			{ actions.length > 0 && (
				<Flex
					justify="flex-start"
					className="gla-google-business-profile-account-card__notice-actions"
					wrap
				>
					{ actions }
				</Flex>
			) }
		</Notice>
	);
}
