/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import CustomerEffortScore from '@woocommerce/customer-effort-score';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';

const EVENT_CONTEXT = 'gla-setup';

export default function CustomerEffortScorePrompt() {
	const removeCESPromptFlagFromLocal = () => {
		localStorage.remove(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
	};

	const onNoticeShown = () => {
		recordEvent( 'gla_ces_snackbar_open', {
			context: EVENT_CONTEXT,
		} );
	};

	const onNoticeDismissed = () => {
		removeCESPromptFlagFromLocal();
		recordEvent( 'gla_ces_snackbar_closed', {
			context: EVENT_CONTEXT,
		} );
	};

	const onModalShown = () => {
		removeCESPromptFlagFromLocal();
		recordEvent( 'gla_ces_modal_open', {
			context: EVENT_CONTEXT,
		} );
	};

	const recordScore = ( score, comments ) => {
		recordEvent( 'gla_ces_feedback', {
			context: EVENT_CONTEXT,
			score,
			comments: comments || '',
		} );
	};

	return (
		<CustomerEffortScore
			label={ __(
				'How easy was it to set up Google Listings & Ads?',
				'google-listings-and-ads'
			) }
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
}
