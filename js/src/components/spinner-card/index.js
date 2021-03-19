/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Section from '.~/wcdl/section';

const SpinnerCard = () => {
	return (
		<Section.Card>
			<Section.Card.Body>
				<AppSpinner />
			</Section.Card.Body>
		</Section.Card>
	);
};

export default SpinnerCard;
