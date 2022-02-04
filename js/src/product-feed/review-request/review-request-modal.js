/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, Notice } from '@wordpress/components';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import ReviewRequestIssues from '.~/product-feed/review-request/review-request-issues';

const ReviewRequestModal = ( {
	data,
	isActive = false,
	onClose = () => {},
} ) => {
	const [ checkBoxChecked, setCheckBoxChecked ] = useState( false );

	if ( ! data || ! isActive ) {
		return null;
	}

	const sendRequest = () => {};

	return (
		<AppModal
			className="gla-review-request-modal"
			title={ __( 'Request account review', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton key="secondary" isSecondary onClick={ onClose }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="primary"
					isPrimary
					disabled={ ! checkBoxChecked }
					onClick={ sendRequest }
				>
					{ __(
						'Request account review',
						'google-listings-and-ads'
					) }
				</AppButton>,
			] }
			onRequestClose={ onClose }
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
			<ReviewRequestIssues issues={ data.issues } />
			<CheckboxControl
				className="gla-review-request-modal__checkbox"
				label={ __(
					'I have resolved all the issue(s) listed above.',
					'google-listings-and-ads'
				) }
				checked={ checkBoxChecked }
				onChange={ setCheckBoxChecked }
			/>
		</AppModal>
	);
};

export default ReviewRequestModal;
