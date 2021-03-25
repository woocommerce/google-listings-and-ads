/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import ChooseAudience from '.~/components/free-listings/choose-audience';
import useTargetAudience from '.~/hooks/useTargetAudience';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import SetupFreeListings from './setup-free-listings';

/**
 * Page Component to edit free campaigns.
 * Provides two steps:
 *  - Choose your audience
 *  - Configure your free listings
 * Given the user is editing an existing campaign, both steps should be available.
 * The displayed step is driven by `pageStep` URL parameter, to make it easier to permalink and navigate back and forth.
 */
export default function EditFreeCampaign() {
	const { data: savedTargetAudience } = useTargetAudience();
	const { settings: savedSettings } = useSettings();
	const { saveTargetAudience, saveSettings } = useAppDispatch();

	const [ targetAudience, updateTargetAudience ] = useState(
		savedTargetAudience
	);
	const [ settings, updateSettings ] = useState( savedSettings );

	useEffect( () => {
		if ( savedTargetAudience ) {
			updateTargetAudience( savedTargetAudience );
		}
		if ( savedSettings ) {
			updateSettings( savedSettings );
		}
	}, [ savedTargetAudience, savedSettings ] );

	const { pageStep = '1' } = getQuery();
	const dashboardURL = getNewPath(
		// Clear the step we were at, but perserve programId to be able to highlight the program.
		{ pageStep: undefined },
		'/google/dashboard'
	);

	const handleChooseAudienceContinue = () => {
		getHistory().push( getNewPath( { pageStep: '2' } ) );
	};

	const handleSetupFreeListingsContinue = async () => {
		// TODO: Disable the form so the user won't be able to input any changes, which could be disregarded.
		//       Put Submit button in pending state.
		await Promise.allSettled( [
			saveTargetAudience( targetAudience ),
			saveSettings( settings ),
			// TODO: save batched shipping times and rates
		] );
		// TODO: Call setting sync.
		// TODO notify errors.

		// Enable the submit button.
		getHistory().push( dashboardURL );
	};

	const handleStepClick = ( key ) => {
		getHistory().push( getNewPath( { pageStep: key } ) );
	};
	// TODO: Wse ChooseAudience and SetupFreeListings customized for this page.
	return (
		<FullContainer>
			<TopBar
				title={ __( 'Edit free listings', 'google-listings-and-ads' ) }
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
								stepHeader={ __(
									'STEP ONE',
									'google-listings-and-ads'
								) }
								initialData={ targetAudience }
								onChange={ ( change, newTargetAudience ) =>
									updateTargetAudience( newTargetAudience )
								}
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
								stepHeader={ __(
									'STEP TWO',
									'google-listings-and-ads'
								) }
								settings={ settings }
								onChange={ ( change, newSettings ) => {
									updateSettings( newSettings );
								} }
								onContinue={ handleSetupFreeListingsContinue }
							/>
						),
						onClick: handleStepClick,
					},
				] }
			/>
		</FullContainer>
	);
}
