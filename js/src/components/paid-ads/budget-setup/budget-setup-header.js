/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Tooltip } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import styles from './budget-setup.module.scss';

/**
 * Renders the header for the budget setup component.
 */
export default function BudgetSetupHeader() {
	return (
		<div className={ classnames( styles.header, styles.metricsGroup ) }>
			<span className={ styles.metricsItem }>
				{ __( 'Weekly conversions', 'google-listings-and-ads' ) }
				<Tooltip
					className={ styles.tooltip }
					text={
						<>
							{ __(
								'The estimated total value of all the conversions (sales volume) your campaign will generate in a week.',
								'google-listings-and-ads'
							) }
							<Link
								type="external"
								target="_blank"
								href="https://support.google.com/google-ads/answer/7197048"
							>
								{ __(
									'Learn more.',
									'google-listings-and-ads'
								) }
							</Link>
						</>
					}
				>
					<GridiconInfoOutline size={ 16 } />
				</Tooltip>
			</span>
			<span className={ styles.metricsItem }>
				{ __( 'Weekly conv. value', 'google-listings-and-ads' ) }
				<Tooltip
					className={ styles.tooltip }
					text={ __(
						'The estimated number of conversions (unit sales) for a typical week. This number may vary based on change in your weekly spend.',
						'google-listings-and-ads'
					) }
				>
					<GridiconInfoOutline size={ 16 } />
				</Tooltip>
			</span>
			<span className={ styles.metricsItem }>
				{ __( 'Weekly cost', 'google-listings-and-ads' ) }
				<Tooltip
					className={ styles.tooltip }
					text={ __(
						`This is the estimated average amount you'll spend weekly during the month. Some weeks you may spend less than this amount or up to 2 times this amount.`,
						'google-listings-and-ads'
					) }
				>
					<GridiconInfoOutline size={ 16 } />
				</Tooltip>
			</span>
		</div>
	);
}
