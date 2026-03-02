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

	const description = hasGoogleMCConnection
		? __(
				'Where do you want to sell your products?',
				'google-listings-and-ads'
		  )
		: __(
				'Where do you want to advertise your services?',
				'google-listings-and-ads'
		  );

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
								{ __(
									'Where do you offer your services?',
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
											'Your ad will be shown in all supported countries.',
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
