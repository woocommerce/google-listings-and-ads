/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContentHeader from '.~/components/stepper/step-content-header';
import heroImageURL from './google-free-listing.png';
import './index.scss';

/**
 * Hero element for free listing configuration.
 */
const Hero = () => {
	return (
		<div className="gla-setup-free-listing-hero">
			<StepContentHeader
				className="hero-text"
				title={ __(
					'Configure your product listings',
					'google-listings-and-ads'
				) }
				description={
					<div>
						<p className="hero-text__subtitle">
							{ __(
								'Your product listings will look something like this.',
								'google-listings-and-ads'
							) }
						</p>
						<p className="hero-text__body">
							{ __(
								'Your product details, estimated shipping info and tax details will be displayed across Google.',
								'google-listings-and-ads'
							) }
						</p>
					</div>
				}
			/>
			<img
				className="gla-setup-free-listing-hero__image"
				src={ heroImageURL }
				alt={ __(
					'Google Shopping search results example',
					'google-listings-and-ads'
				) }
			/>
		</div>
	);
};

export default Hero;
