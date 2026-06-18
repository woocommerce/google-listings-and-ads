/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import Section from '~/components/section';
import AppRadioContentControl from '~/components/app-radio-content-control';
import RadioHelperText from '~/components/radio-helper-text';
import AppDocumentationLink from '~/components/app-documentation-link';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import useSettings from '~/hooks/useSettings';
import useMCSetup from '~/hooks/useMCSetup';

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-shipping', link_id: 'shipping-read-more', href: 'https://support.google.com/merchants/answer/7050921' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-shipping', link_id: 'shipping-manual', href: 'https://www.google.com/retail/solutions/merchant-center/' }`
 *
 * @param {Object} props
 * @param {JSX.Element} props.children Optional children to render below the shipping rate method options.
 */
const ShippingRateMethodSection = ( { children } ) => {
	const { getInputProps } = useAdaptiveFormContext();
	const { settings } = useSettings();
	const { hasFinishedResolution, data: mcSetup } = useMCSetup();
	const inputProps = getInputProps( 'shipping_rate' );
	const { isMultiLingualStore } = glaData;

	// Take a one-time snapshot once settings have resolved.
	// Using a ref so mid-session auto-saves (which flip isFlatShippingRate to
	// false) don't hide the option after it was already shown.
	// For multilingual stores the flat option is shown only when the stored
	// value is already 'flat' (i.e. the merchant previously configured it).
	// The SavedSetupStepper auto-save initialises multilingual stores to
	// 'manual', never 'flat', so isFlatShippingRate is false on a clean
	// onboarding and the option is correctly hidden.
	const showFlatOptionRef = useRef( null );
	if ( showFlatOptionRef.current === null && settings !== undefined ) {
		const isFlatShippingRate =
			settings.shipping_rate === SHIPPING_RATE_METHOD.FLAT;
		showFlatOptionRef.current = ! isMultiLingualStore || isFlatShippingRate;
	}
	const showFlatOption = showFlatOptionRef.current ?? ! isMultiLingualStore;

	// Hide the automatic shipping rate option if there are no shipping rates and the merchant is onboarding.
	const hideAutomaticShippingRate =
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
			<Section.Card>
				<Section.Card.Body>
					<VerticalGapLayout size="large">
						{ ! hideAutomaticShippingRate && (
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'Automatically sync my store’s shipping settings to Google.',
									'google-listings-and-ads'
								) }
								value={ SHIPPING_RATE_METHOD.AUTOMATIC }
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

						{ showFlatOption && (
							<AppRadioContentControl
								{ ...inputProps }
								label={ __(
									'My shipping settings are simple. I can manually estimate flat shipping rates.',
									'google-listings-and-ads'
								) }
								value={ SHIPPING_RATE_METHOD.FLAT }
								collapsible
							/>
						) }

						<AppRadioContentControl
							{ ...inputProps }
							label={ __(
								'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.',
								'google-listings-and-ads'
							) }
							value={ SHIPPING_RATE_METHOD.MANUAL }
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
			{ children }
		</Section>
	);
};

export default ShippingRateMethodSection;
