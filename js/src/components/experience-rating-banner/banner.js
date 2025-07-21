/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Notice, Icon } from '@wordpress/components';
import { external as externalIcon } from '@wordpress/icons';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import { BANNER_DISMISSED_KEY } from './constants';
import {
	REPORT_SOURCE_PAID,
	PREFERENCES_STORE_NAMESPACE,
	APP_RATINGS_BANNER_CONTEXT,
} from '~/constants';
import AppButton from '~/components/app-button';
import useMCProductStatistics from '~/hooks/useMCProductStatistics';
import useProductsReport from '~/pages/reports/products/useProductsReport';
import FeedbackModal from './feedback-modal';
import './index.scss';

/**
 * When the experience rating banner is displayed to the user.
 *
 * @event gla_app_ratings_shown
 * @property {string} context The context in which the event is triggered.
 */

/**
 * When the user clicks the "Good" button on the banner.
 *
 * @event gla_app_ratings_good_clicked
 * @property {string} context The context in which the event is triggered.
 */

/**
 * When the feedback modal is closed by the user.
 *
 * @event gla_app_ratings_close
 * @property {string} context The context in which the event is triggered.
 */

/**
 * When the user clicks the "Need help" button on the banner.
 *
 * @event gla_app_ratings_need_help_clicked
 * @property {string} context The context in which the event is triggered.
 */

/**
 * Banner component.
 *
 * Displays a dismissible banner asking users to rate their experience with Google for WooCommerce.
 * Handles user interactions such as clicking "Good", "Need help", and dismissing the banner,
 * and records corresponding events. Shows a feedback modal when "Good" is clicked.
 *
 * @return {JSX.Element|null} The Banner component, or null if dismissed.
 *
 * @fires gla_app_ratings_shown When the banner is shown.
 * @fires gla_app_ratings_good_clicked When the "Good" button is clicked.
 * @fires gla_app_ratings_close When the feedback modal is closed.
 * @fires gla_app_ratings_need_help_clicked When the "Need help" button is clicked.
 */
const Banner = () => {
	const { data: statisticsData } = useMCProductStatistics();
	const { data: productsReport } = useProductsReport( REPORT_SOURCE_PAID );
	const [ showModal, setShowModal ] = useState( false );
	const { set } = useDispatch( preferencesStore ) || {};

	const shouldDisplayBanner = useMemo( () => {
		const statistics = statisticsData?.statistics || {};

		if ( Object.keys( statistics ).length === 0 ) {
			return false;
		}

		const totalProducts = Object.values( statistics ).reduce(
			( total, value ) => total + value,
			0
		);

		if ( ! totalProducts ) {
			return false;
		}

		const notSyncedProducts = statistics.not_synced;
		const activeProducts = statistics.active;
		const conversions = productsReport?.totals?.conversions?.value || 0;

		const hasEnoughActiveProducts =
			( activeProducts / totalProducts ) * 100 >= 70;
		const hasAllProductsSynced =
			notSyncedProducts === 0 && activeProducts > 0;
		const hasConversions = conversions > 0;

		return (
			hasEnoughActiveProducts && hasAllProductsSynced && hasConversions
		);
	}, [ statisticsData, productsReport ] );

	useEffect( () => {
		if ( shouldDisplayBanner ) {
			recordGlaEvent( 'gla_app_ratings_shown', {
				context: APP_RATINGS_BANNER_CONTEXT,
			} );
		}
	}, [ shouldDisplayBanner ] );

	if ( ! shouldDisplayBanner ) {
		return null;
	}

	const handleGoodOnClick = () => {
		recordGlaEvent( 'gla_app_ratings_good_clicked', {
			context: APP_RATINGS_BANNER_CONTEXT,
		} );
		setShowModal( true );
	};

	const handleRequestClose = () => {
		recordGlaEvent( 'gla_app_ratings_close', {
			context: APP_RATINGS_BANNER_CONTEXT,
		} );
		setShowModal( false );
	};

	const handleNeedHelpOnClick = () => {
		recordGlaEvent( 'gla_app_ratings_need_help_clicked', {
			context: APP_RATINGS_BANNER_CONTEXT,
		} );
	};

	const dismissBanner = () => {
		set( PREFERENCES_STORE_NAMESPACE, BANNER_DISMISSED_KEY, true );
	};

	return (
		<div className="gla-experience-rating-banner__container">
			{ showModal && (
				<FeedbackModal
					onRequestClose={ handleRequestClose }
					onRateUsClick={ dismissBanner }
				/>
			) }
			<Notice
				className="gla-experience-rating-banner"
				status="info"
				isDismissible={ true }
				onRemove={ dismissBanner }
			>
				<p className="gla-experience-rating-banner__text">
					{ __(
						'How was your experience with Google for WooCommerce?',
						'google-listings-and-ads'
					) }
				</p>

				<div className="gla-experience-rating-banner__actions">
					<AppButton onClick={ handleGoodOnClick } isSecondary>
						{ __( 'Good', 'google-listings-and-ads' ) }
					</AppButton>

					<AppButton
						isSecondary
						href="https://woocommerce.com/my-account/contact-support/"
						target="_blank"
						onClick={ handleNeedHelpOnClick }
						icon={ <Icon icon={ externalIcon } /> }
						iconPosition="right"
						iconSize={ 16 }
					>
						{ __( 'Need help', 'google-listings-and-ads' ) }
					</AppButton>
				</div>
			</Notice>
		</div>
	);
};

export default Banner;
