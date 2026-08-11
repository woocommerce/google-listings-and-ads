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
import {
	GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
	GCR_ENROLLMENT_HELP_URL,
	BADGE_WIDGET_POSITION_OPTIONS,
	DEFAULT_BADGE_WIDGET_POSITION,
} from './constants';
import './index.scss';

/**
 * Triggered when the "Find out how" button is clicked.
 *
 * @event gla_reviews_settings_gcr_enrollment_help_click
 */

/**
 * Renders the "Collect reviews after purchase" (Google Customer Reviews) setting.
 *
 * Visibility is gated by the parent Settings page rendering this only when
 * `hasGoogleMCConnection` is true (see FEA-08.1.1 AC-002) — not repeated here.
 *
 * The "Estimated shipping times" dependency notice (AC-005) is deferred —
 * tracked as a follow-up once the Shipping page/route it depends on exists.
 */
const ReviewsSettings = () => {
	const { settings, saveSettings } = useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isBadgeWidgetSaving, setIsBadgeWidgetSaving ] = useState( false );
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

	const isEnabled = Boolean( settings.collect_reviews_after_purchase );

	const handleToggle = async () => {
		setIsSaving( true );
		try {
			await saveSettings( {
				...settings,
				collect_reviews_after_purchase: ! isEnabled,
			} );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error updating the review collection setting.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setIsSaving( false );
		}
	};

	const dismissGCRNotice = () => {
		setPreference(
			PREFERENCES_STORE_NAMESPACE,
			GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
			true
		);
	};

	const isBadgeWidgetEnabled = Boolean( settings.badge_widget_enabled );
	const badgeWidgetPosition =
		settings.badge_widget_position || DEFAULT_BADGE_WIDGET_POSITION;

	const handleBadgeWidgetToggle = async () => {
		setIsBadgeWidgetSaving( true );
		try {
			await saveSettings( {
				...settings,
				badge_widget_enabled: ! isBadgeWidgetEnabled,
			} );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error updating the badge widget setting.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setIsBadgeWidgetSaving( false );
		}
	};

	const handleBadgeWidgetPositionChange = async ( position ) => {
		setIsBadgeWidgetSaving( true );
		try {
			await saveSettings( {
				...settings,
				badge_widget_position: position,
			} );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error updating the badge widget position.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setIsBadgeWidgetSaving( false );
		}
	};

	return (
		<Section
			title={ __( 'Google Customer Reviews', 'google-listings-and-ads' ) }
			description={ sectionDescription }
		>
			<Section.Card>
				<Section.Card.Body>
					<VerticalGapLayout size="large">
						<AppStandaloneToggleControl
							label={ __(
								'Collect reviews after purchase',
								'google-listings-and-ads'
							) }
							checked={ isEnabled }
							disabled={ isSaving }
							onChange={ handleToggle }
							help={ __(
								'Google asks customers on the order confirmation page if they would like to review your store. Customers who opt in receive an email from Google once their order arrives.',
								'google-listings-and-ads'
							) }
						/>
						{ ! isGCRNoticeDismissed && (
							<Notice
								status="info"
								isDismissible
								onRemove={ dismissGCRNotice }
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
										'Find out how',
										'google-listings-and-ads'
									) }
								</AppButton>
							</Notice>
						) }
						<AppStandaloneToggleControl
							label={ __(
								'Google store widget',
								'google-listings-and-ads'
							) }
							checked={ isBadgeWidgetEnabled }
							disabled={ isBadgeWidgetSaving }
							onChange={ handleBadgeWidgetToggle }
							help={ __(
								'Enable the Google store widget to display your store ratings and reviews.',
								'google-listings-and-ads'
							) }
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
									className="gla-reviews-settings__widget-position"
									label={ __(
										'Widget position',
										'google-listings-and-ads'
									) }
									hideLabelFromVision
									selected={ badgeWidgetPosition }
									options={ BADGE_WIDGET_POSITION_OPTIONS }
									disabled={ isBadgeWidgetSaving }
									onChange={ handleBadgeWidgetPositionChange }
								/>
							</div>
						) }
					</VerticalGapLayout>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ReviewsSettings;
