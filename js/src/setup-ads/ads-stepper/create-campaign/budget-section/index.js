/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppInputControl from '.~/components/app-input-control';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import getMonthlyMaxEstimated from '../getMonthlyMaxEstimated';
import './index.scss';
import FreeAdCredit from './free-ad-credit';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import BudgetRecommendation from './budget-recommendation';

const BudgetSection = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;
	const {
		country: [ selectedCountryCode ],
		amount,
	} = values;
	const { code: currencyCode } = useStoreCurrency();

	const options = useMemo( () => {
		return {
			path: `/wc/gla/ads/campaigns/budget-recommendation/${ selectedCountryCode }`,
		};
	}, [ selectedCountryCode ] );

	const [ fetchBudgetRecommendation, { data } ] = useApiFetchCallback(
		options
	);

	useEffect( () => {
		if ( ! selectedCountryCode ) {
			return;
		}

		fetchBudgetRecommendation();
	}, [ fetchBudgetRecommendation, selectedCountryCode ] );

	const monthlyMaxEstimated = getMonthlyMaxEstimated( values.amount );

	// TODO: free ad credit is only applicable for new Google Ads account.
	const hasFreeAdCredit = true;

	return (
		<div className="gla-budget-section">
			<Section
				title={ __( 'Budget', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'Enter a daily average cost that works best for your business and the results that you want. You can change your budget or cancel your ad at any time.',
							'google-listings-and-ads'
						) }
					</p>
				}
			>
				<Section.Card>
					<Section.Card.Body>
						<div className="gla-budget-section__cost">
							<AppInputControl
								label={ __(
									'Daily average cost',
									'google-listings-and-ads'
								) }
								suffix={ currencyCode }
								{ ...getInputProps( 'amount' ) }
							/>
							<AppInputControl
								disabled
								label={ __(
									'Monthly max, estimated ',
									'google-listings-and-ads'
								) }
								suffix={ currencyCode }
								value={ monthlyMaxEstimated }
							/>
						</div>
						{ data && (
							<BudgetRecommendation
								recommendation={ data }
								showLowerBudgetNotice={
									amount !== '' &&
									Number( amount ) <
										Number( data.daily_budget_low )
								}
							/>
						) }
						{ hasFreeAdCredit && <FreeAdCredit /> }
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default BudgetSection;
