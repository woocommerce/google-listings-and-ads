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
import AppDocumentationLink from '~/components/app-documentation-link';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import { recordGlaEvent } from '~/utils/tracks';
import './cyo-incentive-picker.scss';

/**
 * Fired when the CYO incentive picker is shown to the user.
 *
 * @event gla_cyo_incentive_picker_shown
 * @property {string} context The context in which the incentive picker is shown, e.g. 'create-ads', 'edit-ads', 'setup-ads', 'setup-mc', or 'setup-ads-only'.
 */

/**
 * Fired when the user selects an incentive offer.
 *
 * @event gla_cyo_incentive_selected
 * @property {string} context The context in which the incentive offer is selected.
 * @property {string} level The level of the selected incentive offer, e.g. 'low', 'medium', or 'high'.
 */

/**
 * Renders the component for picking a "Choose Your Own" incentive for ads campaigns, which allows merchants to select from different ads credit offers based on their expected ad spend.
 *
 * @fires gla_cyo_incentive_picker_shown when the incentive picker is shown to the user.
 * @fires gla_cyo_incentive_selected when the user selects an incentive offer.
 * @fires gla_documentation_link_click with `{ context: 'setup-ads' | 'setup-ads-only', link_id: 'incentives-terms-and-conditions-apply', href: 'https://ads.google.com/home/terms-and-conditions/incentives/' }`
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
			description={
				<p>
					{ __(
						'New advertisers are eligible for free Google Ads credits.',
						'google-listings-and-ads'
					) }
				</p>
			}
			title={ __( 'Ads credit offer', 'google-listings-and-ads' ) }
			verticalGap={ 4 }
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
						{ createInterpolateElement(
							__(
								'Select an offer that fits your monthly budget. New advertisers will receive an ad credit after meeting the minimum spend requirement for the selected offer. <link>Terms and conditions apply</link>.',
								'google-listings-and-ads'
							),
							{
								link: (
									<AppDocumentationLink
										context={ context }
										href="https://ads.google.com/home/terms-and-conditions/incentives/"
										linkId="incentives-terms-and-conditions-apply"
									/>
								),
							}
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
										className="gla-cyoi-radio-control__radio-control"
										help={ sprintf(
											/* translators: %s: amount in users' currency */
											__(
												'Spend %s with Google Ads in the first 60 days to unlock the credit.',
												'google-listings-and-ads'
											),
											formattedSpendAmount
										) }
										key={ id }
										onChange={ handleIncentiveChange }
										options={ [ { label, value: offer } ] }
										selected={ selectedIncentiveOffer }
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
