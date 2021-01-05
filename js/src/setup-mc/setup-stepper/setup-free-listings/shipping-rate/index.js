/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import AppRadioContentControl from '../../../../components/app-radio-content-control';
import RadioHelperText from '../../../../wcdl/radio-helper-text';
import VerticalGapLayout from '../components/vertical-gap-layout';
import SimpleShippingRateSetup from './SimpleShippingRateSetup';
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
						<Link
							type="external"
							href="https://docs.woocommerce.com/"
							target="_blank"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</Link>
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
							<SimpleShippingRateSetup formProps={ formProps } />
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
											<Link
												type="external"
												href="https://www.google.com/retail/solutions/merchant-center/"
												target="_blank"
											/>
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
