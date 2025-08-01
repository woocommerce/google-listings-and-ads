/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Flex, FlexBlock } from '@wordpress/components';
import GridiconNoticeOutline from 'gridicons/dist/notice-outline';

/**
 * Renders a notice that the budget is lower than the recommended amount.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional class name.
 * @param {string} props.recommendedAmount The recommended amount in currency format.
 */
export default function LowBudgetNotice( { className, recommendedAmount } ) {
	return (
		<Flex className={ className } gap={ 3 }>
			<GridiconNoticeOutline size={ 24 } />
			<FlexBlock>
				{ createInterpolateElement(
					__(
						`Your budget is lower than other advertisers' budgets, which may affect performance. For best results, we recommend at least <amount /> per day.`,
						'google-listings-and-ads'
					),
					{
						amount: <strong>{ recommendedAmount }</strong>,
					}
				) }
			</FlexBlock>
		</Flex>
	);
}
