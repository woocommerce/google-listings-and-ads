/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import EuPoliticalContentField from './eu-political-content-field';

/**
 * Displays a checkbox to confirm whether political content is advertised.
 *
 * @param {Object} props React props.
 * @param {'setup-ads'|'create-ads'|'edit-ads'} props.context A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
const EuPoliticalContentCard = ( { context } ) => {
	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __( 'EU political content', 'google-listings-and-ads' ) }
				</Section.Card.Title>
				<EuPoliticalContentField context={ context } />
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EuPoliticalContentCard;
