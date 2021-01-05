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
import RadioHelperText from '../../../../wcdl/radio-helper-text';
import AppRadioContentControl from '../../../../components/app-radio-content-control';
import VerticalGapLayout from '../components/vertical-gap-layout';

const TaxRate = ( props ) => {
	const {
		formProps: { getInputProps },
	} = props;

	return (
		<Section
			title={ __(
				'Tax rate (required for U.S. only)',
				'google-listings-and-ads'
			) }
			description={
				<div>
					<p>
						{ __(
							'This tax rate will be shown to potential customers, together with the cost of your product.',
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
							{ ...getInputProps( 'taxRateOption' ) }
							label={ __(
								'My store uses destination-based tax rates.',
								'google-listings-and-ads'
							) }
							value="yes"
						>
							TODO
						</AppRadioContentControl>
						<AppRadioContentControl
							{ ...getInputProps( 'taxRateOption' ) }
							label={ __(
								'My store does not use destination-based tax rates.',
								'google-listings-and-ads'
							) }
							value="no"
						>
							<RadioHelperText>
								{ createInterpolateElement(
									__(
										' I’ll set my tax rates up manually in <link>Google Merchant Center</link>. I understand that if I don’t set this up, my products will be disapproved.',
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

export default TaxRate;
