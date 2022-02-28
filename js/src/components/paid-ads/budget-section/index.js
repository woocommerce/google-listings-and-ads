/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import getMonthlyMaxEstimated from './getMonthlyMaxEstimated';
import './index.scss';
import BudgetRecommendation from './budget-recommendation';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppInputPriceControl from '.~/components/app-input-price-control';

const nonInteractableProps = {
	noPointerEvents: true,
	readOnly: true,
	tabIndex: -1,
};

/**
 * Renders <Section> and <Section.Card> UI with campaign budget inputs.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 */
const BudgetSection = ( props ) => {
	const {
		formProps: { getInputProps, setValue, values },
		disabled = false,
	} = props;
	const {
		country: [ selectedCountryCode ],
		amount,
	} = values;
	const { googleAdsAccount } = useGoogleAdsAccount();
	const monthlyMaxEstimated = getMonthlyMaxEstimated( amount );
	// Display the currency code that will be used by Google Ads, but still use the store's currency formatting settings.
	const currency = googleAdsAccount?.currency;

	/**
	 * In addition to the initial value setting during initialization, when `disabled` changes
	 * - from false to true, then clear filled amount to `undefined` for showing a blank <input>.
	 * - from true to false, then reset amount to the initial value passed from the consumer side.
	 */
	const initialAmountRef = useRef( amount );
	useEffect( () => {
		const nextAmount = disabled ? undefined : initialAmountRef.current;
		setValue( 'amount', nextAmount );
	}, [ disabled, setValue ] );

	return (
		<div className="gla-budget-section">
			<Section
				disabled={ disabled }
				title={ __( 'Budget', 'google-listings-and-ads' ) }
				description={
					<>
						<p>
							{ __(
								'Enter a daily average cost that works best for your business and the results that you want. You can change your budget or cancel your ad at any time.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							{ __(
								'You will be billed directly by Google Ads.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							{ __(
								'Google will optimize your ads to maximize performance across your selected country(s).',
								'google-listings-and-ads'
							) }
						</p>
					</>
				}
			>
				<Section.Card>
					<Section.Card.Body className="gla-budget-section__card-body">
						<div className="gla-budget-section__card-body__cost">
							<AppInputPriceControl
								label={ __(
									'Daily average cost',
									'google-listings-and-ads'
								) }
								suffix={ currency }
								{ ...getInputProps( 'amount' ) }
								{ ...( disabled && nonInteractableProps ) }
							/>
							<AppInputPriceControl
								disabled
								label={ __(
									'Monthly max, estimated ',
									'google-listings-and-ads'
								) }
								suffix={ currency }
								value={ monthlyMaxEstimated }
							/>
						</div>
						{ selectedCountryCode && (
							<BudgetRecommendation
								countryCode={ selectedCountryCode }
								dailyAverageCost={ amount }
							/>
						) }
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default BudgetSection;
