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
import SearchConsoleNoticeRow from './search-console-notice-row';

/**
 * Renders the "connecting"/setting-up state: shown while the backend is still silently
 * resolving a single-match or no-match property.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The row.
 */
export default function ConnectingRow( { account } ) {
	return (
		<SearchConsoleNoticeRow
			account={ account }
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
