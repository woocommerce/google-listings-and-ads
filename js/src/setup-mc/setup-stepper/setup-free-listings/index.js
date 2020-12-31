/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import StepContent from '../components/step-content';
import StepContentHeader from '../components/step-content-header';
import StepContentFooter from '../components/step-content-footer';
import { ReactComponent as GoogleFreeListingImage } from './google-free-listing.svg';
import ShippingRate from './shipping-rate';
import PreLaunchChecklist from './pre-launch-checklist';
import './index.scss';

const SetupFreeListings = () => {
	// TODO: check form values and make button disabled/enabled.
	const isCompleteSetupButtonDisabled = true;

	// TODO: call backend API.
	const onCompleteSetupClick = () => {};

	return (
		<div className="gla-setup-free-listings">
			<div className="hero">
				<StepContentHeader
					className="hero__header"
					step={ __( 'STEP THREE', 'google-listings-and-ads' ) }
					title={ __(
						'Configure your product listings',
						'google-listings-and-ads'
					) }
					description={
						<div>
							<p className="subtitle">
								{ __(
									'Your free product listings will look something like this.',
									'google-listings-and-ads'
								) }
							</p>
							<p className="body">
								{ __(
									'Your product details, estimated shipping info and tax details will be displayed across Google.',
									'google-listings-and-ads'
								) }
							</p>
						</div>
					}
				/>
				<GoogleFreeListingImage viewBox="0 0 720 319"></GoogleFreeListingImage>
			</div>
			<StepContent>
				<ShippingRate />
				<PreLaunchChecklist />
				<StepContentFooter>
					<Button
						isPrimary
						disabled={ isCompleteSetupButtonDisabled }
						onClick={ onCompleteSetupClick }
					>
						{ __( 'Complete setup', 'google-listings-and-ads' ) }
					</Button>
				</StepContentFooter>
			</StepContent>
		</div>
	);
};

export default SetupFreeListings;
