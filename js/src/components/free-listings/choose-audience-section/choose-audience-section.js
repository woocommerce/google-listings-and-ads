/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
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
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-audience', link_id: 'site-language', href: 'https://support.google.com/merchants/answer/160637' }`
 */
const ChooseAudienceSection = () => {
	const {
		values,
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
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
									{ renderRequestedValidation( 'countries' ) }
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
