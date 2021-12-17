/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import CustomerEffortScore from '@woocommerce/customer-effort-score';
import { recordEvent } from '@woocommerce/tracks';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object} props React component props.
 * @param {string} props.eventContext Context to be used in the CES wrapper events.
 * @param {string} props.label Text to be displayed in the CES notice and modal.
 */
const CustomerEffortScorePrompt = ( { eventContext, label } ) => {
	const removeCESPromptFlagFromLocal = () => {
		localStorage.remove(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
	};

	const onNoticeShown = () => {
		recordEvent( 'gla_ces_snackbar_open', {
			context: eventContext,
		} );
	};

	const onNoticeDismissed = () => {
		removeCESPromptFlagFromLocal();
		recordEvent( 'gla_ces_snackbar_closed', {
			context: eventContext,
		} );
	};

	const onModalShown = () => {
		removeCESPromptFlagFromLocal();
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
		<CustomerEffortScore
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

CustomerEffortScorePrompt.propTypes = {
	/**
	 * The text to be displayed on the CES prompt
	 */
	label: PropTypes.string.isRequired,
	/**
	 * Context property to be sent when calling recordEvent
	 */
	eventContext: PropTypes.string.isRequired,
};

export default CustomerEffortScorePrompt;
