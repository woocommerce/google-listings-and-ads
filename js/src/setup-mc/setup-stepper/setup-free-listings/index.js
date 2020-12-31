/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContent from '../components/step-content';
import StepContentHeader from '../components/step-content-header';
import { ReactComponent as GoogleFreeListingImage } from './google-free-listing.svg';
import ShippingRate from './shipping-rate';
import './index.scss';

const SetupFreeListings = () => {
	// TODO:
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
			</StepContent>
		</div>
	);
};

export default SetupFreeListings;
