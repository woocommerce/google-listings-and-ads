/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import AppRadioContentControl from '../../../../components/app-radio-content-control';
import RadioHelperText from '../../../../wcdl/radio-helper-text';
import TrackedExternalLink from '../../../../components/tracked-external-link';
import VerticalGapLayout from '../components/vertical-gap-layout';
import ShippingRateSetup from './shipping-rate-setup';
import './index.scss';

const ShippingRate = ( props ) => {
	const { formProps } = props;
	const { getInputProps } = formProps;

	return (
		<Section
			title={ __( 'Shipping rate', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Your estimated shipping rate will be shown to potential customers on Google.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						{ /* TODO: Link to read more on shipping rate. */ }
						<TrackedExternalLink
							id="setup-mc:shipping-rate"
							href="https://docs.woocommerce.com/"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</TrackedExternalLink>
					</p>
				</div>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<VerticalGapLayout size="large">
						<AppRadioContentControl
							{ ...getInputProps( 'shippingRateOption' ) }
							label={ __(
								'I have a fairly simple shipping setup and I can estimate flat shipping rates.',
								'google-listings-and-ads'
							) }
							value="simple"
						>
							<ShippingRateSetup formProps={ formProps } />
						</AppRadioContentControl>
						<AppRadioContentControl
							{ ...getInputProps( 'shippingRateOption' ) }
							label={ __(
								'I have a more complex shipping setup and I cannot estimate flat shipping rates.',
								'google-listings-and-ads'
							) }
							value="complex"
						>
							<RadioHelperText>
								{ createInterpolateElement(
									__(
										' I’ll set this up manually in <link>Google Merchant Center</link>. I understand that if I don’t set this up, my products will be disapproved.',
										'google-listings-and-ads'
									),
									{
										link: (
											<TrackedExternalLink
												id="setup-mc:shipping-rate-manual"
												href="https://www.google.com/retail/solutions/merchant-center/"
											></TrackedExternalLink>
										),
									}
								) }
							</RadioHelperText>
						</AppRadioContentControl>
					</VerticalGapLayout>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ShippingRate;
