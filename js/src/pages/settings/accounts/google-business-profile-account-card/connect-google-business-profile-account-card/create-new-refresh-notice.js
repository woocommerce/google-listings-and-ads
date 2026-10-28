/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NoticeDetail from '../notice-detail';

const REFRESH_TEXT_BY_TYPE = {
	account: __(
		'Refresh the page to see your new account.',
		'google-listings-and-ads'
	),
	location: __(
		'Refresh the page to see your new location.',
		'google-listings-and-ads'
	),
};

/**
 * Renders the reminder to refresh the page once a {@see CreateNewLink} is clicked, because nothing
 * tells this page that a new Google Business Profile account or location exists.
 *
 * @param {Object} props Component props.
 * @param {'account'|'location'} props.type What the clicked link creates.
 * @return {JSX.Element} The notice.
 */
export default function CreateNewRefreshNotice( { type } ) {
	return (
		<NoticeDetail
			status="info"
			body={ <p>{ REFRESH_TEXT_BY_TYPE[ type ] }</p> }
		/>
	);
}
