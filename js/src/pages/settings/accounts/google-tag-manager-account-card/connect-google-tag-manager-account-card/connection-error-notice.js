/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import { getGoogleTagManagerHelpUrl } from '~/utils/urls';
import { ERROR_SLOTS } from '~/data/constants';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import NoticeDetail from './notice-detail';

const ERROR_SLOTS_TO_CHECK = [
	ERROR_SLOTS.GOOGLE_TAG_MANAGER_CONNECTION_ERROR_SLOT,
];

/**
 * Clicking on the button to start a fresh Google Tag Manager connection attempt after a failed one.
 *
 * @event gla_google_tag_manager_connection_retry_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the connection-failed notice shown in the `AccountCard` error area when a Google Tag
 * Manager connection attempt failed. "Try again" starts a fresh manual connection attempt — it
 * does not preserve or re-target the previously picked account.
 *
 * `null` when the store has no error for the connection error slot — this is meant to be used as
 * an `AccountCard` `ErrorComponent` (always mounted whenever `errorSlots` is non-empty, regardless
 * of whether there's currently an error), same contract as `DetailedError`.
 *
 * @fires gla_google_tag_manager_connection_retry_button_click
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onTryAgain Callback when the user clicks "Try again".
 * @return {JSX.Element|null} The notice, or `null` when there's no connection error.
 */
export default function ConnectionErrorNotice( { onTryAgain } ) {
	const [ detailedError ] = useDetailedErrorBySlots( ERROR_SLOTS_TO_CHECK );

	if ( ! detailedError ) {
		return null;
	}

	const apiMessage = detailedError.error?.message;

	return (
		<NoticeDetail
			status="error"
			title={ __(
				"We couldn't connect Google Tag Manager",
				'google-listings-and-ads'
			) }
			body={
				<p>
					{ apiMessage ||
						__(
							"Something went wrong. Check that you're signed in to the right Google account, then try again.",
							'google-listings-and-ads'
						) }
				</p>
			}
			actions={ [
				<AppButton
					key="try-again"
					eventName="gla_google_tag_manager_connection_retry_button_click"
					eventProps={ { context: 'settings-tag-manager' } }
					onClick={ onTryAgain }
					isSecondary
				>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppDocumentationLink
					key="get-help"
					context="settings-tag-manager"
					linkId="gtm-connection-failed-get-help"
					href={ getGoogleTagManagerHelpUrl() }
				>
					{ __( 'Get help', 'google-listings-and-ads' ) }
				</AppDocumentationLink>,
			] }
		/>
	);
}
