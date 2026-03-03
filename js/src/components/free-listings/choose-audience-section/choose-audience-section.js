/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppRadioContentControl from '~/components/app-radio-content-control';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import RadioHelperText from '~/components/radio-helper-text';
import SupportedCountrySelect from '~/components/supported-country-select';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './choose-audience-section.scss';

/**
 * Section form to choose audience.
 *
 * To be used in onboarding and further editing.
 * Does not provide any save strategy, this is to be bound externally.
 */
const ChooseAudienceSection = () => {
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const {
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	const content = hasGoogleMCConnection
		? {
				description: __(
					'Where do you want to sell your products?',
					'google-listings-and-ads'
				),
				titleHelper: __(
					'Your store should already have the appropriate shipping and tax rates (if required) for potential customers in your selected location(s).',
					'google-listings-and-ads'
				),
				radioHelper: __(
					'Your listings will be shown in all supported countries.',
					'google-listings-and-ads'
				),
		  }
		: {
				description: __(
					'Where do you want to advertise your services?',
					'google-listings-and-ads'
				),
				titleHelper: __(
					'Where do you offer your services?',
					'google-listings-and-ads'
				),
				radioHelper: __(
					'Your ad will be shown in all supported countries.',
					'google-listings-and-ads'
				),
		  };

	const { description, titleHelper, radioHelper } = content;

	return (
		<>
			<Section
				className="gla-choose-audience-section"
				title={ __( 'Audience', 'google-listings-and-ads' ) }
				description={ <p>{ description }</p> }
			>
				<Section.Card>
					<Section.Card.Body>
						<Subsection>
							<Subsection.Title>
								{ __( 'Location', 'google-listings-and-ads' ) }
							</Subsection.Title>
							<Subsection.HelperText>
								{ titleHelper }
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
										{ radioHelper }
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
