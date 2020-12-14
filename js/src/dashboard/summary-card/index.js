/**
 * External dependencies
 */
import { SummaryList } from '@woocommerce/components';
import {
	Card,
	CardHeader,
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const SummaryCard = ( props ) => {
	const { title, children } = props;

	return (
		<Card className="summary-card">
			<CardHeader size="medium">
				<Text variant="title.small">{ title }</Text>
			</CardHeader>
			<SummaryList>
				{ () => {
					return children;
				} }
			</SummaryList>
		</Card>
	);
};

export default SummaryCard;
