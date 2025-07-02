/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { external as externalIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppModal from '../app-modal';
import AppButton from '../app-button';
import { APP_RATINGS_BANNER_CONTEXT } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * @event gla_app_ratings_maybe_later_clicked
 * @property {string} context The context in which the event is triggered.
 */

/**
 * @event gla_app_ratings_rate_clicked
 * @property {string} context The context in which the event is triggered.
 */

/**
 * FeedbackModal component.
 *
 * Displays a modal asking users to rate the extension, with options to cancel or proceed to the rating page.
 *
 * @param {Object} props - Component props.
 * @param {Function} props.onRequestClose - Function to call when the modal is closed.
 * @param {Function} props.onRateUsClick - Function to call when the "Rate us" button is clicked.
 * @return {JSX.Element} The FeedbackModal component.
 *
 * @fires gla_app_ratings_maybe_later_clicked - Fired when the user clicks the "Maybe later" button.
 * @fires gla_app_ratings_rate_clicked - Fired when the user clicks the "Rate us" button.
 */
const FeedbackModal = ( { onRequestClose, onRateUsClick } ) => {
	const trackEvent = ( eventName ) => {
		recordGlaEvent( eventName, { context: APP_RATINGS_BANNER_CONTEXT } );
	};

	const handleMaybeLaterOnClick = () => {
		trackEvent( 'gla_app_ratings_maybe_later_clicked' );
		onRequestClose();
	};

	const handleRateUsOnClick = () => {
		trackEvent( 'gla_app_ratings_rate_clicked' );
		onRequestClose();
		onRateUsClick();
	};

	return (
		<AppModal
			title={ __(
				'Thanks for letting us know!',
				'google-listings-and-ads'
			) }
			className="gla-experience-rating-banner__feedback-modal"
			buttons={ [
				<AppButton
					key="maybe-later"
					onClick={ handleMaybeLaterOnClick }
					isSecondary
				>
					{ __( 'Maybe later', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="rate-us"
					onClick={ handleRateUsOnClick }
					isPrimary
					target="_blank"
					href="https://wordpress.org/support/plugin/google-listings-and-ads/reviews/#new-post"
					icon={ <Icon icon={ externalIcon } /> }
					iconPosition="right"
					iconSize={ 16 }
				>
					{ __( 'Rate us', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
			shouldCloseOnEsc
			shouldCloseOnClickOutside
		>
			<p>
				{ __(
					"If you have a minute, we'd appreciate it if you could leave us a rating. Your review will help other store owners find this extension.",
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default FeedbackModal;
