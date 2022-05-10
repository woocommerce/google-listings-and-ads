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
 * Render a modal showing the issues list and a notice with a remind for
 * the user to review those issues before requesting the review.
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
	const { mcRequestReview } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	if ( ! isActive ) {
		return null;
	}

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

		mcRequestReview()
			.then( () => {
				createNotice(
					'success',
					__(
						'Your account review was successfully requested.',
						'google-listings-and-ads'
					)
				);
				setIsRequestingReview( false );
				onClose( 'request-review-success' );
				recordEvent( 'gla_request_review_success' );
			} )
			.catch( () => {
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
						onClose( 'maybe-later' );
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
				onClose( 'dismiss' );
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
