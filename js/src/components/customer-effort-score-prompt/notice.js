/**
 * External dependencies
 */
import { CustomerFeedbackModal } from '@woocommerce/customer-effort-score';
import { useInstanceId } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * The store used by WooCommerce admin to render snackbar notices, which is also
 * where the `@woocommerce/customer-effort-score` package creates its CES notice.
 */
const CES_NOTICES_STORE_KEY = 'core/notices2';

/**
 * Renders a Customer Effort Score notice that is owned by this component, and the
 * feedback modal opened from it.
 *
 * The notice is created with an id tied to this component instance. Since the
 * notices store replaces a notice that has the same id, mounting twice does not
 * create two notices.
 *
 * @param {Object} props React component props.
 * @param {string} props.title Text displayed in the notice and as the modal title.
 * @param {string} props.firstQuestion The first question displayed in the modal.
 * @param {string} [props.secondQuestion] The second question displayed in the modal.
 * @param {JSX.Element} [props.icon] Icon shown in the notice.
 * @param {Function} [props.recordScoreCallback] Called with the scores and comments when the feedback is sent.
 * @param {Function} [props.onNoticeShownCallback] Called when the notice is shown.
 * @param {Function} [props.onNoticeDismissedCallback] Called when the notice is dismissed.
 * @param {Function} [props.onModalShownCallback] Called when the modal is shown.
 * @param {Function} [props.onModalDismissedCallback] Called when the modal is closed without sending feedback.
 * @return {JSX.Element|null} The feedback modal when it is open, otherwise null.
 */
const CustomerEffortUnmountableNotice = ( {
	title,
	firstQuestion,
	secondQuestion,
	icon,
	recordScoreCallback = noop,
	onNoticeShownCallback = noop,
	onNoticeDismissedCallback = noop,
	onModalShownCallback = noop,
	onModalDismissedCallback = noop,
} ) => {
	const [ isModalOpen, setModalOpen ] = useState( false );
	const { createNotice, removeNotice } = useDispatch( CES_NOTICES_STORE_KEY );
	const noticeId = `gla-ces-prompt-${ useInstanceId(
		CustomerEffortUnmountableNotice
	) }`;

	// Keep the latest props in a ref, so the notice created on mount always calls
	// the current callbacks without having to recreate the notice when they change.
	const latestProps = useRef();
	latestProps.current = {
		title,
		icon,
		onNoticeShownCallback,
		onNoticeDismissedCallback,
		onModalShownCallback,
	};

	useEffect( () => {
		createNotice( 'success', latestProps.current.title, {
			id: noticeId,
			actions: [
				{
					label: __( 'Give feedback', 'google-listings-and-ads' ),
					onClick: () => {
						setModalOpen( true );
						latestProps.current.onModalShownCallback();
					},
				},
			],
			icon: latestProps.current.icon,
			explicitDismiss: true,
			onDismiss: () => latestProps.current.onNoticeDismissedCallback(),
		} );
		latestProps.current.onNoticeShownCallback();

		return () => {
			removeNotice( noticeId );
		};
	}, [ createNotice, removeNotice, noticeId ] );

	if ( ! isModalOpen ) {
		return null;
	}

	return (
		<CustomerFeedbackModal
			title={ title }
			firstQuestion={ firstQuestion }
			secondQuestion={ secondQuestion }
			recordScoreCallback={ ( ...args ) => {
				setModalOpen( false );
				recordScoreCallback( ...args );
			} }
			onCloseModal={ () => {
				setModalOpen( false );
				onModalDismissedCallback();
			} }
		/>
	);
};

export default CustomerEffortUnmountableNotice;
