/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import ChooseAudience from '.~/components/free-listings/choose-audience';
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
	const { pageStep = '1' } = getQuery();
	const dashboardURL = getNewPath(
		// Clear the step we were at, but perserve programId to be able to highlight the program.
		{ pageStep: undefined },
		'/google/dashboard'
	);

	const handleChooseAudienceContinue = () => {
		getHistory().push( getNewPath( { pageStep: '2' } ) );
	};

	const handleSetupFreeListingsContinue = () => {
		// TODO: Make SetupFreeListings actually call this callback.
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
