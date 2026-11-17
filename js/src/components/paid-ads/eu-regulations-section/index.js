/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useHasEuTargetCountry from '~/hooks/useHasEuTargetCountry';
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
	const { hasFinishedResolution, hasEuTargetCountry } =
		useHasEuTargetCountry();

	if ( ! hasFinishedResolution || ! hasEuTargetCountry ) {
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
