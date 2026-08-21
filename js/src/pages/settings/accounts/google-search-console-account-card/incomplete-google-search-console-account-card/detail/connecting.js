/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { geReportsUrl } from '~/utils/urls';
import NoticeDetail from '../notice-detail';

const REPORTS_URL = geReportsUrl();

/**
 * Renders the silent "setting up" detail shown while the backend is still resolving a
 * single-match or no-match property — no merchant action is needed, so this never shows a
 * selector.
 *
 * @return {JSX.Element} The detail.
 */
export default function Connecting() {
	return (
		<NoticeDetail
			status="info"
			title={ __(
				'Setting up Google Search Console',
				'google-listings-and-ads'
			) }
			body={ __(
				'We are connecting your account.',
				'google-listings-and-ads'
			) }
			actions={ [
				<AppButton key="view-reports" href={ REPORTS_URL } isSecondary>
					{ __( 'View reports', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
