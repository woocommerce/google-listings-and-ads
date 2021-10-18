/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import CardContent from './card-content';

const GoogleAccount = ( props ) => {
	const { disabled = false } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<CardContent disabled={ disabled } />
			</Section.Card.Body>
		</Section.Card>
	);
};

export default GoogleAccount;
