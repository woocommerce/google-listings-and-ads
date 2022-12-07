/**
 * External dependencies
 */
import { Card, CardHeader } from '@wordpress/components';
/**
 * Internal dependencies
 */
import './summary-card.scss';
import Text from '.~/components/app-text';

/**
 * Returns a Card with the given content.
 *
 * @param {Object} props React props
 * @param {string} props.title Card titile.
 * @param {Array<JSX.Element>} props.children Children to be rendered as cards body.
 *
 * @return {Card} Card with title and content.
 */
const SummaryCard = ( { title, children } ) => {
	return (
		<Card className="gla-summary-card">
			<CardHeader size="medium">
				<Text variant="title-small">{ title }</Text>
			</CardHeader>
			{ children }
		</Card>
	);
};

export default SummaryCard;
