/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import useHasEuTargetCountry from '~/hooks/useHasEuTargetCountry';
import EuPoliticalContentField from './eu-political-content-field';

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
		<Section.Card>
			<Section.Card.Body padding="medium">
				<Flex direction="column" align="stretch" gap={ 6 }>
					<FlexBlock>
						<Subsection.Title>
							{ __(
								'EU regulations',
								'google-listings-and-ads'
							) }
						</Subsection.Title>
						<Subsection.Subtitle>
							{ __(
								'Advertisers must confirm whether their ads include political content.',
								'google-listings-and-ads'
							) }
						</Subsection.Subtitle>
					</FlexBlock>
					<EuPoliticalContentField context={ context } />
				</Flex>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EuRegulationsCard;
