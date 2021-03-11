/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import './index.scss';

const BudgetRecommendation = ( props ) => {
	const { recommendation, showLowerBudgetNotice } = props;
	const {
		country_code: countryCode,
		daily_budget_low: dailyBudgetLow,
		daily_budget_high: dailyBudgetHigh,
		currency,
	} = recommendation;
	const map = useCountryKeyNameMap();

	return (
		<div className="gla-budget-recommendation">
			<div className="gla-budget-recommendation__recommendation">
				<GridiconInfoOutline />
				<div>
					{ createInterpolateElement(
						__(
							'Most merchants targeting <countryname /> set a daily budget of <low /> to <high /> <currency /> for approximately 10 conversions a week.',
							'google-listings-and-ads'
						),
						{
							countryname: (
								<strong>{ map[ countryCode ] }</strong>
							),
							low: <strong>{ dailyBudgetLow }</strong>,
							high: <strong>{ dailyBudgetHigh }</strong>,
							currency: <strong>{ currency }</strong>,
						}
					) }
				</div>
			</div>
			{ showLowerBudgetNotice && (
				<div className="gla-budget-recommendation__low-budget">
					<GridiconInfoOutline />
					<div>
						{ __(
							'With a budget lower than your competitor range, your campaign may not get noticeable results.',
							'google-listings-and-ads'
						) }
					</div>
				</div>
			) }
		</div>
	);
};

export default BudgetRecommendation;
