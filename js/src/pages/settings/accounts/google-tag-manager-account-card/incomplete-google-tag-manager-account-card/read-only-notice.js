/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import NoticeDetail from '../components/notice-detail';

/**
 * Renders the explanation shown in place of the "Create new container" link when the Google
 * connection can't create Google Tag Manager containers. Renders nothing when it can.
 *
 * @return {JSX.Element|null} The notice, or `null` when containers can be created.
 */
export default function ReadOnlyNotice() {
	const { account } = useGoogleTagManagerAccount();

	if ( account.canCreateContainer !== false ) {
		return null;
	}

	return (
		<div className="gla-google-tag-manager-account-card__notice-wrapper">
			<NoticeDetail
				status="info"
				body={
					<p>
						{ __(
							"The Google connection for this store has read-only access to Google Tag Manager, so containers can't be created from here.",
							'google-listings-and-ads'
						) }
					</p>
				}
			/>
		</div>
	);
}
