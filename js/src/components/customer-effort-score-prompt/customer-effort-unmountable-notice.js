/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import CustomerFeedbackModal from '@woocommerce/customer-effort-score/build/customer-feedback-modal';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useCESNotice from '.~/hooks/useCESNotice';

/**
 * Renders an unmountable CES notice to gather a customer effort score.
 *
 * ## Motivation
 *
 * The CustomerEffortScore component included in the package @woocommerce/customer-effort-score does not remove the notice
 * when the component has been unmounted, throwing an error when the "Give Feedback" button has been clicked. This new
 * component removes the notice if the CES component does not exist.
 *
 * @param {Object} props                               Component props.
 * @param {string} props.label                         The label displayed in the modal.
 * @param {JSX.Element} props.icon                     Icon (React component) to be shown on the notice.
 * @param {Function} [props.recordScoreCallback]       Function to call when the score should be recorded.
 * @param {Function} [props.onNoticeShownCallback]     Function to call when the notice is shown.
 * @param {Function} [props.onNoticeDismissedCallback] Function to call when the notice is dismissed.
 * @param {Function} [props.onModalShownCallback]      Function to call when the modal is shown.
 */
const CustomerEffortScoreUnmountableNotice = ( {
	label,
	icon,
	recordScoreCallback = noop,
	onNoticeShownCallback = noop,
	onNoticeDismissedCallback = noop,
	onModalShownCallback = noop,
} ) => {
	const [ shouldCreateNotice, setShouldCreateNotice ] = useState( true );
	const [ visible, setVisible ] = useState( false );
	const onClickFeedBack = () => {
		setVisible( true );
		onModalShownCallback();
	};

	const createCESNotice = useCESNotice(
		label,
		icon,
		onClickFeedBack,
		onNoticeDismissedCallback
	);

	useEffect( () => {
		if ( ! shouldCreateNotice ) {
			return;
		}

		createCESNotice();
		setShouldCreateNotice( false );
		onNoticeShownCallback();
	}, [ shouldCreateNotice, createCESNotice, onNoticeShownCallback ] );

	if ( shouldCreateNotice || ! visible ) {
		return null;
	}

	return (
		<CustomerFeedbackModal
			label={ label }
			recordScoreCallback={ recordScoreCallback }
		/>
	);
};

export default CustomerEffortScoreUnmountableNotice;
