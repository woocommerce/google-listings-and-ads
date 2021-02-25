/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '../title-button-layout';
import AccountId from './account-id';

const ConnectedCard = ( props ) => {
	const { googleMCAccount } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ <AccountId id={ googleMCAccount.id } /> }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectedCard;
