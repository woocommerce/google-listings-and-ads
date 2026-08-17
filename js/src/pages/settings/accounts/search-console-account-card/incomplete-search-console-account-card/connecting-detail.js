/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { geReportsUrl } from '~/utils/urls';
import NoticeDetail from './notice-detail';

/**
 * Renders the silent "setting up" detail shown while the backend is still resolving a
 * single-match or no-match property — no merchant action is needed, so this never shows a
 * selector (Q-003 in the Search Console connection PRD).
 *
 * @return {JSX.Element} The detail.
 */
export default function ConnectingDetail() {
	return (
		<NoticeDetail
			status="info"
			icon={ info }
			title={ __(
				'Setting up Google Search Console',
				'google-listings-and-ads'
			) }
			body={ __(
				'We are connecting your account.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton href={ geReportsUrl() } isSecondary>
					{ __( 'View reports', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}
