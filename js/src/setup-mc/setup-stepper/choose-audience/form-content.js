/**
 * External dependencies
 */
import { Button, RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppRadioContentControl from '.~/components/app-radio-content-control';
import AppDocumentationLink from '.~/components/app-documentation-link';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import RadioHelperText from '.~/wcdl/radio-helper-text';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import SupportedCountrySelect from '.~/components/supported-country-select';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import useAutoSaveTargetAudienceEffect from './useAutoSaveTargetAudienceEffect';
import useAutoClearShippingEffect from './useAutoClearShippingEffect';
import '.~/components/free-listings/choose-audience/index.scss';

/**
 * Form to choose audience.
 * Auto-saves.
 *
 * @see .~/components/free-listings/choose-audience/form-content
 * @param {Object} props
 */
const FormContent = ( props ) => {
	const { formProps } = props;
	const { values, isValidForm, getInputProps, handleSubmit } = formProps;
	const { locale, language } = values;

	useAutoSaveTargetAudienceEffect( values );
	useAutoClearShippingEffect( values.location, values.countries );

	return (
		<>
			<Section
				title={ __( 'Audience', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'Where do you want to sell your products?',
							'google-listings-and-ads'
						) }
					</p>
				}
			>
				<Section.Card>
					<Section.Card.Body>
						<Subsection>
							<Subsection.Title>
								{ __( 'Language', 'google-listings-and-ads' ) }
							</Subsection.Title>
							<Subsection.HelperText className="helper-text">
								{ createInterpolateElement(
									__(
										'Listings can only be displayed in your site language. <link>Read more</link>',
										'google-listings-and-ads'
									),
									{
										link: (
											<AppDocumentationLink
												context="setup-mc-audience"
												linkId="site-language"
												href="https://support.google.com/merchants/answer/160637"
											/>
										),
									}
								) }
							</Subsection.HelperText>
							<RadioControl
								selected={ locale }
								options={ [
									{
										label: language,
										value: locale,
									},
								] }
							/>
						</Subsection>
						<Subsection>
							<Subsection.Title>
								{ __( 'Location', 'google-listings-and-ads' ) }
							</Subsection.Title>
							<Subsection.HelperText className="helper-text">
								{ __(
									'Your store should already have the appropriate shipping and tax rates (if required) for potential customers in your selected location(s).',
									'google-listings-and-ads'
								) }
							</Subsection.HelperText>
							<VerticalGapLayout>
								<AppRadioContentControl
									{ ...getInputProps( 'location' ) }
									collapsible={ true }
									label={ __(
										'Selected countries only',
										'google-listings-and-ads'
									) }
									value="selected"
								>
									<div className="input">
										<SupportedCountrySelect
											multiple
											{ ...getInputProps( 'countries' ) }
										/>
									</div>
									<RadioHelperText>
										{ __(
											'Can’t find a country? Only supported countries can be selected.',
											'google-listings-and-ads'
										) }
									</RadioHelperText>
								</AppRadioContentControl>
								<AppRadioContentControl
									{ ...getInputProps( 'location' ) }
									label={ __(
										'All countries',
										'google-listings-and-ads'
									) }
									value="all"
								>
									<RadioHelperText>
										{ __(
											'Your listings will be shown in all supported countries.',
											'google-listings-and-ads'
										) }
									</RadioHelperText>
								</AppRadioContentControl>
							</VerticalGapLayout>
						</Subsection>
					</Section.Card.Body>
				</Section.Card>
			</Section>
			<StepContentFooter>
				<Button
					isPrimary
					disabled={ ! isValidForm }
					onClick={ handleSubmit }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</Button>
			</StepContentFooter>
		</>
	);
};

export default FormContent;
