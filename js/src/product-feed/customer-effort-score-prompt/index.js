/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import CustomerEffortScore from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';

export default function CustomerEffortScorePrompt() {
	const removeCesPromptFlagFromLocal = () => {
		localStorage.remove(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
	};

	const onNoticeShown = () => {
		// TODO: record event
	};

	const onNoticeDismissed = () => {
		// TODO: record event
		removeCesPromptFlagFromLocal();
	};

	const onModalShown = () => {
		// TODO: record event
		removeCesPromptFlagFromLocal();
	};

	const recordScore = () => {
		// TODO: record event
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
