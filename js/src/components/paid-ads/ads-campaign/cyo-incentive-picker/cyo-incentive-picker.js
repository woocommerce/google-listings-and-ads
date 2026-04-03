/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import CYOIRadioControl from './cyoi-radio-control';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import './cyo-incentive-picker.scss';

const CyoIncentivePicker = () => {
	const { getInputProps } = useAdaptiveFormContext();
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

	const { value: selectedIncentiveId, ...restInputProps } =
		getInputProps( 'incentiveId' );

	const defaultIncentiveId =
		incentives.find( ( incentive ) => incentive.offer === 'medium' )?.id ||
		incentives[ 0 ].id;

	const options = [ 'low', 'medium', 'high' ].reduce( ( acc, offer ) => {
		const item = incentives.find(
			( incentive ) => incentive.offer === offer
		);

		if ( item ) {
			acc.push( {
				id: item.id,
				spendAmount: item.requirement.spend.requiredAmount.units,
				radioProps: {
					...restInputProps,
					selected: selectedIncentiveId ?? defaultIncentiveId,
					value: item.id,
					label: formatAmount(
						item.requirement.spend.awardAmount.units
					),
				},
			} );
		}
		return acc;
	}, [] );

	return (
		<Section
			className="gla-cyoi-section"
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
					<div className="gla-cyoi-incentive-picker__container">
						{ options.map( ( { id, spendAmount, radioProps } ) => {
							return (
								<div
									key={ id }
									className="gla-cyoi-incentive-picker__row"
								>
									<CYOIRadioControl { ...radioProps } />
									<div className="gla-cyoi-incentive-picker__option">
										{ sprintf(
											/* translators: %s: amount in users' currency */
											__(
												'Spend %s with Google Ads in the first 60 days to unlock the credit.',
												'google-listings-and-ads'
											),
											formatAmount( spendAmount )
										) }
									</div>
									<div className="gla-cyoi-incentive-picker__helper">
										{ __(
											'in Ads credit',
											'google-listings-and-ads'
										) }
									</div>
								</div>
							);
						} ) }
					</div>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default CyoIncentivePicker;
