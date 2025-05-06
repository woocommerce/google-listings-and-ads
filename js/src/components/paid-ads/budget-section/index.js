/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tip } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useBudgetMetrics from '~/hooks/useBudgetMetrics';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import AppInputPriceControl from '~/components/app-input-price-control';
import AppInputControl from '~/components/app-input-control';
import './index.scss';

/**
 * Renders a UI for setting up the campaign budget.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} [props.children] Extra content to be rendered under the card of budget inputs.
 */
const BudgetSection = ( { children } ) => {
	const formContext = useAdaptiveFormContext();
	const { adapter, getInputProps, values } = formContext;
	const { countryCodes } = adapter;
	const { amount } = values;
	const { googleAdsAccount } = useGoogleAdsAccount();

	const [ budget, setBudget ] = useState( amount );
	const debouncedSetBudget = useDebounce( setBudget, 1000 );
	const { data } = useBudgetMetrics( countryCodes, budget );

	useEffect( () => {
		debouncedSetBudget( amount );
	}, [ debouncedSetBudget, amount ] );

	// Display the currency code that will be used by Google Ads, but still use the store's currency formatting settings.
	const currency = googleAdsAccount?.currency;
	const weeklyCost = data ? data.metrics.cost : null;

	return (
		<div className="gla-budget-section">
			<Section
				verticalGap={ 4 }
				title={ __( 'Set your budget', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'With Performance Max campaigns, you can set your own budget and Google’s Smart Bidding technology will serve the most appropriate ad, with the optimal bid, to maximize campaign performance. You only pay when people click on your ads, and you can start or stop your campaign whenever you want.',
							'google-listings-and-ads'
						) }
					</p>
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
							/>
							<AppInputControl
								disabled
								label={ __(
									'Weekly cost',
									'google-listings-and-ads'
								) }
								value={ weeklyCost }
							/>
						</div>
						<Tip>
							{ __(
								'We recommend running campaigns at least 1 month so it can learn to optimize for your business.',
								'google-listings-and-ads'
							) }
						</Tip>
					</Section.Card.Body>
				</Section.Card>
				{ children }
			</Section>
		</div>
	);
};

export default BudgetSection;
