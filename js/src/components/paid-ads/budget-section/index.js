/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import getMonthlyMaxEstimated from './getMonthlyMaxEstimated';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import AppInputPriceControl from '~/components/app-input-price-control';
import './index.scss';

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
 * @param {JSX.Element} [props.children] Extra content to be rendered under the card of budget inputs.
 */
const BudgetSection = ( { formProps, disabled = false, children } ) => {
	const { getInputProps, values } = formProps;
	const { amount } = values;
	const { googleAdsAccount } = useGoogleAdsAccount();
	const monthlyMaxEstimated = getMonthlyMaxEstimated( amount );
	// Display the currency code that will be used by Google Ads, but still use the store's currency formatting settings.
	const currency = googleAdsAccount?.currency;

	return (
		<div className="gla-budget-section">
			<Section
				verticalGap={ 4 }
				disabled={ disabled }
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
								{ ...( disabled && nonInteractableProps ) }
							/>
							<AppInputPriceControl
								disabled
								label={ __(
									'Monthly max, estimated',
									'google-listings-and-ads'
								) }
								suffix={ currency }
								value={ monthlyMaxEstimated }
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
