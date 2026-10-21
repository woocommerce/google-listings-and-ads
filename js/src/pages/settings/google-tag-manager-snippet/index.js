/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleTagManagerStatus from '~/hooks/useGoogleTagManagerStatus';
import useGoogleTagManagerSettings from '~/hooks/useGoogleTagManagerSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import { recordGlaEvent } from '~/utils/tracks';
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '~/pages/settings/accounts/google-tag-manager-account-card/constants';
import AdsConversionConflictNotice from './ads-conversion-conflict-notice';

/**
 * The Google Tag Manager snippet is turned on or off from the Settings page.
 *
 * @event gla_google_tag_manager_snippet_toggle
 * @property {boolean} enabled Whether the snippet was turned on or off.
 * @property {string} context Indicates from which page the snippet was toggled. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the settings section for turning the Google Tag Manager container snippet on or off.
 * The toggle is disabled until a Google Tag Manager container is connected, and a warning is shown
 * above it when the connected container already contains a Google Ads conversion tag.
 *
 * @fires gla_google_tag_manager_snippet_toggle after the snippet setting is saved.
 */
const GoogleTagManagerSnippet = () => {
	const { isConnected } = useGoogleTagManagerStatus();
	const { settings, hasFinishedResolution } = useGoogleTagManagerSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateGoogleTagManagerSettings } = useAppDispatch();

	const handleChange = async ( enabled ) => {
		try {
			setIsSaving( true );
			await updateGoogleTagManagerSettings( {
				snippet_injection_enabled: enabled,
			} );

			recordGlaEvent( 'gla_google_tag_manager_snippet_toggle', {
				enabled,
				context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
			} );

			createNotice(
				'success',
				__(
					'Google Tag Manager snippet setting updated successfully.',
					'google-listings-and-ads'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateGoogleTagManagerSettings` action.
		} finally {
			setIsSaving( false );
		}
	};

	const helpText = isConnected
		? __(
				'Enable the Google Tag Manager snippet to allow for tracking on your store.',
				'google-listings-and-ads'
		  )
		: __(
				'Please connect your Google Tag Manager account in order to manage your script.',
				'google-listings-and-ads'
		  );

	return (
		<Section
			title={ __(
				'Google Tag Manager snippet',
				'google-listings-and-ads'
			) }
			description={ __(
				'Google Tag Manager manages tracking and other tags on your store.',
				'google-listings-and-ads'
			) }
		>
			{ ! hasFinishedResolution && <SpinnerCard /> }

			{ hasFinishedResolution && (
				<Section.Card>
					<Section.Card.Body>
						<Flex direction="column" gap={ 4 }>
							<AdsConversionConflictNotice />
							<ToggleControl
								label={ __(
									'Google Tag Manager snippet',
									'google-listings-and-ads'
								) }
								help={ helpText }
								checked={
									isConnected &&
									Boolean( settings?.snippetInjectionEnabled )
								}
								onChange={ handleChange }
								disabled={
									! isConnected || ! settings || isSaving
								}
							/>
						</Flex>
					</Section.Card.Body>
				</Section.Card>
			) }
		</Section>
	);
};

export default GoogleTagManagerSnippet;
