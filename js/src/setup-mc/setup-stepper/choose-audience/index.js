/**
 * External dependencies
 */
import { Button, RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../../../wcdl/section';
import Subsection from '../../../wcdl/subsection';
import AppDocumentationLink from '../../../components/app-documentation-link';
import useAudienceSelectedCountryCodes from '../../../hooks/useAudienceSelectedCountryCodes';
import StepContent from '../components/step-content';
import StepContentHeader from '../components/step-content-header';
import StepContentFooter from '../components/step-content-footer';
import SupportedCountrySelect from './supported-country-select';
import useTargetAudience from '.~/hooks/useTargetAudience';
import './index.scss';
import VerticalGapLayout from '../setup-free-listings/components/vertical-gap-layout';
import AppRadioContentControl from '.~/components/app-radio-content-control';
import RadioHelperText from '.~/wcdl/radio-helper-text';

const ChooseAudience = ( props ) => {
	const { onContinue } = props;
	const [ value, setValue ] = useAudienceSelectedCountryCodes();
	const { data } = useTargetAudience();

	return (
		<div className="gla-choose-audience">
			<StepContent>
				<StepContentHeader
					step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
					title={ __(
						'Choose your audience',
						'google-listings-and-ads'
					) }
					description={ __(
						'Configure who sees your product listings on Google.',
						'google-listings-and-ads'
					) }
				/>
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
									{ __(
										'Language',
										'google-listings-and-ads'
									) }
								</Subsection.Title>
								<Subsection.HelperText className="helper-text">
									{ createInterpolateElement(
										__(
											'Listings can only be displayed in your site language. <link>Read more</link>',
											'google-listings-and-ads'
										),
										{
											link: (
												// TODO: check URL and track event is correct.
												<AppDocumentationLink
													context="setup-mc-audience"
													linkId="site-language"
													href="https://support.google.com/merchants/answer/160491"
												/>
											),
										}
									) }
								</Subsection.HelperText>
								{ data && (
									<RadioControl
										selected={ data.locale }
										options={ [
											{
												label: data.language,
												value: data.locale,
											},
										] }
									/>
								) }
							</Subsection>
							<Subsection>
								<Subsection.Title>
									{ __(
										'Location',
										'google-listings-and-ads'
									) }
								</Subsection.Title>
								<Subsection.HelperText className="helper-text">
									{ __(
										'Your store should already have the appropriate shipping and tax rates (if required) for potential customers in your selected location(s).',
										'google-listings-and-ads'
									) }
								</Subsection.HelperText>
								<VerticalGapLayout>
									<AppRadioContentControl
										label={ __(
											'All countries',
											'google-listings-and-ads'
										) }
									>
										<RadioHelperText>
											{ __(
												'Your listings will be shown in all supported countries.',
												'google-listings-and-ads'
											) }
										</RadioHelperText>
									</AppRadioContentControl>
									<AppRadioContentControl
										label={ __(
											'Selected countries only',
											'google-listings-and-ads'
										) }
									>
										<div className="input">
											<SupportedCountrySelect
												multiple
												value={ value }
												onChange={ setValue }
											/>
										</div>
										<RadioHelperText>
											{ __(
												'Can’t find a country? Only supported countries can be selected.',
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
					<Button isPrimary onClick={ onContinue }>
						{ __( 'Continue', 'google-listings-and-ads' ) }
					</Button>
				</StepContentFooter>
			</StepContent>
		</div>
	);
};

export default ChooseAudience;
