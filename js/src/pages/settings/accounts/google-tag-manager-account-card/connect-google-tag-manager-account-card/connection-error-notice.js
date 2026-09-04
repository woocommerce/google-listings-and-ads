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
import { useAppDispatch } from '~/data';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import NoticeDetail from './notice-detail';

/**
 * The connection error slot(s) this notice reads from and clears.
 *
 * @type {Array<string>}
 */
export const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_TAG_MANAGER_CONNECTION_ERROR_SLOT,
];

const GOOGLE_TAG_MANAGER_HELP_URL = getGoogleTagManagerHelpUrl();

/**
 * Clicking on the button to start a fresh Google Tag Manager connection attempt after a failed one.
 *
 * @event gla_google_tag_manager_connection_retry_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the connection-error notice shown in the `AccountCard` error area when a Google Tag
 * Manager connection attempt failed. "Try again" clears the error slot itself.
 *
 * `null` when the store has no error for the connection error slot.
 *
 * @fires gla_google_tag_manager_connection_retry_button_click
 *
 * @return {JSX.Element|null} The notice, or `null` when there's no connection error.
 */
export default function ConnectionErrorNotice() {
	const [ detailedError ] = useDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	const { clearDetailedErrorBySlots } = useAppDispatch();

	if ( ! detailedError ) {
		return null;
	}

	const apiMessage = detailedError.error?.message;

	const handleTryAgainClick = () => {
		clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	};

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
					onClick={ handleTryAgainClick }
					isSecondary
				>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppDocumentationLink
					key="get-help"
					context="settings-tag-manager"
					linkId="gtm-connection-failed-get-help"
					href={ GOOGLE_TAG_MANAGER_HELP_URL }
				>
					{ __( 'Get help', 'google-listings-and-ads' ) }
				</AppDocumentationLink>,
			] }
		/>
	);
}
