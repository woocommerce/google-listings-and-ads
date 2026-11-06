/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '~/constants';
import localStorage from '~/utils/localStorage';
import { recordGlaEvent } from '~/utils/tracks';
import CustomerEffortUnmountableNotice from './notice';

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
 * A Customer Effort Score prompt that uses tracks to track the selected
 * customer effort score. The prompt notice is removed when this component unmounts.
 *
 * @fires gla_ces_snackbar_open whenever the CES snackbar (notice) is open
 * @fires gla_ces_snackbar_closed whenever the CES snackbar (notice) is closed
 * @fires gla_ces_modal_open whenever the CES modal is open
 * @fires gla_ces_feedback whenever the CES feedback is recorded
 *
 * @param {Object} props React component props.
 * @param {string} props.eventContext Context to be used in the CES wrapper events.
 * @param {string} props.label Text to be displayed in the CES notice and modal.
 * @param {string} props.secondLabel Text to be displayed for the second question in the modal.
 * @return {JSX.Element} Rendered element.
 */
const CustomerEffortScorePrompt = ( { eventContext, label, secondLabel } ) => {
	const removeCESPromptFlagFromLocal = () => {
		localStorage.remove(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
	};

	const onNoticeShown = () => {
		removeCESPromptFlagFromLocal();
		recordGlaEvent( 'gla_ces_snackbar_open', {
			context: eventContext,
		} );
	};

	const onNoticeDismissed = () => {
		recordGlaEvent( 'gla_ces_snackbar_closed', {
			context: eventContext,
		} );
	};

	const onModalShown = () => {
		recordGlaEvent( 'gla_ces_modal_open', {
			context: eventContext,
		} );
	};

	const recordScore = ( score, score2, comments ) => {
		recordGlaEvent( 'gla_ces_feedback', {
			context: eventContext,
			score,
			comments: comments || '',
		} );
	};

	return (
		<CustomerEffortUnmountableNotice
			title={ label }
			firstQuestion={ label }
			secondQuestion={ secondLabel }
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
