/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, RadioControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import AppStandaloneToggleControl from '~/components/app-standalone-toggle-control';
import AppButton from '~/components/app-button';
import SpinnerCard from '~/components/spinner-card';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import useSettings from '~/hooks/useSettings';
import usePreference from '~/hooks/usePreference';
import { handleApiError } from '~/utils/handleError';
import { recordGlaEvent } from '~/utils/tracks';
import {
	GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
	GCR_ENROLLMENT_HELP_URL,
	BADGE_WIDGET_POSITION_OPTIONS,
	DEFAULT_BADGE_WIDGET_POSITION,
} from './constants';
import './index.scss';

/**
 * Triggered when the "Learn how" button is clicked.
 *
 * @event gla_reviews_settings_gcr_enrollment_help_click
 */

/**
 * Triggered when the review-collection toggle is changed, after the save attempt.
 *
 * @event gla_reviews_collection_toggle
 * @property {boolean} enabled Whether review collection was enabled or disabled.
 */

/**
 * Triggered when the badge widget toggle is changed, after the save attempt.
 *
 * @event gla_reviews_badge_widget_toggle
 * @property {boolean} enabled Whether the badge widget was enabled or disabled.
 */

/**
 * Renders the Google Customer Reviews settings: the review-collection opt-in
 * toggle, and the store badge widget toggle with its position control.
 *
 * A dependency notice tied to the store's configured shipping times is deferred
 * for the review-collection setting, tracked as a follow-up once the Shipping
 * page/route it depends on exists.
 *
 * @fires gla_reviews_collection_toggle after each attempt to save the review-collection setting.
 * @fires gla_reviews_badge_widget_toggle after each attempt to save the badge widget setting.
 */
const GoogleCustomerReviewsSettings = () => {
	const { settings, saveSettings } = useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { set: setPreference } = useDispatch( preferencesStore );

	const isGCRNoticeDismissed = usePreference(
		GCR_ENROLLMENT_NOTICE_DISMISSED_KEY
	);

	const sectionDescription = (
		<div>
			<p>
				{ __(
					'Google Reviews provide free social proof, increased organic visibility, and a boost to advertising performance.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Shoppers rely heavily on reviews to navigate consumer skepticism; having a strong Google rating directly drives higher conversion rates and builds trust with new buyers.',
					'google-listings-and-ads'
				) }
			</p>
		</div>
	);

	if ( ! settings ) {
		return (
			<Section
				title={ __(
					'Google Customer Reviews',
					'google-listings-and-ads'
				) }
				description={ sectionDescription }
			>
				<SpinnerCard />
			</Section>
		);
	}

	const isEnabled = Boolean( settings.gcr_collect_reviews_after_purchase );

	/**
	 * Persists a settings patch, tracking the in-flight save state and
	 * surfacing an error notice on failure.
	 *
	 * @param {Object} patch Partial settings object to save.
	 */
	const saveSetting = async ( patch ) => {
		setIsSaving( true );
		try {
			await saveSettings( patch );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error updating the setting.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setIsSaving( false );
		}
	};

	const handleReviewCollectionChange = async () => {
		const nextEnabled = ! isEnabled;

		await saveSetting( {
			gcr_collect_reviews_after_purchase: nextEnabled,
		} );

		recordGlaEvent( 'gla_reviews_collection_toggle', {
			enabled: nextEnabled,
		} );
	};

	const handleRemoveGCRNotice = () => {
		setPreference(
			PREFERENCES_STORE_NAMESPACE,
			GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
			true
		);
	};

	const isBadgeWidgetEnabled = Boolean( settings.gcr_badge_widget_enabled );
	const badgeWidgetPosition =
		settings.gcr_badge_widget_position || DEFAULT_BADGE_WIDGET_POSITION;

	const handleBadgeWidgetChange = async () => {
		const nextEnabled = ! isBadgeWidgetEnabled;

		await saveSetting( { gcr_badge_widget_enabled: nextEnabled } );

		recordGlaEvent( 'gla_reviews_badge_widget_toggle', {
			enabled: nextEnabled,
		} );
	};

	/**
	 * @param {'bottom-right'|'bottom-left'} position Badge widget position to save.
	 * See BADGE_WIDGET_POSITION_OPTIONS in ./constants.
	 */
	const handleBadgeWidgetPositionChange = ( position ) =>
		saveSetting( { gcr_badge_widget_position: position } );

	return (
		<Section
			title={ __( 'Google Customer Reviews', 'google-listings-and-ads' ) }
			description={ sectionDescription }
		>
			<Section.Card>
				<Section.Card.Body>
					<VerticalGapLayout size="large">
						{ ! isGCRNoticeDismissed && (
							<Notice
								status="info"
								onRemove={ handleRemoveGCRNotice }
								isDismissible
							>
								<p>
									{ __(
										"This setting will only take effect if you're enrolled in the Merchant Center.",
										'google-listings-and-ads'
									) }
								</p>
								<AppButton
									variant="primary"
									href={ GCR_ENROLLMENT_HELP_URL }
									target="_blank"
									eventName="gla_reviews_settings_gcr_enrollment_help_click"
									eventProps={ {
										context: 'reviews-settings',
										link_id: 'gcr-enrollment-help',
										href: GCR_ENROLLMENT_HELP_URL,
									} }
								>
									{ __(
										'Learn how',
										'google-listings-and-ads'
									) }
								</AppButton>
							</Notice>
						) }
						<AppStandaloneToggleControl
							label={ __(
								'Collect reviews after purchase',
								'google-listings-and-ads'
							) }
							onChange={ handleReviewCollectionChange }
							help={ __(
								'Google asks customers on the order confirmation page if they would like to review your store. Customers who opt in receive an email from Google once their order arrives.',
								'google-listings-and-ads'
							) }
							checked={ isEnabled }
							disabled={ isSaving }
						/>
						<AppStandaloneToggleControl
							label={ __(
								'Google store widget',
								'google-listings-and-ads'
							) }
							onChange={ handleBadgeWidgetChange }
							help={ __(
								'Enable the Google store widget to display your store ratings and reviews.',
								'google-listings-and-ads'
							) }
							checked={ isBadgeWidgetEnabled }
							disabled={ isSaving }
						/>
						{ isBadgeWidgetEnabled && (
							<div>
								<Subsection.Title>
									{ __(
										'Widget position',
										'google-listings-and-ads'
									) }
								</Subsection.Title>
								<RadioControl
									className="gla-google-customer-reviews-settings__widget-position"
									selected={ badgeWidgetPosition }
									options={ BADGE_WIDGET_POSITION_OPTIONS }
									onChange={ handleBadgeWidgetPositionChange }
									disabled={ isSaving }
								/>
							</div>
						) }
					</VerticalGapLayout>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default GoogleCustomerReviewsSettings;
