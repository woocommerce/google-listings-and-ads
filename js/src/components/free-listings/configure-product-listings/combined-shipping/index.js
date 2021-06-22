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
import ShippingRateSetup from '../shipping-rate/shipping-rate-setup';
import ShippingTimeSetup from '../shipping-time/shipping-time-setup';
import './index.scss';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Form section to set shipping rate and time per country.
 *
 * @param {Object} props
 * @param {Object} props.formProps
 * @param {Array<CountryCode>} props.countries List of supported countries.
 */
const CombinedShipping = ( { formProps, countries: selectedCountryCodes } ) => {
	const { getInputProps, values } = formProps;

	// Note: since we only use `shipping_rate` to determine how to syncboth shipping rates and times,
	//       so here also only apply `shipping_rate` to initial form data and sync its manipulation.
	// Please refer to the `should_sync_shipping` method in ~/src/API/Google/Settings.php
	const shipping = values.shipping_rate;
	const shippingProps = {
		...getInputProps( 'shipping_rate' ),
		onChange( value ) {
			getInputProps( 'shipping_rate' ).onChange( value );
			getInputProps( 'shipping_time' ).onChange( value );
		},
	};

	return (
		<Section
			title={ __( 'Shipping rate and time', 'google-listings-and-ads' ) }
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
							<AppRadioContentControl
								{ ...shippingProps }
								label={ __(
									'I have a fairly simple shipping setup and I can estimate flat shipping rates and times.',
									'google-listings-and-ads'
								) }
								value="flat"
								collapsible
							/>
							<AppRadioContentControl
								{ ...shippingProps }
								label={ __(
									'I have a more complex shipping setup and I cannot estimate flat shipping rates and times.',
									'google-listings-and-ads'
								) }
								value="manual"
								collapsible
							>
								<RadioHelperText>
									{ createInterpolateElement(
										__(
											' I’ll set this up manually in <link>Google Merchant Center</link>. I understand that if I don’t set this up, my products will be disapproved.',
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

				{ shipping === 'flat' && (
					<>
						<Section.Card>
							<Section.Card.Body>
								<h3
									variant="title.small"
									className="gla-shipping-title"
								>
									{ __(
										'Estimated shipping rates',
										'google-listings-and-ads'
									) }
								</h3>
								<ShippingRateSetup
									selectedCountryCodes={
										selectedCountryCodes
									}
									formProps={ formProps }
								/>
							</Section.Card.Body>
						</Section.Card>

						<Section.Card>
							<Section.Card.Body>
								<h3
									variant="title.small"
									className="gla-shipping-title"
								>
									{ __(
										'Estimated shipping times',
										'google-listings-and-ads'
									) }
								</h3>
								<ShippingTimeSetup
									selectedCountryCodes={
										selectedCountryCodes
									}
									formProps={ formProps }
								/>
							</Section.Card.Body>
						</Section.Card>
					</>
				) }
			</VerticalGapLayout>
		</Section>
	);
};

export default CombinedShipping;
