/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import { ACTION_SKIP } from './constants';

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
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed. Defaults to a no-op function.
 * @param {Function} props.onSkipConfirmation Function to be called when the user confirms skipping the paid ads setup. Defaults to a no-op function.
 * @param {boolean} [props.isProcessing=false] Indicates whether a process is currently running (e.g., the confirmation is being processed). If true, the confirmation button will show a loading state.
 */
const SkipPaidAdsConfirmationModal = ( {
	onRequestClose = noop,
	onSkipConfirmation = noop,
	isProcessing = false,
} ) => {
	const handleSkipConfirmationClick = ( event ) => {
		onSkipConfirmation( event );
	};

	return (
		<AppModal
			className="gla-ads-skip-paid-ads-modal"
			title={ __( 'Skip setting up ads?', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton key="no" isSecondary onClick={ onRequestClose }>
					{ __( 'No', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="yes"
					// TODO: confirm the eventName
					eventName="gla_skip_paid_ads_modal_confirm_button_click"
					onClick={ handleSkipConfirmationClick }
					loading={ isProcessing }
					data-action={ ACTION_SKIP }
					isPrimary
				>
					{ __( 'Yes', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'Enabling Performance Max is highly recommended to drive more sales and reach new audiences across Google channels like Search, YouTube and Discover.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Performance Max uses the best of Google’s AI to show the most impactful ads for your products at the right time and place. Google will use your product data to create ads for this campaign.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ createInterpolateElement(
					__(
						'<Link>Learn more about Performance Max.</Link>',
						'google-listings-and-ads'
					),
					{
						Link: (
							<AppDocumentationLink
								href="https://support.google.com/google-ads/answer/10724817"
								// TODO: review context and linkId values
								context="skip-paid-ads-modal"
								linkId="skip-paid-ads-modal-learn-more-performance-max"
							/>
						),
					}
				) }
			</p>
		</AppModal>
	);
};

export default SkipPaidAdsConfirmationModal;
