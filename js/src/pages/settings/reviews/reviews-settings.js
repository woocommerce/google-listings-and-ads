/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import Section from '~/components/section';
import AppStandaloneToggleControl from '~/components/app-standalone-toggle-control';
import AppButton from '~/components/app-button';
import SpinnerCard from '~/components/spinner-card';
import useSettings from '~/hooks/useSettings';
import usePreference from '~/hooks/usePreference';
import { handleApiError } from '~/utils/handleError';
import {
	GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
	GCR_ENROLLMENT_HELP_URL,
} from './constants';

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

	return (
		<Section
			title={ __( 'Google Customer Reviews', 'google-listings-and-ads' ) }
			description={ sectionDescription }
		>
			<Section.Card>
				<Section.Card.Body>
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
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ReviewsSettings;
