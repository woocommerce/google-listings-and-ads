/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import useHasEuTargetCountry from './useHasEuTargetCountry';
import EuPoliticalContentField from './eu-political-content-field';
import './eu-regulations-card.scss';

/**
 * Displays the EU regulations card with the EU political content field when any target country is in the EU.
 * Renders nothing otherwise.
 *
 * @param {Object} props React props.
 * @param {'setup-ads'|'create-ads'|'edit-ads'} props.context A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
const EuRegulationsCard = ( { context } ) => {
	const { hasFinishedResolution, hasEuTargetCountry } =
		useHasEuTargetCountry();

	if ( ! hasFinishedResolution || ! hasEuTargetCountry ) {
		return null;
	}

	return (
		<Section.Card className="gla-eu-regulations-card">
			<Section.Card.Body>
				<VerticalGapLayout size="large">
					<div>
						<Section.Card.Title>
							{ __(
								'EU regulations',
								'google-listings-and-ads'
							) }
						</Section.Card.Title>
						<Subsection.Subtitle>
							{ __(
								'Advertisers must confirm whether their ads include political content.',
								'google-listings-and-ads'
							) }
						</Subsection.Subtitle>
					</div>
					<EuPoliticalContentField context={ context } />
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EuRegulationsCard;
