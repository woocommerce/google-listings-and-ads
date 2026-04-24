/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';
import {
	createInterpolateElement,
	useEffect,
	useRef,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import { recordGlaEvent } from '~/utils/tracks';
import './cyo-incentive-picker.scss';

/**
 * Renders the component for picking a "Choose Your Own" incentive for ads campaigns, which allows merchants to select from different ads credit offers based on their expected ad spend.
 *
 * @param {Object} props React props.
 * @param {string} props.context The context in which this component is used, e.g. 'create-ads', 'edit-ads', 'setup-ads', 'setup-mc', or 'setup-ads-only'. This is used for tracking purposes and may also be used to conditionally render content within the component.
 * @return {JSX.Element|null} The rendered component, or null if the incentives are still being resolved or if there are no incentives available.
 */
const CyoIncentivePicker = ( { context } ) => {
	const { getInputProps } = useAdaptiveFormContext();
	const { data: incentives, hasFinishedResolution } = useCYOIncentives();
	const { formatAmount } = useAdsCurrency();
	const {
		value: selectedIncentiveOffer,
		onChange,
		...restInputProps
	} = getInputProps( 'incentiveOffer' );

	const shouldDisplay = hasFinishedResolution && incentives?.length > 0;
	const hasTrackedShownRef = useRef( false );

	useEffect( () => {
		if ( shouldDisplay && ! hasTrackedShownRef.current ) {
			hasTrackedShownRef.current = true;
			recordGlaEvent( 'gla_cyo_incentive_picker_shown', {
				context,
			} );
		}
	}, [ context, shouldDisplay ] );

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
				offer: item.offer,
				spendAmount: item.requirement.spend.requiredAmount.units,
				awardAmount: item.requirement.spend.awardAmount.units,
				radioProps: {
					...restInputProps,
					onChange,
					checked: selectedIncentiveOffer === item.offer,
					value: item.offer,
				},
			} );
		}
		return acc;
	}, [] );

	const handleIncentiveChange = ( offer ) => {
		onChange( offer );

		recordGlaEvent( 'gla_cyo_incentive_selected', {
			context,
			level: offer,
		} );
	};

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
								awardAmount,
								radioProps: {
									selected,
									value,
									...restRadioProps
								},
							} ) => {
								const formattedSpendAmount =
									formatAmount( spendAmount );
								const formattedRewardAmount =
									formatAmount( awardAmount );
								const label = createInterpolateElement(
									sprintf(
										/* translators: %s: amount in users' currency */
										__(
											'Get <strong>%s</strong>',
											'google-listings-and-ads'
										),
										formattedRewardAmount
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
										onChange={ handleIncentiveChange }
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
