/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import TopBar from '.~/components/stepper/top-bar';
import ChooseAudience from '.~/components/free-listings/choose-audience';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import SetupFreeListings from './setup-free-listings';
import useLayout from '.~/hooks/useLayout';
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import HelpIconButton from '.~/components/help-icon-button';
import hasUnsavedShippingRates from './hasUnsavedShippingRates';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '.~/hooks/useSaveShippingTimes';

/**
 * Function use to allow the user to navigate between form steps without the prompt.
 *
 * @param {Object} location Location object given by the `getHistory().block` callback argument.
 * @return {boolean} `true` if given location is not another step of our form.
 */
function isNotOurStep( location ) {
	const allowList = new Set( [
		'/' + getNewPath( { pageStep: undefined } ),
		'/' + getNewPath( { pageStep: 1 } ),
		'/' + getNewPath( { pageStep: 2 } ),
	] );
	// TODO: Explore if we can make thich check cleaner given `history`'s API.
	const destination = location.pathname + location.search;
	return ! allowList.has( destination );
}

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
export default function EditFreeCampaign() {
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

	const didTimesChanged = ! isEqual( shippingTimes, savedShippingTimes );
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
		didAnythingChanged,
		isNotOurStep
	);

	const { pageStep = '1' } = getQuery();
	const dashboardURL = getNewPath(
		// Clear the step we were at, but perserve programId to be able to highlight the program.
		{ pageStep: undefined, subpath: undefined },
		'/google/dashboard'
	);

	/**
	 * Update shipping rates and times after users are done
	 * with the changes in Choose Audience step.
	 *
	 * Shipping rates and shipping times that do not have
	 * a corresponding country in target audience will be removed.
	 */
	const updateShippingAfterChooseAudienceStep = () => {
		const finalCountries = getFinalCountries( targetAudience );

		const newShippingRates = loadedShippingRates.filter(
			( shippingRate ) => {
				return finalCountries.includes( shippingRate.country );
			}
		);
		updateShippingRates( newShippingRates );

		const newShippingTimes = loadedShippingTimes.filter(
			( shippingTime ) => {
				return finalCountries.includes( shippingTime.countryCode );
			}
		);
		updateShippingTimes( newShippingTimes );
	};

	const handleChooseAudienceChange = ( change, newTargetAudience ) => {
		updateTargetAudience( newTargetAudience );
	};

	const handleChooseAudienceContinue = () => {
		updateShippingAfterChooseAudienceStep();
		getHistory().push( getNewPath( { pageStep: '2' } ) );
	};

	const handleSetupFreeListingsContinue = async () => {
		// TODO: Disable the form so the user won't be able to input any changes, which could be disregarded.
		try {
			await Promise.allSettled( [
				saveTargetAudience( targetAudience ),
				saveSettings( settings ),
				saveShippingRates( shippingRates ),
				saveShippingTimes( shippingTimes ),
			] );

			// Sync data once our changes are saved, even partially succesfully.
			await fetchSettingsSync();

			createNotice(
				'success',
				__(
					'Your changes to your Free Listings have been saved and will be synced to your Google Merchant Center account.',
					'google-listings-and-ads'
				)
			);
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

	const handleStepClick = ( key ) => {
		/**
		 * When users move from Step 1 Choose Audience to Step 2 Configure listings,
		 * we update shipping rates and shipping times based on the changes in Choose Audience.
		 */
		if ( key === '2' ) {
			updateShippingAfterChooseAudienceStep();
		}

		getHistory().push( getNewPath( { pageStep: key } ) );
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
			<Stepper
				className="gla-setup-stepper"
				currentStep={ pageStep }
				steps={ [
					{
						key: '1',
						label: __(
							'Choose your audience',
							'google-listings-and-ads'
						),
						content: (
							<ChooseAudience
								initialData={ targetAudience }
								onChange={ handleChooseAudienceChange }
								onContinue={ handleChooseAudienceContinue }
							/>
						),
						onClick: handleStepClick,
					},
					{
						key: '2',
						label: __(
							'Configure your product listings',
							'google-listings-and-ads'
						),
						content: (
							<SetupFreeListings
								countries={ getFinalCountries(
									targetAudience
								) }
								settings={ settings }
								onSettingsChange={ ( change, newSettings ) => {
									updateSettings( newSettings );
								} }
								shippingRates={ loadedShippingRates }
								onShippingRatesChange={ updateShippingRates }
								shippingTimes={ loadedShippingTimes }
								onShippingTimesChange={ updateShippingTimes }
								onContinue={ handleSetupFreeListingsContinue }
								submitLabel={ __(
									'Save changes',
									'google-listings-and-ads'
								) }
							/>
						),
						onClick: handleStepClick,
					},
				] }
			/>
		</>
	);
}
