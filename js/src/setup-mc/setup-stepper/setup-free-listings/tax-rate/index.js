/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import RadioHelperText from '../../../../wcdl/radio-helper-text';
import AppRadioContentControl from '../../../../components/app-radio-content-control';
import AppDocumentationLink from '../../../../components/app-documentation-link';
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
						<AppDocumentationLink
							context="setup-mc-tax-rate"
							linkId="tax-rate-read-more"
							href="https://docs.woocommerce.com/"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
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
							<RadioHelperText>
								{ __(
									'Google’s estimated tax rates will automatically be applied to my product listings.',
									'google-listings-and-ads'
								) }
							</RadioHelperText>
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
										'I’ll set my tax rates up manually in <link>Google Merchant Center</link>. I understand that if I don’t set this up, my products will be disapproved.',
										'google-listings-and-ads'
									),
									{
										link: (
											<AppDocumentationLink
												context="setup-mc-tax-rate"
												linkId="tax-rate-manual"
												href="https://www.google.com/retail/solutions/merchant-center/"
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
