/**
 * External dependencies
 */
import { Link, Stepper } from '@woocommerce/components';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';

/**
 * Internal dependencies
 */
import FullContainer from '.~/components/full-container';
import ChooseAudience from '.~/components/edit-program/free-listings/choose-audience';
import SetupFreeListings from './setup-free-listings';
import './index.scss';

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
	const dashboardURL = getNewPath( {}, '/google/dashboard' );

	const handleChooseAudienceContinue = (/* formData */) => {
		getHistory().push( getNewPath( { pageStep: '2' } ) );
	};

	const handleSetupFreeListingsContinue = () => {
		// TODO: provide a param to highlight edited program.
		// TODO: Make SetupFreeListings actually call this callback.
		getHistory().push( dashboardURL );
	};

	const handleStepClick = ( key ) => {
		getHistory().push( getNewPath( { pageStep: key } ) );
	};
	return (
		<FullContainer>
			<div className="gla-edit-campaign-top-bar">
				<Link
					className="back-button"
					href={ dashboardURL }
					type="wc-admin"
				>
					<GridiconChevronLeft size={ 20 } />
				</Link>
				<span className="title">
					{ __( 'Edit free listings', 'google-listings-and-ads' ) }
				</span>
			</div>
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
							'Configure your free listings',
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
