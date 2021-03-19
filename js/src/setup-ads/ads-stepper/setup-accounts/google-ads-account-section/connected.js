/**
 * Internal dependencies
 */
import TitleButtonLayout from '.~/components/title-button-layout';
import AccountId from '.~/components/account-id';
import Section from '.~/wcdl/section';

const Connected = ( props ) => {
	const { googleAdsAccount } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ <AccountId id={ googleAdsAccount.id } /> }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default Connected;
