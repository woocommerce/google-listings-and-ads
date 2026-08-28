/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import SurveyModal from './survey-modal';
import isWCTracksEnabled from '~/utils/isWCTracksEnabled';

/**
 * @fires gla_documentation_link_click with `{ context: 'skip-paid-ads-modal', link_id: 'paid-ads-with-performance-max-campaigns-learn-more', href: 'https://support.google.com/google-ads/answer/10724817' }`
 */

/**
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 * If WC tracking is disabled, it will show a simple confirmation modal.
 * If WC tracking is enabled, it will show a survey modal to gather user feedback.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed.
 * @param {Function} props.onSkipCreatePaidAds Function to be called when the user confirms skipping the paid ads setup.
 */
const SkipPaidAdsConfirmationModal = ( {
	onRequestClose,
	onSkipCreatePaidAds,
} ) => {
	const wcTracksEnabled = isWCTracksEnabled();

	if ( wcTracksEnabled ) {
		return (
			<SurveyModal
				onRequestClose={ onRequestClose }
				onSkipCreatePaidAds={ onSkipCreatePaidAds }
			/>
		);
	}

	return (
		<AppModal
			buttons={ [
				<AppButton key="cancel" onClick={ onRequestClose } isSecondary>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="complete-setup"
					onClick={ onSkipCreatePaidAds }
					isPrimary
				>
					{ __(
						'Complete setup without setting up ads',
						'google-listings-and-ads'
					) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
			title={ __( 'Skip setting up ads?', 'google-listings-and-ads' ) }
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
					context="skip-paid-ads-modal"
					href="https://support.google.com/google-ads/answer/10724817"
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
