/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { recordGlaEvent } from '~/utils/tracks';
import useBudgetMetrics from '~/hooks/useBudgetMetrics';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import AppInputPriceControl from '~/components/app-input-price-control';
import BudgetBadge from './budget-badge';
import BudgetSetupHeader from './budget-setup-header';
import BudgetRadioControl from './budget-radio-control';
import DailyBudgetLabel from './daily-budget-label';
import LowBudgetNotice from './low-budget-notice';
import round from '~/utils/round';
import styles from './budget-setup.module.scss';

const i18nLevel = {
	low: __( 'Low', 'google-listings-and-ads' ),
	high: __( 'High', 'google-listings-and-ads' ),
	current: __( 'Current', 'google-listings-and-ads' ),
	recommended: __( 'Recommended', 'google-listings-and-ads' ),
};

function isBelowLowRecommendation( amount, recommendation, precision ) {
	if (
		! recommendation?.low?.dailyBudget ||
		! Number.isInteger( precision )
	) {
		return false;
	}

	return amount < round( recommendation.low.dailyBudget, precision );
}

function BudgetMetrics( { formatAmount, metrics } ) {
	const renderUplift = metrics?.uplift && Number( metrics?.uplift ) !== 0;

	return (
		<div className={ styles.metricsGroup }>
			<span className={ styles.metricsItem }>
				{ metrics ? metrics.conversions : null }
			</span>
			<span className={ styles.metricsItem }>
				{ metrics ? formatAmount( metrics.conversionsValue ) : null }
				{ !! renderUplift && <BudgetBadge amount={ metrics.uplift } /> }
			</span>
			<span className={ styles.metricsItem }>
				{ metrics ? formatAmount( metrics.cost ) : null }
			</span>
		</div>
	);
}

/**
 * @event gla_raise_budget_recommendation_option_selected
 * @property {string} context The context where the event is fired. Value: `budget-setup`.
 * @property {string} level The budget recommendation level selected by the user. Possible values: `low`, `recommended`, or `high`.
 * @property {string} budget The daily budget amount selected by the user.
 */

/**
 * Renders a UI for selecting a campaign budget from recommendations or
 * entering a custom campaign budget.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @fires gla_raise_budget_recommendation_option_selected when a budget recommendation option with uplift is selected.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.hideRecommendations=false]
 */
export default function BudgetSetup( { hideRecommendations = false } ) {
	const formContext = useAdaptiveFormContext();
	const { adapter, getInputProps, values } = formContext;
	const { countryCodes, budgetRecommendation } = adapter;
	const { amount, currentAmount } = values;
	const { adsCurrencyConfig, formatAmount } = useAdsCurrency();

	const [ budget, setBudget ] = useState( amount );
	const debouncedSetBudget = useDebounce( setBudget, 1000 );
	const { data: currentMetrics } = useBudgetMetrics(
		countryCodes,
		currentAmount
	);
	const { data } = useBudgetMetrics( countryCodes, budget );

	useEffect( () => {
		debouncedSetBudget( amount );
	}, [ debouncedSetBudget, amount ] );

	const { help, ...amountInputProps } = getInputProps( 'amount' );

	// The current level metrics from raise budget recommendations.
	const raiseBudgetCurrentLevelMetrics =
		budgetRecommendation?.current?.metrics;

	const options = [ 'high', 'recommended', 'low' ].reduce( ( acc, level ) => {
		const item = hideRecommendations
			? null
			: budgetRecommendation?.[ level ];

		if ( item ) {
			const dailyBudget = formatAmount( item.dailyBudget );
			const { onChange, ...restInputProps } = getInputProps( 'level' );

			acc.push( {
				level,
				metrics: item.metrics,
				radioProps: {
					...restInputProps,
					onChange: ( value ) => {
						if (
							item.metrics?.uplift &&
							Number( item.metrics?.uplift ) !== 0
						) {
							recordGlaEvent(
								'gla_raise_budget_recommendation_option_selected',
								{
									context: 'budget-setup',
									level: value,
									budget: dailyBudget,
								}
							);
						}

						onChange( value );
					},
					value: level,
					label: <DailyBudgetLabel amount={ dailyBudget } />,
				},
			} );
		}
		return acc;
	}, [] );

	const shouldNoticeRecommendedBudget =
		! help &&
		! hideRecommendations &&
		budgetRecommendation?.recommended &&
		isBelowLowRecommendation(
			amount,
			budgetRecommendation,
			adsCurrencyConfig.precision
		);

	const getRowClassName = ( level ) => {
		const selected = level === values.level;
		return classnames(
			styles.row,
			level === 'custom' && styles.customRow,
			hideRecommendations && styles.hideRecommendations,
			selected && styles.rowSelected
		);
	};

	return (
		<div className={ styles.container }>
			<BudgetSetupHeader />

			{ options.map( ( { level, radioProps, metrics } ) => {
				const helperContentClassName =
					level === 'recommended'
						? styles.highlightRecommended
						: null;

				return (
					<div className={ getRowClassName( level ) } key={ level }>
						<BudgetRadioControl { ...radioProps } />
						<BudgetMetrics
							formatAmount={ formatAmount }
							metrics={ metrics }
						/>
						<div className={ styles.helper }>
							<span className={ helperContentClassName }>
								{ i18nLevel[ level ] }
							</span>
						</div>
					</div>
				);
			} ) }

			{ currentAmount && (
				<div className={ getRowClassName( 'current' ) }>
					<BudgetRadioControl
						{ ...getInputProps( 'level' ) }
						label={
							<DailyBudgetLabel
								amount={ formatAmount( currentAmount ) }
							/>
						}
						value="current"
					/>
					<BudgetMetrics
						formatAmount={ formatAmount }
						metrics={
							raiseBudgetCurrentLevelMetrics ||
							currentMetrics?.metrics
						}
					/>
					<div className={ styles.helper }>
						<span>
							{ __( 'Current', 'google-listings-and-ads' ) }
						</span>
					</div>
				</div>
			) }

			<div className={ getRowClassName( 'custom' ) }>
				<BudgetRadioControl
					{ ...getInputProps( 'level' ) }
					label={ __(
						'Set custom budget',
						'google-listings-and-ads'
					) }
					value="custom"
				/>
				{ values.level === 'custom' && (
					<>
						<AppInputPriceControl
							suffix={ data?.currency }
							{ ...amountInputProps }
							className={ classnames(
								amountInputProps.className,
								styles.customInput
							) }
						/>
						<BudgetMetrics
							formatAmount={ formatAmount }
							metrics={ data?.metrics }
						/>
						{ help && (
							<div className={ styles.customHelp } role="alert">
								{ help }
							</div>
						) }

						{ shouldNoticeRecommendedBudget && (
							<LowBudgetNotice
								className={ styles.customNotice }
								recommendedAmount={ formatAmount(
									budgetRecommendation.recommended.dailyBudget
								) }
							/>
						) }
					</>
				) }
			</div>
		</div>
	);
}
