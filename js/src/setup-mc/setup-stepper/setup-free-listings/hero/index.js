/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContentHeader from '../../components/step-content-header';
import { ReactComponent as GoogleFreeListingImage } from './google-free-listing.svg';
import './index.scss';

const Hero = () => {
	return (
		<div className="gla-setup-free-listing-hero">
			<StepContentHeader
				className="hero-text"
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
			<div className="hero-image">
				<GoogleFreeListingImage viewBox="0 0 720 319"></GoogleFreeListingImage>
			</div>
		</div>
	);
};

export default Hero;
