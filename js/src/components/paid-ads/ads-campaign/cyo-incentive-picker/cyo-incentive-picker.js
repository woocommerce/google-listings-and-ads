/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import './cyo-incentive-picker.scss';

const CyoIncentivePicker = () => {
	const { getInputProps } = useAdaptiveFormContext();
	const { data: incentives, hasFinishedResolution } = useCYOIncentives();
	const { formatAmount } = useAdsCurrency();

	const shouldDisplay = hasFinishedResolution && incentives?.length > 0;

	const { value: selectedIncentiveId, ...restInputProps } =
		getInputProps( 'incentiveId' );

	if ( ! shouldDisplay ) {
		return null;
	}

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
					checked: selectedIncentiveId === item.id,
					value: item.id,
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
						{ options.map(
							( {
								id,
								spendAmount,
								radioProps: {
									selected,
									value,
									...restRadioProps
								},
							} ) => {
								const formattedSpendAmount =
									formatAmount( spendAmount );
								const label = createInterpolateElement(
									sprintf(
										/* translators: %s: amount in users' currency */
										__(
											'Get <strong>%s</strong>',
											'google-listings-and-ads'
										),
										formattedSpendAmount
									),
									{
										strong: <strong />,
									}
								);

								return (
									<RadioControl
										{ ...restRadioProps }
										key={ id }
										className="gla-cyoi-radio-control__radio-control"
										options={ [ { value, label } ] }
										help={ sprintf(
											/* translators: %s: amount in users' currency */
											__(
												'Spend %s with Google Ads in the first 60 days to unlock the credit.',
												'google-listings-and-ads'
											),
											formattedSpendAmount
										) }
										hideLabelFromVision
									/>
								);
							}
						) }
					</div>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default CyoIncentivePicker;
