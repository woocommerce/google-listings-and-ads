/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import Section from '.~/wcdl/section';
import AppRadioContentControl from '.~/components/app-radio-content-control';
import RadioHelperText from '.~/wcdl/radio-helper-text';
import AppDocumentationLink from '.~/components/app-documentation-link';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import FlatShippingRatesInputCards from './flat-shipping-rates-input-cards';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useMCSetup from '.~/hooks/useMCSetup';

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-shipping', link_id: 'shipping-read-more', href: 'https://support.google.com/merchants/answer/7050921' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-shipping', link_id: 'shipping-manual', href: 'https://www.google.com/retail/solutions/merchant-center/' }`
 */

const ShippingRateSection = () => {
	const { getInputProps, values } = useAdaptiveFormContext();
	const { settings } = useSettings();
	const { hasFinishedResolution, data: mcSetup } = useMCSetup();
	const inputProps = getInputProps( 'shipping_rate' );

	// Hide the automatic shipping rate option if there are no shipping rates and the merchant is onboarding.
	const hideAutomatticShippingRate =
		! settings?.shipping_rates_count &&
		hasFinishedResolution &&
		mcSetup?.status === 'incomplete';

	return (
		<Section
			title={ __( 'Shipping rates', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Your estimated shipping rates and times will be shown to potential customers on Google.',
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
							{ ! hideAutomatticShippingRate && (
								<AppRadioContentControl
									{ ...inputProps }
									label={ __(
										'Automatically sync my store’s shipping settings to Google.',
										'google-listings-and-ads'
									) }
									value="automatic"
									collapsible
								>
									<RadioHelperText>
										{ __(
											'My current settings and any future changes to my store’s shipping rates and classes will be automatically synced to Google Merchant Center.',
											'google-listings-and-ads'
										) }
									</RadioHelperText>
								</AppRadioContentControl>
							) }
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are simple. I can manually estimate flat shipping rates.',
									'google-listings-and-ads'
								) }
								value="flat"
								collapsible
							/>
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.',
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
				{ values.shipping_rate === 'flat' && (
					<FlatShippingRatesInputCards />
				) }
			</VerticalGapLayout>
		</Section>
	);
};

export default ShippingRateSection;
