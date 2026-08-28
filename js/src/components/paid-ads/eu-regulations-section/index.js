/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useTargetAudience from '~/hooks/useTargetAudience';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import EuPoliticalContentCard from './eu-political-content-card';
import Section from '~/components/section';

/**
 * Displays the EU regulations section if any EU country is selected.
 * If no EU country is selected, nothing is rendered.
 *
 * @param {Object} props React props.
 * @param {'setup-ads'|'create-ads'|'edit-ads'} props.context A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
const EuRegulationsSection = ( { context } ) => {
	const {
		data: { continents },
		hasFinishedResolution: hasResolvedCountriesAndContinents,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const { data, hasFinishedResolution: hasResolvedTargetAudience } =
		useTargetAudience();

	if ( ! hasResolvedCountriesAndContinents || ! hasResolvedTargetAudience ) {
		return null;
	}

	const { countries } = data;

	if ( ! countries.length ) {
		return null;
	}

	const euCountries = continents.EU.countries || [];
	const isAnyEUCountrySelected = countries.some( ( countryCode ) =>
		euCountries.includes( countryCode )
	);

	if ( ! isAnyEUCountrySelected ) {
		return null;
	}

	return (
		<Section
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
			title={ __( 'EU regulations', 'google-listings-and-ads' ) }
		>
			<EuPoliticalContentCard context={ context } />
		</Section>
	);
};

export default EuRegulationsSection;
