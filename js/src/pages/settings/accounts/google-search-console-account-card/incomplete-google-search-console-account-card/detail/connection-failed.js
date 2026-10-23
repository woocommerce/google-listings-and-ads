/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NoticeDetail from '../notice-detail';

/**
 * Renders the "connection failed" step's detail, shown when the initial connect attempt failed.
 *
 * @return {JSX.Element} The detail.
 */
export default function ConnectionFailed() {
	return (
		<NoticeDetail
			status="error"
			title={ __( 'Connection failed', 'google-listings-and-ads' ) }
			body={ __(
				"We couldn't connect your Google Search Console account. Please try again.",
				'google-listings-and-ads'
			) }
		/>
	);
}
