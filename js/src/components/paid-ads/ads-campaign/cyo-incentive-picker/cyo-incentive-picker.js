/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __, sprintf } from '@wordpress/i18n';
import { RadioControl, Notice, Flex, FlexItem } from '@wordpress/components';
import { createInterpolateElement, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppButton from '~/components/app-button';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import AppDocumentationLink from '~/components/app-documentation-link';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import { recordGlaEvent } from '~/utils/tracks';
import './cyo-incentive-picker.scss';

/**
 * Renders the component for picking a "Choose Your Own" incentive for ads campaigns, which allows merchants to select from different ads credit offers based on their expected ad spend.
 *
 * @param {Object}   props React props.
 * @param {string}   props.context The context in which this component is used, e.g. 'create-ads', 'edit-ads', 'setup-ads', 'setup-mc', or 'setup-ads-only'. This is used for tracking purposes and may also be used to conditionally render content within the component.
 * @param {Object}   props.incentiveResult The result of applying a CYO incentive. This is used to determine whether to show an error message after applying an incentive.
 * @param {Function} props.onRetry Callback to retry applying the incentive.
 * @return {JSX.Element|null} The rendered component, or null if the incentives are still being resolved or if there are no incentives available.
 */
const CyoIncentivePicker = ( { context, incentiveResult, onRetry = noop } ) => {
	const { getInputProps } = useAdaptiveFormContext();
	const { data: incentives, hasFinishedResolution } = useCYOIncentives();
	const { formatAmount } = useAdsCurrency();
	const isServiceBasedMerchant = useServiceBasedMerchant();

	const shouldDisplay = hasFinishedResolution && incentives?.length > 0;

	const { value: selectedIncentiveId, ...restInputProps } =
		getInputProps( 'incentiveId' );

	useEffect( () => {
		if ( shouldDisplay ) {
			recordGlaEvent( 'gla_cyo_incentive_picker_shown', {
				context,
				is_service_based_merchant: isServiceBasedMerchant,
			} );
		}
	}, [ context, shouldDisplay, isServiceBasedMerchant ] );

	if ( ! shouldDisplay ) {
		return null;
	}

	const handleOnRetryClick = () => {
		onRetry( selectedIncentiveId );
	};

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
					checked: selectedIncentiveId === item.id,
					value: item.id,
				},
			} );
		}
		return acc;
	}, [] );

	const handleIncentiveChange = ( incentiveId ) => {
		restInputProps.onChange( incentiveId );

		const selectedOption = options.find(
			( option ) => String( option.id ) === String( incentiveId )
		);

		if ( selectedOption ) {
			recordGlaEvent( 'gla_cyo_incentive_selected', {
				context,
				is_service_based_merchant: isServiceBasedMerchant,
				level: selectedOption.offer,
			} );
		}
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

					{ incentiveResult?.error && (
						<Notice status="error" isDismissible={ false }>
							<p>
								{ incentiveResult.error.message ||
									__(
										'There was an issue applying the selected offer. Please try again.',
										'google-listings-and-ads'
									) }
							</p>

							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem>
									<AppButton
										onClick={ handleOnRetryClick }
										loading={ incentiveResult.loading }
										eventName="gla_cyoi_apply_incentive_retry_click"
										eventProps={ { context } }
										isPrimary
									>
										{ __(
											'Try again',
											'google-listings-and-ads'
										) }
									</AppButton>
								</FlexItem>

								<FlexItem>
									<AppDocumentationLink
										href="https://ads.google.com/aw/overview"
										linkId="apply-in-google-ads"
										context={ context }
									>
										{ __(
											'Apply in Google Ads',
											'google-listings-and-ads'
										) }
									</AppDocumentationLink>
								</FlexItem>
							</Flex>
						</Notice>
					) }
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default CyoIncentivePicker;
