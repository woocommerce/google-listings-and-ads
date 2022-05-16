/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, Notice } from '@wordpress/components';
import { createInterpolateElement, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import ReviewRequestIssues from './review-request-issues';
import { useAppDispatch } from '.~/data';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

/**
 * Triggered when request review button is clicked
 *
 * @event gla_request_review
 */

/**
 * Triggered when the request review is successful
 *
 * @event gla_request_review_success
 */

/**
 * Triggered when the request review fails
 *
 * @event gla_request_review_failure
 */

/**
 * Triggered when clicking on the checkbox
 *
 * @property {'check'|'uncheck'} action Indicates if the checkbox is checked or unchecked
 * @event gla_request_review_issues_solved_checkbox_click
 */

/**
 * Render a modal showing the issues list and a notice with a remind for
 * the user to review those issues before requesting the review.
 *
 * @fires gla_request_review_issues_solved_checkbox_click with `action: 'checked' | 'unchecked'
 * @fires gla_request_review
 * @fires gla_request_review_success
 * @fires gla_request_review_failure
 *
 * @param {Object} props Component props
 * @param {Object[]} [props.issues=[]] Array with issues
 * @param {boolean} [props.isActive=false] True if the Modal is visible, false otherwise
 * @param {Function} [props.onClose] Callback function when closing the modal
 */
const ReviewRequestModal = ( {
	issues = [],
	isActive = false,
	onClose = () => {},
} ) => {
	const [ checkBoxChecked, setCheckBoxChecked ] = useState( false );
	const [ isRequestingReview, setIsRequestingReview ] = useState( false );
	const { sendMCReviewRequest } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	if ( ! isActive ) {
		return null;
	}

	const handleOnClose = ( action ) => {
		if ( isRequestingReview ) return;
		onClose( action );
	};

	const handleCheckboxChange = ( checked ) => {
		setCheckBoxChecked( checked );
		recordEvent( 'gla_request_review_issues_solved_checkbox_click', {
			action: checked ? 'check' : 'uncheck',
		} );
	};

	const handleReviewRequest = () => {
		if ( isRequestingReview ) return;

		setIsRequestingReview( true );
		recordEvent( 'gla_request_review' );

		sendMCReviewRequest()
			.then( () => {
				createNotice(
					'success',
					__(
						'Your account review was successfully requested.',
						'google-listings-and-ads'
					)
				);
				recordEvent( 'gla_request_review_success' );
				onClose( 'request-review-success' );
			} )
			.catch( () => {
				setIsRequestingReview( false );
				recordEvent( 'gla_request_review_failure' );
			} );
	};

	return (
		<AppModal
			className="gla-review-request-modal"
			title={ __( 'Request account review', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton
					key="secondary"
					isSecondary
					onClick={ () => {
						handleOnClose( 'maybe-later' );
					} }
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					loading={ isRequestingReview }
					key="primary"
					isPrimary
					disabled={ ! checkBoxChecked && issues.length }
					onClick={ handleReviewRequest }
				>
					{ __(
						'Request account review',
						'google-listings-and-ads'
					) }
				</AppButton>,
			] }
			onRequestClose={ () => {
				handleOnClose( 'dismiss' );
			} }
		>
			<Notice
				className="gla-review-request-modal__notice"
				status="warning"
				isDismissible={ false }
			>
				<p>
					{ createInterpolateElement(
						__(
							'Please ensure that you have resolved all account suspension issues before requesting for an account review. If some issues are unresolved, you wont be able to request another review for at least 7 days. <Link>Learn more</Link>',
							'google-listings-and-ads'
						),
						{
							Link: (
								<AppDocumentationLink
									href="https://support.google.com/merchants/answer/2948694"
									context="request-review-modal"
									linkId="request-review-modal-learn-more"
								/>
							),
						}
					) }
				</p>
			</Notice>
			<ReviewRequestIssues issues={ issues } />
			{ issues.length > 0 && (
				<CheckboxControl
					className="gla-review-request-modal__checkbox"
					label={ __(
						'I have resolved all the issue(s) listed above.',
						'google-listings-and-ads'
					) }
					checked={ checkBoxChecked }
					onChange={ handleCheckboxChange }
				/>
			) }
		</AppModal>
	);
};

export default ReviewRequestModal;
