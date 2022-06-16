/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';
import CustomerEffortScoreUnmountableNotice from './customer-effort-unmountable-notice';

/**
 * CES prompt snackbar open
 *
 * @event gla_ces_snackbar_open
 */
/**
 * CES prompt snackbar closed
 *
 * @event gla_ces_snackbar_closed
 */
/**
 * CES modal open
 *
 * @event gla_ces_modal_open
 */
/**
 * CES feedback recorded
 *
 * @event gla_ces_feedback
 */

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @fires gla_ces_snackbar_open whenever the CES snackbar (notice) is open
 * @fires gla_ces_snackbar_closed whenever the CES snackbar (notice) is closed
 * @fires gla_ces_modal_open whenever the CES modal is open
 * @fires gla_ces_feedback whenever the CES feedback is recorded
 *
 * @param {Object} props React component props.
 * @param {string} props.eventContext Context to be used in the CES wrapper events.
 * @param {string} props.label Text to be displayed in the CES notice and modal.
 * @return {JSX.Element} Rendered element.
 */
const CustomerEffortScorePrompt = ( { eventContext, label } ) => {
	const removeCESPromptFlagFromLocal = () => {
		localStorage.remove(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
	};

	const onNoticeShown = () => {
		removeCESPromptFlagFromLocal();
		recordEvent( 'gla_ces_snackbar_open', {
			context: eventContext,
		} );
	};

	const onNoticeDismissed = () => {
		recordEvent( 'gla_ces_snackbar_closed', {
			context: eventContext,
		} );
	};

	const onModalShown = () => {
		recordEvent( 'gla_ces_modal_open', {
			context: eventContext,
		} );
	};

	const recordScore = ( score, comments ) => {
		recordEvent( 'gla_ces_feedback', {
			context: eventContext,
			score,
			comments: comments || '',
		} );
	};

	return (
		<CustomerEffortScoreUnmountableNotice
			label={ label }
			recordScoreCallback={ recordScore }
			onNoticeShownCallback={ onNoticeShown }
			onNoticeDismissedCallback={ onNoticeDismissed }
			onModalShownCallback={ onModalShown }
			icon={
				<span
					style={ { height: 21, width: 21 } }
					role="img"
					aria-label={ __(
						'Pencil icon',
						'google-listings-and-ads'
					) }
				>
					✏️
				</span>
			}
		/>
	);
};

export default CustomerEffortScorePrompt;
