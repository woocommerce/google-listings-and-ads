/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import { ACTION_SKIP } from './constants';

/**
 * @fires gla_documentation_link_click with `{ context: 'skip-paid-ads-modal', link_id: 'paid-ads-with-performance-max-campaigns-learn-more', href: 'https://support.google.com/google-ads/answer/10724817' }`
 */

/**
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed.
 * @param {Function} props.onSkipCreatePaidAds Function to be called when the user confirms skipping the paid ads setup.
 */
const SkipPaidAdsConfirmationModal = ( {
	onRequestClose,
	onSkipCreatePaidAds,
} ) => {
	return (
		<AppModal
			title={ __( 'Skip setting up ads?', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton key="cancel" isSecondary onClick={ onRequestClose }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="complete-setup"
					onClick={ onSkipCreatePaidAds }
					data-action={ ACTION_SKIP }
					isPrimary
				>
					{ __(
						'Complete setup without setting up ads',
						'google-listings-and-ads'
					) }
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
				<AppDocumentationLink
					href="https://support.google.com/google-ads/answer/10724817"
					context="skip-paid-ads-modal"
					linkId="paid-ads-with-performance-max-campaigns-learn-more"
				>
					{ __(
						'Learn more about Performance Max.',
						'google-listings-and-ads'
					) }
				</AppDocumentationLink>
			</p>
		</AppModal>
	);
};

export default SkipPaidAdsConfirmationModal;
