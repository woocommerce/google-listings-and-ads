/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../wcdl/section';
import StepContentHeader from '../components/step-content-header';
import './index.scss';

const ChooseAudience = () => {
	return (
		<div className="gla-choose-audience">
			<StepContentHeader
				step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
				title={ __(
					'Choose your audience',
					'google-listings-and-ads'
				) }
				description={ __(
					'Configure who sees your product listings on Google.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				title={ __( 'Audience', 'google-listings-and-ads' ) }
				description={
					<div>
						<p>
							{ __(
								'Your store must have the appropriate shipping and tax rates (if required) for customers in all your selected countries.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							<Link
								type="external"
								href="https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/"
								target="_blank"
							>
								{ __( 'Read more', 'google-listings-and-ads' ) }
							</Link>
						</p>
					</div>
				}
			>
				<Section.Card>
					<Section.Card.Body>TODO:</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default ChooseAudience;
