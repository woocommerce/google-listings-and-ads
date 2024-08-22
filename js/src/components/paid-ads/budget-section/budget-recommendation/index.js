/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Tip } from '@wordpress/components';
import GridiconNoticeOutline from 'gridicons/dist/notice-outline';

/**
 * Internal dependencies
 */
import './index.scss';

function toRecommendationRange( isMultiple, ...values ) {
	const conversionMap = { strong: <strong />, em: <em />, br: <br /> };
	const template = isMultiple
		? // translators: it's a range of recommended budget amount. 1: the value of the budget, 2: the currency of amount.
		  __(
				'We recommend running campaigns at least 1 month so it can learn to optimize for your business.<br /><em>Tip: Most merchants targeting similar countries <strong>set a daily budget of %1$f %2$s</strong></em>',
				'google-listings-and-ads'
		  )
		: // translators: it's a range of recommended budget amount. 1: the value of the budget, 2: the currency of amount 3: a country name selected by the merchant.
		  __(
				'We recommend running campaigns at least 1 month so it can learn to optimize for your business.<br /><em>Tip: Most merchants targeting <strong>%3$s set a daily budget of %1$f %2$s</strong></em>',
				'google-listings-and-ads'
		  );

	return createInterpolateElement(
		sprintf( template, ...values ),
		conversionMap
	);
}

const BudgetRecommendation = ( props ) => {
	const {
		countryCodes,
		dailyBudget: recommendedBudget,
		country: countryName,
		currency,
		value: currentBudget,
	} = props;

	const recommendationRange = toRecommendationRange(
		countryCodes.length > 1,
		recommendedBudget,
		currency,
		countryName
	);

	const showLowerBudgetNotice = currentBudget < recommendedBudget;

	return (
		<div className="gla-budget-recommendation">
			{ showLowerBudgetNotice && (
				<div className="gla-budget-recommendation__low-budget">
					<GridiconNoticeOutline />
					<div>
						{ __(
							'With a budget lower than your competitor range, your campaign may not get noticeable results.',
							'google-listings-and-ads'
						) }
					</div>
				</div>
			) }
			<Tip>{ recommendationRange }</Tip>
		</div>
	);
};

export default BudgetRecommendation;
