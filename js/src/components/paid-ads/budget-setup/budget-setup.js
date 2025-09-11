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
import useBudgetMetrics from '~/hooks/useBudgetMetrics';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import AppInputPriceControl from '~/components/app-input-price-control';
import BudgetBadge from './budget-badge';
import BudgetSetupHeader from './budget-setup-header';
import BudgetRadioControl from './budget-radio-control';
import LowBudgetNotice from './low-budget-notice';
import round from '~/utils/round';
import styles from './budget-setup.module.scss';

const i18nLevel = {
	low: __( 'Low', 'google-listings-and-ads' ),
	high: __( 'High', 'google-listings-and-ads' ),
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
	return (
		<div className={ styles.metricsGroup }>
			<span className={ styles.metricsItem }>
				{ metrics ? metrics.conversions : null }
			</span>
			<span className={ styles.metricsItem }>
				{ metrics ? formatAmount( metrics.conversionsValue ) : null }
				{ Number( metrics?.uplift ) !== 0 && (
					<BudgetBadge amount={ metrics.uplift } />
				) }
			</span>
			<span className={ styles.metricsItem }>
				{ metrics ? formatAmount( metrics.cost ) : null }
			</span>
		</div>
	);
}

/**
 * Renders a UI for selecting a campaign budget from recommendations or
 * entering a custom campaign budget.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.hideRecommendations=false]
 */
export default function BudgetSetup( { hideRecommendations = false } ) {
	const formContext = useAdaptiveFormContext();
	const { adapter, getInputProps, values } = formContext;
	const { countryCodes, budgetRecommendation } = adapter;
	const { amount } = values;
	const { adsCurrencyConfig, formatAmount } = useAdsCurrency();

	const [ budget, setBudget ] = useState( amount );
	const debouncedSetBudget = useDebounce( setBudget, 1000 );
	const { data } = useBudgetMetrics( countryCodes, budget );

	useEffect( () => {
		debouncedSetBudget( amount );
	}, [ debouncedSetBudget, amount ] );

	const options = [ 'high', 'recommended', 'low' ].reduce( ( acc, level ) => {
		const item = hideRecommendations
			? null
			: budgetRecommendation?.[ level ];

		if ( item ) {
			const dailyBudget = formatAmount( item.dailyBudget );

			acc.push( {
				level,
				metrics: item.metrics,
				radioProps: {
					...getInputProps( 'level' ),
					value: level,
					label: (
						<>
							{ dailyBudget }
							<span className={ styles.dayUnit }>
								/{ __( 'day', 'google-listings-and-ads' ) }
							</span>
						</>
					),
				},
			} );
		}
		return acc;
	}, [] );

	const { help, ...amountInputProps } = getInputProps( 'amount' );
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
					<div key={ level } className={ getRowClassName( level ) }>
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

			<div className={ getRowClassName( 'custom' ) }>
				<BudgetRadioControl
					{ ...getInputProps( 'level' ) }
					value="custom"
					label={ __(
						'Set custom budget',
						'google-listings-and-ads'
					) }
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
