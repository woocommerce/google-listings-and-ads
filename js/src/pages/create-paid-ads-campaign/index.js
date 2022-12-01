/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import { useAppDispatch } from '.~/data';
import { getDashboardUrl } from '.~/utils/urls';
import validateForm from '.~/utils/paid-ads/validateForm';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import AdaptiveForm from '.~/components/adaptive-form';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import AssetGroup, {
	ACTION_COMPLETE,
} from '.~/components/paid-ads/asset-group';
import { CAMPAIGN_STEP as STEP } from '.~/constants';

const dashboardURL = getDashboardUrl();

/**
 * Renders the campaign creation page.
 *
 * @fires gla_launch_paid_campaign_button_click on submit
 */
const CreatePaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const [ step, setStep ] = useState( STEP.CAMPAIGN );
	const { createAdsCampaign } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: initialCountryCodes } = useTargetAudienceFinalCountryCodes();

	const handleSubmit = async ( values, submitter ) => {
		const { action } = submitter.dataset;

		try {
			const { amount, countryCodes } = values;
			recordEvent( 'gla_launch_paid_campaign_button_click', {
				audiences: countryCodes.join( ',' ),
				budget: amount,
			} );

			await createAdsCampaign( amount, countryCodes );

			if ( action === ACTION_COMPLETE ) {
				// TODO: Save asset group
			}

			createNotice(
				'success',
				__(
					'You’ve successfully created a paid campaign!',
					'google-listings-and-ads'
				)
			);
		} catch ( e ) {
			return;
		}

		getHistory().push( getDashboardUrl() );
	};

	if ( ! initialCountryCodes ) {
		return null;
	}

	return (
		<>
			<TopBar
				title={ __(
					'Create your paid campaign',
					'google-listings-and-ads'
				) }
				helpButton={ <HelpIconButton eventContext="create-ads" /> }
				backHref={ dashboardURL }
			/>
			<AdaptiveForm
				initialValues={ {
					amount: 0,
					countryCodes: initialCountryCodes,
				} }
				validate={ validateForm }
				onSubmit={ handleSubmit }
			>
				<Stepper
					currentStep={ step }
					steps={ [
						{
							key: STEP.CAMPAIGN,
							label: __(
								'Create paid campaign',
								'google-listings-and-ads'
							),
							content: (
								<AdsCampaign
									onContinue={ () =>
										setStep( STEP.ASSET_GROUP )
									}
								/>
							),
							onClick: setStep,
						},
						{
							key: STEP.ASSET_GROUP,
							label: __(
								'Boost your campaign',
								'google-listings-and-ads'
							),
							content: <AssetGroup />,
						},
					] }
				/>
			</AdaptiveForm>
		</>
	);
};

export default CreatePaidAdsCampaign;
