/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { geReportsUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import SearchConsoleNoticeAccountCard from './notice-account-card';

/**
 * Renders the "connecting"/setting-up state: shown while the backend is still silently
 * resolving a single-match or no-match property.
 *
 * @return {JSX.Element} The account card.
 */
export default function ConnectingSearchConsoleAccountCard() {
	return (
		<SearchConsoleNoticeAccountCard
			description={ __(
				'See how your store performs in Google Search.',
				'google-listings-and-ads'
			) }
			status="info"
			icon={ info }
			badgeLabel={ __( 'In progress', 'google-listings-and-ads' ) }
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
