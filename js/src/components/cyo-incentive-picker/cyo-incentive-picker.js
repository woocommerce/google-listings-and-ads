/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import CYOIRadioControl from './cyoi-radio-control';
import styles from './cyo-incentive-picker.module.scss';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import './index.scss';

const CyoIncentivePicker = () => {
	const { data: incentives, hasFinishedResolution } = useCYOIncentives();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { formatAmount } = useAdsCurrency();

	const shouldDisplay =
		hasFinishedResolution &&
		incentives?.length > 0 &&
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	if ( ! shouldDisplay ) {
		return null;
	}

	return (
		<div className="gla-cyoi-section">
			<Section
				verticalGap={ 4 }
				title={ __( 'Ads credit offer', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'New advertisers are eligible for free Google Ads credits.',
							'google-listings-and-ads'
						) }
					</p>
				}
			>
				<Section.Card>
					<Section.Card.Body className="gla-cyoi-section__card-body">
						<div>
							<Subsection.Title>
								{ __(
									'Choose a sign-up offer to jumpstart your first campaign',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<Subsection.Subtitle>
								{ __(
									'Select an offer that fits your monthly budget. New advertisers will receive an ad credit after meeting the minimum spend requirement for the selected offer.',
									'google-listings-and-ads'
								) }
							</Subsection.Subtitle>
						</div>
						<div className={ styles.container }>
							{ incentives.map( ( incentive ) => {
								const { id, offer, requirement } = incentive;
								const rewardAmount =
									requirement.spend.awardAmount.units;
								const spendAmount =
									requirement.spend.requiredAmount.units;

								return (
									<div key={ id } className={ styles.row }>
										<CYOIRadioControl
											label={ rewardAmount }
											offer={ offer }
											requirement={ requirement }
											value={ id }
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
												{ __(
													'in Ads credit',
													'google-listings-and-ads'
												) }
											</span>
										</div>
									</div>
								);
							} ) }
						</div>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default CyoIncentivePicker;
