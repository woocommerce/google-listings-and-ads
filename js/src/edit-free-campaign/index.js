/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import TopBar from '.~/components/stepper/top-bar';
import SetupFreeListings from '.~/components/free-listings/setup-free-listings';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useLayout from '.~/hooks/useLayout';
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import HelpIconButton from '.~/components/help-icon-button';
import hasUnsavedShippingRates from './hasUnsavedShippingRates';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '.~/hooks/useSaveShippingTimes';
import createErrorMessageForRejectedPromises from '.~/utils/createErrorMessageForRejectedPromises';

/**
 * Saving changes to the free campaign.
 *
 * @event gla_free_campaign_edited
 */

/**
 * Page Component to edit free campaigns.
 * Provides two steps:
 *  - Choose your audience
 *  - Configure your free listings
 * Given the user is editing an existing campaign, both steps should be available.
 * The displayed step is driven by `pageStep` URL parameter, to make it easier to permalink and navigate back and forth.
 *
 * @fires gla_free_campaign_edited
 */
const EditFreeCampaign = () => {
	useLayout( 'full-content' );

	const {
		targetAudience: savedTargetAudience,
		getFinalCountries,
	} = useTargetAudienceFinalCountryCodes();

	const { settings: savedSettings } = useSettings();
	const { saveTargetAudience, saveSettings } = useAppDispatch();
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();

	const [ targetAudience, updateTargetAudience ] = useState(
		savedTargetAudience
	);
	const [ settings, updateSettings ] = useState( savedSettings );

	const {
		hasFinishedResolution: hfrShippingRates,
		data: savedShippingRates,
	} = useShippingRates();
	const [ shippingRates, updateShippingRates ] = useState(
		savedShippingRates
	);
	// This is a quick and not safe workaround for
	// https://github.com/woocommerce/google-listings-and-ads/pull/422#discussion_r607796375
	// - `<Form>` element ignoring changes to its `initialValues` prop
	// - default state of shipping* data of `[]`
	// - resolver not signaling, that data is not ready yet
	const loadedShippingRates = ! hfrShippingRates ? null : shippingRates;

	const {
		hasFinishedResolution: hfrShippingTimes,
		data: savedShippingTimes,
	} = useShippingTimes();
	const [ shippingTimes, updateShippingTimes ] = useState(
		savedShippingTimes
	);
	const loadedShippingTimes = ! hfrShippingTimes ? null : shippingTimes;

	// TODO: Consider making it less repetitive.
	useEffect( () => updateSettings( savedSettings ), [ savedSettings ] );
	useEffect( () => updateTargetAudience( savedTargetAudience ), [
		savedTargetAudience,
	] );
	useEffect( () => updateShippingRates( savedShippingRates ), [
		savedShippingRates,
	] );
	useEffect( () => updateShippingTimes( savedShippingTimes ), [
		savedShippingTimes,
	] );

	const [ fetchSettingsSync ] = useApiFetchCallback( {
		path: `/wc/gla/mc/settings/sync`,
		method: 'POST',
	} );
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

	// Confirm leaving the page, if there are any changes and the user is navigating away from our stepper.
	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		didAnythingChanged
	);

	const dashboardURL = getNewPath(
		// Clear the step we were at, but perserve programId to be able to highlight the program.
		{ pageStep: undefined, subpath: undefined },
		'/google/dashboard'
	);

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
			await fetchSettingsSync();

			if ( errorMessage ) {
				createNotice( 'error', errorMessage );
			} else {
				createNotice(
					'success',
					__(
						'Your changes to your Free Listings have been saved and will be synced to your Google Merchant Center account.',
						'google-listings-and-ads'
					)
				);
			}

			recordEvent( 'gla_free_campaign_edited' );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Something went wrong while saving your changes. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<>
			<TopBar
				title={ __( 'Edit free listings', 'google-listings-and-ads' ) }
				helpButton={
					<HelpIconButton eventContext="edit-free-listings" />
				}
				backHref={ dashboardURL }
			/>
			<SetupFreeListings
				headerTitle={ __(
					'Edit your listings',
					'google-listings-and-ads'
				) }
				targetAudience={ targetAudience }
				resolveFinalCountries={ getFinalCountries }
				onTargetAudienceChange={ updateTargetAudience }
				settings={ settings }
				onSettingsChange={ updateSettings }
				shippingRates={ loadedShippingRates }
				onShippingRatesChange={ updateShippingRates }
				shippingTimes={ loadedShippingTimes }
				onShippingTimesChange={ updateShippingTimes }
				onContinue={ handleSetupFreeListingsContinue }
				submitLabel={ __( 'Save changes', 'google-listings-and-ads' ) }
			/>
		</>
	);
};

export default EditFreeCampaign;
