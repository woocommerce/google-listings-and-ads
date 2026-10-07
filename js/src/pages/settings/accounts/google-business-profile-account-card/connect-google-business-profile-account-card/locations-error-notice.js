/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import NoticeDetail from '../notice-detail';

/**
 * Renders the notice shown when looking up the merchant's Google Business Profile locations
 * failed, with a "Try again" action.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onRetry Called when the merchant asks to try the lookup again.
 * @return {JSX.Element} The notice.
 */
export default function LocationsErrorNotice( { onRetry } ) {
	return (
		<NoticeDetail
			status="error"
			body={
				<p>
					{ __(
						"We couldn't load your Google Business Profile locations.",
						'google-listings-and-ads'
					) }
				</p>
			}
			actions={ [
				<AppButton key="retry" isSecondary onClick={ onRetry }>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
