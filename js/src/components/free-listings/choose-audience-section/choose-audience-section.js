/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';
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
import SupportedCountrySelect from '.~/components/supported-country-select';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import './choose-audience-section.scss';

/**
 * Section form to choose audience.
 *
 * To be used in onboarding and further editing.
 * Does not provide any save strategy, this is to be bound externaly.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-audience', link_id: 'site-language', href: 'https://support.google.com/merchants/answer/160637' }`
 */
const ChooseAudienceSection = ( { formProps } ) => {
	const { values, getInputProps } = formProps;
	const { locale, language } = values;

	return (
		<>
			<Section
				className="gla-choose-audience-section"
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
							<Subsection.HelperText className="gla-choose-audience-section__language-helper">
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
							<Subsection.HelperText>
								{ __(
									'Your store should already have the appropriate shipping and tax rates (if required) for potential customers in your selected location(s).',
									'google-listings-and-ads'
								) }
							</Subsection.HelperText>
							<VerticalGapLayout size="medium">
								<AppRadioContentControl
									{ ...getInputProps( 'location' ) }
									collapsible={ true }
									label={ __(
										'Selected countries only',
										'google-listings-and-ads'
									) }
									value="selected"
								>
									<SupportedCountrySelect
										multiple
										{ ...getInputProps( 'countries' ) }
										help={ __(
											'Can’t find a country? Only supported countries can be selected.',
											'google-listings-and-ads'
										) }
									/>
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
		</>
	);
};

export default ChooseAudienceSection;
