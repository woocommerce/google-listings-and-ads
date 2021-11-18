/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import TitleButtonLayout from '.~/components/title-button-layout';
import Section from '.~/wcdl/section';

const Connected = ( props ) => {
	const { googleAdsAccount } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ toAccountText( googleAdsAccount.id ) }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default Connected;
