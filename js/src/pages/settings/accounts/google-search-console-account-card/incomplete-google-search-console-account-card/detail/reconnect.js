/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NoticeDetail from '../notice-detail';

/**
 * Renders the "reconnect" step's detail, shown when the Google Search Console connection has expired.
 *
 * @return {JSX.Element} The detail.
 */
export default function Reconnect() {
	return (
		<NoticeDetail
			status="error"
			title={ __( 'Connection expired', 'google-listings-and-ads' ) }
			body={ __(
				'Your Google Search Console connection needs to be re-authorized.',
				'google-listings-and-ads'
			) }
		/>
	);
}
