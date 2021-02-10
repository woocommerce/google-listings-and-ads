/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '../title-button-layout';

const ConnectedCard = ( props ) => {
	const { googleMCAccount } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout title={ googleMCAccount.id } />
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectedCard;
