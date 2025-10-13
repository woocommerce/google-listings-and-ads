/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import EuPoliticalContentCard from './eu-political-content-card';
import Section from '~/components/section';

/**
 * Displays the EU regulations section if any EU country is selected.
 */
const EuRegulationsSection = ( { context } ) => {
	const {
		data: { continents },
		hasFinishedResolution,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const {
		adapter: { countryCodes },
	} = useAdaptiveFormContext();

	if (
		! hasFinishedResolution ||
		! countryCodes.length ||
		context === 'edit_ads'
	) {
		return null;
	}

	const euCountries = continents.EU.countries || [];
	const isAnyEUCountrySelected = countryCodes.some( ( countryCode ) =>
		euCountries.includes( countryCode )
	);

	if ( ! isAnyEUCountrySelected ) {
		return null;
	}

	return (
		<Section
			title={ __( 'EU regulations', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Advertisers must confirm whether their ads include political content.',
							'google-listings-and-ads'
						) }
					</p>
				</div>
			}
		>
			<EuPoliticalContentCard context={ context } />
		</Section>
	);
};

export default EuRegulationsSection;
