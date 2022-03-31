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
import FlatShippingRatesInputCards from './flat-shipping-rates-input-cards';

const ShippingRateSection = ( { formProps, audienceCountries } ) => {
	const { getInputProps, values, setValue } = formProps;
	const inputProps = getInputProps( 'shipping_rate' );

	const getShippingRateOptionChangeHandler = ( onChange ) => ( value ) => {
		switch ( value ) {
			case 'automatic':
			case 'flat':
				setValue( 'shipping_time', 'flat' );
				break;

			case 'manual':
				setValue( 'shipping_time', 'manual' );
				break;
		}

		onChange( value );
	};

	return (
		<Section
			title={ __( 'Shipping rates', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Your shipping rates will be shown to potential customers on Google.',
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
								label={ createInterpolateElement(
									__(
										'<strong>Recommended:</strong> Automatically sync my store’s shipping settings to Google.',
										'google-listings-and-ads'
									),
									{
										strong: <strong></strong>,
									}
								) }
								value="automatic"
								collapsible
								onChange={ getShippingRateOptionChangeHandler(
									inputProps.onChange
								) }
							>
								<RadioHelperText>
									{ __(
										'My current settings and any future changes to my store’s shipping rates and classes will be automatically synced to Google Merchant Center.',
										'google-listings-and-ads'
									) }
								</RadioHelperText>
							</AppRadioContentControl>
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are simple. I can manually estimate flat shipping rates.',
									'google-listings-and-ads'
								) }
								value="flat"
								collapsible
								onChange={ getShippingRateOptionChangeHandler(
									inputProps.onChange
								) }
							/>
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.',
									'google-listings-and-ads'
								) }
								value="manual"
								collapsible
								onChange={ getShippingRateOptionChangeHandler(
									inputProps.onChange
								) }
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
				{ values.shipping_rate === 'flat' && (
					<FlatShippingRatesInputCards
						audienceCountries={ audienceCountries }
						formProps={ formProps }
					/>
				) }
			</VerticalGapLayout>
		</Section>
	);
};

export default ShippingRateSection;
