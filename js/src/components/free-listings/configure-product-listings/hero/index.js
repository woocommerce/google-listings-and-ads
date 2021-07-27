/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import StepContentHeader from '.~/components/stepper/step-content-header';
import './index.scss';

/**
 * Full URL to the hero image.
 */
const heroImageURL =
	glaData.assetsURL +
	'js/src/components/free-listings/configure-product-listings/hero/google-free-listing.svg';

/**
 * Hero element for free listing configuration.
 *
 * @param {Object} props
 * @param {string} props.stepHeader Header text to indicate the step number.
 */
const Hero = ( { stepHeader } ) => {
	return (
		<div className="gla-setup-free-listing-hero">
			<StepContentHeader
				className="hero-text"
				step={ stepHeader }
				title={ __(
					'Configure your product listings',
					'google-listings-and-ads'
				) }
				description={
					<div>
						<p className="hero-text__subtitle">
							{ __(
								'Your free product listings will look something like this.',
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
			<div className="hero-image">
				<img
					src={ heroImageURL }
					alt={ __(
						'Google Shopping search results example',
						'google-listings-and-ads'
					) }
					width="720"
					height="319"
				/>
			</div>
		</div>
	);
};

export default Hero;
