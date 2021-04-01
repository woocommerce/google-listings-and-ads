/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import Section from '.~/wcdl/section';
import TitleButtonLayout from '.~/components/title-button-layout';

const ConnectedCard = ( props ) => {
	const { googleMCAccount } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ toAccountText( googleMCAccount.id ) }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectedCard;
