/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import { noop, merge } from 'lodash';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import { ACTION_SKIP } from './constants';
import { STORE_KEY } from '.~/data/constants';

/**
 * Triggered when the skip button is clicked
 * // TODO: to review
 *
 * @event gla_onboarding_complete_button_click
 */

/**
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed. Defaults to a no-op function.
 * @param {Function} props.onSkipConfirmation Function to be called when the user confirms skipping the paid ads setup. Defaults to a no-op function.
 * @param {boolean} [props.isProcessing=false] Indicates whether a process is currently running (e.g., the confirmation is being processed). If true, the confirmation button will show a loading state.
 * @param {boolean} [props.showPaidAdsSetup=false] Indicates whether the paid ads setup is currently shown. If true, additional event properties will be included in the eventProps.
 * @param {Object} [props.paidAds={}] The paid ads data, including the campaign form data and validation status.
 */
const SkipPaidAdsConfirmationModal = ( {
	onRequestClose = noop,
	onSkipConfirmation = noop,
	isProcessing = false,
	showPaidAdsSetup = false,
	paidAds = {},
} ) => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	const eventProps = {
		opened_paid_ads_setup: 'no',
		google_ads_account_status: googleAdsAccount?.status,
		billing_method_status: 'unknown',
		campaign_form_validation: 'unknown',
	};

	// TODO: Review once https://github.com/woocommerce/google-listings-and-ads/issues/2500 is merged
	if ( showPaidAdsSetup ) {
		const selector = select( STORE_KEY );
		const billing = selector.getGoogleAdsAccountBillingStatus();

		merge( eventProps, {
			opened_paid_ads_setup: 'yes',
			billing_method_status: billing?.status,
			campaign_form_validation: paidAds.isValid ? 'valid' : 'invalid',
		} );
	}

	return (
		<AppModal
			className="gla-ads-skip-paid-ads-modal"
			title={ __( 'Skip setting up ads?', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton key="cancel" isSecondary onClick={ onRequestClose }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="complete-setup"
					// TODO: confirm the eventName
					eventName="gla_onboarding_complete_button_click"
					onClick={ onSkipConfirmation }
					loading={ isProcessing }
					data-action={ ACTION_SKIP }
					eventProps={ eventProps }
					isPrimary
				>
					{ __( 'Complete setup', 'google-listings-and-ads' ) }
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
