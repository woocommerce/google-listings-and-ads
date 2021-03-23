/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppInputControl from '.~/components/app-input-control';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import getMonthlyMaxEstimated from '../getMonthlyMaxEstimated';
import './index.scss';
import FreeAdCredit from './free-ad-credit';
import BudgetRecommendation from './budget-recommendation';
import useFreeAdCredit from '.~/hooks/useFreeAdCredit';

const BudgetSection = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;
	const {
		country: [ selectedCountryCode ],
		amount,
	} = values;
	const { code: currencyCode } = useStoreCurrency();
	const hasFreeAdCredit = useFreeAdCredit();

	const monthlyMaxEstimated = getMonthlyMaxEstimated( values.amount );

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
					<Section.Card.Body className="gla-budget-section__card-body">
						<div className="gla-budget-section__card-body__cost">
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
						{ selectedCountryCode && (
							<BudgetRecommendation
								countryCode={ selectedCountryCode }
								dailyAverageCost={ amount }
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
