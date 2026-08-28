/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import RadioHelperText from '~/components/radio-helper-text';
import AppRadioContentControl from '~/components/app-radio-content-control';
import AppDocumentationLink from '~/components/app-documentation-link';
import VerticalGapLayout from '~/components/vertical-gap-layout';

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-tax-rate', link_id: 'tax-rate-read-more', href: 'https://support.google.com/merchants/answer/160162' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-tax-rate', link_id: 'tax-rate-manual', href: 'https://www.google.com/retail/solutions/merchant-center/' }`
 */

/**
 * Renders the options of tax rate.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} [props.handleSubmit] Function to handle form submission.
 */
const TaxRate = ( { handleSubmit } ) => {
	const {
		getInputProps,
		adapter: { isSubmitting },
	} = useAdaptiveFormContext();

	const { onChange, ...inputProps } = getInputProps( 'tax_rate' );

	const handleOnChange = ( value ) => {
		onChange( value );
		handleSubmit();
	};

	return (
		<Section
			description={
				<div>
					<p>
						{ __(
							'This tax rate will be shown to potential customers, together with the cost of your product.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="setup-mc-tax-rate"
							href="https://support.google.com/merchants/answer/160162"
							linkId="tax-rate-read-more"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
			title={ __(
				'Tax rate (required for U.S. only)',
				'google-listings-and-ads'
			) }
		>
			<Section.Card>
				<Section.Card.Body>
					<VerticalGapLayout size="large">
						<AppRadioContentControl
							{ ...inputProps }
							disabled={ isSubmitting }
							label={ __(
								'My store uses destination-based tax rates.',
								'google-listings-and-ads'
							) }
							onChange={ handleOnChange }
							value="destination"
							collapsible
						>
							<RadioHelperText>
								{ __(
									'Google’s estimated tax rates will automatically be applied to my product listings.',
									'google-listings-and-ads'
								) }
							</RadioHelperText>
						</AppRadioContentControl>
						<AppRadioContentControl
							{ ...inputProps }
							disabled={ isSubmitting }
							label={ __(
								'My store does not use destination-based tax rates.',
								'google-listings-and-ads'
							) }
							onChange={ handleOnChange }
							value="manual"
							collapsible
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
												href="https://www.google.com/retail/solutions/merchant-center/"
												linkId="tax-rate-manual"
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
