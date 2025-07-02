/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import MainTabNav from '~/components/main-tab-nav';
import SetupFreeListings from '~/components/free-listings/setup-free-listings';
import ConfirmSaveModal from './confirm-save-modal';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useSettings from '~/hooks/useSettings';
import useNavigateAwayPromptEffect from '~/hooks/useNavigateAwayPromptEffect';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import hasUnsavedShippingRates from './hasUnsavedShippingRates';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import createErrorMessageForRejectedPromises from '~/utils/createErrorMessageForRejectedPromises';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import { handleApiError } from '~/utils/handleError';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Saving changes of audience and/or shipping settings to the free listings.
 *
 * @event gla_free_campaign_edited
 */

/**
 * Page component to edit audience and shipping settings.
 *
 * Note that:
 * - This page used to be called "Edit free listings" page.
 * - Although it's presented on UI as "Shipping" page,
 *   it actually contains other Merchant Center settings.
 *
 * @fires gla_free_campaign_edited
 */
export default function Shipping() {
	const { targetAudience: savedTargetAudience, getFinalCountries } =
		useTargetAudienceFinalCountryCodes();

	const {
		settings: savedSettings,
		saveSettings,
		syncSettings,
	} = useSettings();

	const { saveTargetAudience } = useAppDispatch();
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();

	const [ targetAudience, updateTargetAudience ] =
		useState( savedTargetAudience );
	const [ settings, updateSettings ] = useState( savedSettings );

	const {
		hasFinishedResolution: hasResolvedShippingRates,
		data: savedShippingRates,
	} = useShippingRates();
	const [ shippingRates, updateShippingRates ] =
		useState( savedShippingRates );

	const {
		hasFinishedResolution: hasResolvedShippingTimes,
		data: savedShippingTimes,
	} = useShippingTimes();
	const [ shippingTimes, updateShippingTimes ] =
		useState( savedShippingTimes );

	const [ resolveConfirmation, setResolveConfirmation ] = useState( null );

	// TODO: Consider making it less repetitive.
	useEffect( () => updateSettings( savedSettings ), [ savedSettings ] );
	useEffect(
		() => updateTargetAudience( savedTargetAudience ),
		[ savedTargetAudience ]
	);
	useEffect(
		() => updateShippingRates( savedShippingRates ),
		[ savedShippingRates ]
	);
	useEffect(
		() => updateShippingTimes( savedShippingTimes ),
		[ savedShippingTimes ]
	);

	const { createNotice } = useDispatchCoreNotices();

	// Check what've changed to show prompt, and send requests only to save changed things.
	const didAudienceChanged = ! isEqual(
		...[ targetAudience, savedTargetAudience ].map( ( el ) => ( {
			...el,
			countries: new Set( el?.countries ),
		} ) )
	);

	const didSettingsChanged = ! isEqual( settings, savedSettings );
	const didRatesChanged = hasUnsavedShippingRates(
		shippingRates,
		savedShippingRates
	);

	// Check what've changed to show prompt. Dont take in consideration the order when comparing the Shipping times.
	const didTimesChanged = ! isEqual(
		new Set( shippingTimes ),
		new Set( savedShippingTimes )
	);

	const didAnythingChanged =
		didAudienceChanged ||
		didSettingsChanged ||
		didRatesChanged ||
		didTimesChanged;

	// Confirm leaving the page, if there are any changes and the user is navigating away from this page.
	useNavigateAwayPromptEffect(
		__(
			'You have unsaved changes. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		didAnythingChanged
	);

	const handleRequestSubmit = () => {
		return new Promise( ( resolve ) => {
			setResolveConfirmation( () => ( shouldContinue ) => {
				resolve( shouldContinue );
				setResolveConfirmation( null );
			} );
		} );
	};

	const handleSetupFreeListingsContinue = async () => {
		// TODO: Disable the form so the user won't be able to input any changes, which could be disregarded.
		try {
			const promises = [
				saveTargetAudience( targetAudience ),
				saveSettings( settings ),
				saveShippingRates( shippingRates ),
				saveShippingTimes( shippingTimes ),
			];

			const errorMessage = await createErrorMessageForRejectedPromises(
				promises,
				[
					__( 'Target audience', 'google-listings-and-ads' ),
					__( 'Merchant Center Settings', 'google-listings-and-ads' ),
					__( 'Shipping rates', 'google-listings-and-ads' ),
					__( 'Shipping times', 'google-listings-and-ads' ),
				]
			);

			// Sync data once our changes are saved, even partially succesfully.
			await syncSettings();

			if ( errorMessage ) {
				createNotice( 'error', errorMessage );
			} else {
				createNotice(
					'success',
					__(
						'Your changes have been saved and will be synced to your Google Merchant Center account.',
						'google-listings-and-ads'
					)
				);
			}

			recordGlaEvent( 'gla_free_campaign_edited' );
		} catch ( error ) {
			handleApiError(
				error,
				__( 'Unable to save your changes.', 'google-listings-and-ads' ),
				__(
					'Something went wrong while saving your changes. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const initialAudience = targetAudience?.countries ? targetAudience : null;
	const initialSettings = settings?.shipping_rate ? settings : null;
	const initialRates = hasResolvedShippingRates ? savedShippingRates : null;
	const initialTimes = hasResolvedShippingTimes ? savedShippingTimes : null;

	return (
		<>
			<ExperienceRatingBanner />
			<MainTabNav />
			<SetupFreeListings
				targetAudience={ initialAudience }
				resolveFinalCountries={ getFinalCountries }
				onTargetAudienceChange={ updateTargetAudience }
				settings={ initialSettings }
				onSettingsChange={ updateSettings }
				shippingRates={ initialRates }
				onShippingRatesChange={ updateShippingRates }
				shippingTimes={ initialTimes }
				onShippingTimesChange={ updateShippingTimes }
				onRequestSubmit={ handleRequestSubmit }
				onContinue={ handleSetupFreeListingsContinue }
				submitLabel={ __( 'Save changes', 'google-listings-and-ads' ) }
			/>
			<Flex justify="flex-end">
				<SetupFreeListings.SubmitButton />
			</Flex>
			{ resolveConfirmation && (
				<ConfirmSaveModal
					onContinue={ () => resolveConfirmation( true ) }
					onRequestClose={ () => resolveConfirmation( false ) }
				/>
			) }
		</>
	);
}
