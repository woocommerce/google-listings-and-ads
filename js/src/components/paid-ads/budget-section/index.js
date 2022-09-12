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
	const { countryCodes, amount } = values;
	const { googleAdsAccount } = useGoogleAdsAccount();
	const monthlyMaxEstimated = getMonthlyMaxEstimated( amount );
	// Display the currency code that will be used by Google Ads, but still use the store's currency formatting settings.
	const currency = googleAdsAccount?.currency;

	// Wrapping `useRef` is because since WC 6.9, the reference of `setValue` may be changed
	// after calling itself and further leads to an infinite re-rendering loop if used in a
	// `useEffect`.
	const setValueRef = useRef();
	setValueRef.current = setValue;

	/**
	 * In addition to the initial value setting during initialization, when `disabled` changes
	 * - from false to true, then clear filled amount to `undefined` for showing a blank <input>.
	 * - from true to false, then reset amount to the initial value passed from the consumer side.
	 */
	const initialAmountRef = useRef( amount );
	useEffect( () => {
		const nextAmount = disabled ? undefined : initialAmountRef.current;
		setValueRef.current( 'amount', nextAmount );
	}, [ disabled ] );

	return (
		<div className="gla-budget-section">
			<Section
				disabled={ disabled }
				title={ __( 'Set your budget', 'google-listings-and-ads' ) }
				description={ __(
					'With Performance Max campaigns, you can set your own budget and Google’s Smart Bidding technology will serve the most appropriate ad, with the optimal bid, to maximize campaign performance.',
					'google-listings-and-ads'
				) }
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
						{ countryCodes.length > 0 && (
							<BudgetRecommendation
								countryCodes={ countryCodes }
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
