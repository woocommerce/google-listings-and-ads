/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './account-detail-row.scss';

/**
 * Renders a bordered row in an account card's detail section: a label followed by its value, with
 * an optional indicator, such as a status badge, on the right.
 *
 * @param {Object} props Component props.
 * @param {string} props.label The row's label.
 * @param {JSX.Element|string} props.children The value shown after the label.
 * @param {JSX.Element} [props.indicator] Content shown on the right of the row.
 * @return {JSX.Element} The row.
 */
const AccountDetailRow = ( { label, children, indicator } ) => {
	return (
		<Flex className="gla-account-detail-row" align="center" gap={ 2 }>
			<FlexBlock className="gla-account-detail-row__text">
				<span className="gla-account-detail-row__label">{ label }</span>
				{ children }
			</FlexBlock>
			{ indicator && (
				<FlexItem className="gla-account-detail-row__indicator">
					{ indicator }
				</FlexItem>
			) }
		</Flex>
	);
};

export default AccountDetailRow;
