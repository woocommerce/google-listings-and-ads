/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppRadioContentControl from '.~/components/app-radio-content-control';
import RadioHelperText from '.~/wcdl/radio-helper-text';
import AppDocumentationLink from '.~/components/app-documentation-link';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import ShippingTimeSetup from './shipping-time/shipping-time-setup';
import Subsection from '.~/wcdl/subsection';

const ShippingTimeSection = ( {
	formProps,
	countries: selectedCountryCodes,
} ) => {
	const { getInputProps, values } = formProps;
	const inputProps = getInputProps( 'shipping_time' );

	return (
		<Section
			title={ __( 'Shipping times', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Your shipping times will be shown to potential customers on Google.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="setup-mc-shipping"
							linkId="shipping-read-more"
							href="https://support.google.com/merchants/answer/7050921"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			<VerticalGapLayout size="large">
				<Section.Card>
					<Section.Card.Body>
						<VerticalGapLayout size="large">
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are simple. I can manually estimate flat shipping times.',
									'google-listings-and-ads'
								) }
								value="flat"
								collapsible
							/>
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are complex. I will enter my shipping times manually in Google Merchant Center.',
									'google-listings-and-ads'
								) }
								value="manual"
								collapsible
							>
								<RadioHelperText>
									{ createInterpolateElement(
										__(
											'I understand that if I don’t set this up manually in <link>Google Merchant Center</link>, my products will be disapproved by Google.',
											'google-listings-and-ads'
										),
										{
											link: (
												<AppDocumentationLink
													context="setup-mc-shipping"
													linkId="shipping-manual"
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
				{ values.shipping_time === 'flat' && (
					<Section.Card>
						<Section.Card.Body>
							<Subsection.Title>
								{ __(
									'Estimated shipping times',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<ShippingTimeSetup
								selectedCountryCodes={ selectedCountryCodes }
								formProps={ formProps }
							/>
						</Section.Card.Body>
					</Section.Card>
				) }
			</VerticalGapLayout>
		</Section>
	);
};

export default ShippingTimeSection;
