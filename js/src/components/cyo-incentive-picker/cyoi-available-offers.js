/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CYOIRadioControl from './cyoi-radio-control';
import styles from './cyo-incentive-picker.module.scss';
import useAdsCurrency from '~/hooks/useAdsCurrency';

const CYOIAvailableOffers = ( { incentives } ) => {
	const { formatAmount } = useAdsCurrency();
	return incentives.map( ( incentive ) => {
		const { id, offer, requirement } = incentive;

		const rewardAmount = requirement.spend.awardAmount.units;
		const spendAmount = requirement.spend.requiredAmount.units;

		return (
			<div key={ id } className={ styles.row }>
				<CYOIRadioControl
					label={ rewardAmount }
					offer={ offer }
					requirement={ requirement }
					value={ offer }
				/>
				<div className={ styles.option }>
					{ sprintf(
						/* translators: %s: amount in dollars */
						__(
							'Spend %s with Google Ads in the first 60 days to unlock the credit.',
							'google-listings-and-ads'
						),
						formatAmount( spendAmount )
					) }
				</div>
				<div className={ styles.helper }>
					<span>
						{ __( 'in Ads credit', 'google-listings-and-ads' ) }
					</span>
				</div>
			</div>
		);
	} );
};

export default CYOIAvailableOffers;
