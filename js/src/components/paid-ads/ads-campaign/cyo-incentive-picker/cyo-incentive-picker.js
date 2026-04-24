/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __, sprintf } from '@wordpress/i18n';
import { RadioControl, Notice, Flex, FlexItem } from '@wordpress/components';
import {
	createInterpolateElement,
	useEffect,
	useRef,
} from '@wordpress/element';

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
import { recordGlaEvent } from '~/utils/tracks';
import './cyo-incentive-picker.scss';

/**
 * @event gla_cyo_incentive_picker_shown
 * @description Fired when the CYO incentive picker is shown to the user.
 * @param {string} context - The context in which the incentive picker is shown, e.g. 'create-ads', 'edit-ads', 'setup-ads', 'setup-mc', or 'setup-ads-only'.
 */

/**
 * @event gla_cyo_incentive_selected
 * @description Fired when the user selects an incentive offer.
 * @param {string} context - The context in which the incentive offer is selected.
 * @param {string} level - The level of the selected incentive offer, e.g. 'low', 'medium', or 'high'.
 */

/**
 * @event gla_cyo_incentive_apply_incentive_retry_click
 * @description Fired when the user clicks the retry button after a failed attempt to apply an incentive.
 * @param {string} context - The context in which the retry action is taken.
 */

/**
 * Renders the component for picking a "Choose Your Own" incentive for ads campaigns, which allows merchants to select from different ads credit offers based on their expected ad spend.
 *
 * @fires gla_cyo_incentive_picker_shown when the incentive picker is shown to the user.
 * @fires gla_cyo_incentive_selected when the user selects an incentive offer.
 * @fires gla_cyo_incentive_apply_incentive_retry_click when the user clicks the retry button after a failed attempt to apply an incentive.
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
	const {
		value: selectedIncentiveOffer,
		checked, // we don't actually need this prop for radio control, but we need to destructure it here to avoid passing it down to RadioControl which will cause a warning since RadioControl doesn't expect a checked prop
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

	const handleOnRetryClick = () => {
		onRetry( selectedIncentiveOffer );
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
				radioProps: restInputProps,
			} );
		}
		return acc;
	}, [] );

	const handleIncentiveChange = ( offer ) => {
		recordGlaEvent( 'gla_cyo_incentive_selected', {
			context,
			level: offer,
		} );

		onChange( offer );
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
								offer,
								radioProps,
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
										{ ...radioProps }
										key={ id }
										className="gla-cyoi-radio-control__radio-control"
										options={ [ { label, value: offer } ] }
										help={ sprintf(
											/* translators: %s: amount in users' currency */
											__(
												'Spend %s with Google Ads in the first 60 days to unlock the credit.',
												'google-listings-and-ads'
											),
											formattedSpendAmount
										) }
										onChange={ handleIncentiveChange }
										selected={ selectedIncentiveOffer }
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
