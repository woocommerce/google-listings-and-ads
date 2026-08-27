/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useRef,
	useImperativeHandle,
	forwardRef,
} from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import BudgetSetup from './budget-setup';
import CampaignAssetsForm from './campaign-assets-form';
import ceil from '~/utils/ceil';

/**
 * @typedef {Object} BudgetIncentivePromptHandler
 * @property {(dailyBudget: number) => Promise<number>} resolve Start resolving a higher budget if needed.
 * 	 - Resolved with a number if a higher budget has set.
 *   - Resolved with `NaN` if cancelled.
 * 	 - Resolved with `null` if this prompt didn't take place.
 */

/**
 * Prompts the user to increase their budget to get back free credits by
 * calling `ref.resolve( dailyBudget )`.
 *
 * Note that this component prompts the user only once, and only if spending
 * eligibility in the same currency has been successfully retrieved and the
 * given daily budget is less than the spending eligibility divided by 60 days.
 *
 * @param {Object} props React props to pass to CampaignAssetsForm.
 * @param {import('react').MutableRefObject<BudgetIncentivePromptHandler>} ref React ref to be attached to the handler of this component.
 */
function BudgetIncentivePrompt( props, ref ) {
	const deferredRef = useRef( null );
	const [ spending, setSpending ] = useState( 0 );
	const [ resolved, setResolved ] = useState( null );
	const { fetchAdsIncentiveCredits } = useAppDispatch();
	const { adsCurrencyConfig, formatAmount } = useAdsCurrency();

	const defaultDailyBudget = ceil(
		spending / 60,
		adsCurrencyConfig.precision
	);

	useEffect( () => {
		// Set `resolved` to true directly if
		// - Unable to fetch spending
		// - The currency of the spending is different from the Ads currency
		fetchAdsIncentiveCredits()
			.then( ( data ) => {
				if ( data.adsCurrency === data.currency ) {
					return data.spending;
				}
				return Promise.reject();
			} )
			.then( setSpending )
			.catch( () => setResolved( true ) );
	}, [ fetchAdsIncentiveCredits ] );

	useImperativeHandle( ref, () => ( {
		resolve( dailyBudget ) {
			if ( resolved || dailyBudget >= defaultDailyBudget ) {
				return Promise.resolve( null );
			}

			return new Promise( ( resolve ) => {
				deferredRef.current = resolve;
				setResolved( false );
			} );
		},
	} ) );

	const validateByMinSpending = ( values ) => {
		if ( values.amount * 60 < spending ) {
			// No need to show help message.
			return { amount: false };
		}
		return {};
	};

	const handleCancel = () => {
		deferredRef.current( NaN );
		setResolved( true );
	};

	const handleSubmit = ( values ) => {
		deferredRef.current( values.dailyBudget );
		setResolved( true );
	};

	if ( resolved !== false ) {
		return null;
	}

	return (
		<CampaignAssetsForm
			{ ...props }
			initialCampaign={ {
				level: 'custom',
				amount: defaultDailyBudget,
			} }
			onSubmit={ handleSubmit }
			validate={ validateByMinSpending }
		>
			{ ( formContext ) => {
				return (
					<AppModal
						buttons={ [
							<AppButton
								key="cancel"
								onClick={ handleCancel }
								text={ __(
									'Cancel',
									'google-listings-and-ads'
								) }
								variant="secondary"
							/>,
							<AppButton
								disabled={ ! formContext.isValidForm }
								key="change"
								onClick={ formContext.handleSubmit }
								text={ __(
									'Change budget',
									'google-listings-and-ads'
								) }
								variant="primary"
							/>,
						] }
						onRequestClose={ handleCancel }
						shouldCloseOnClickOutside={ false }
						title={ __(
							'This offer won’t last long!',
							'google-listings-and-ads'
						) }
					>
						<h4>
							{ sprintf(
								// translators: The recommended amount in currency format.
								__(
									'Increase your budget to %s and get it all back in FREE AD CREDIT*!',
									'google-listings-and-ads'
								),
								formatAmount( defaultDailyBudget )
							) }
						</h4>
						<p>
							{ __(
								'You have 60 days.',
								'google-listings-and-ads'
							) }
						</p>
						<Flex direction="column" gap={ 4 }>
							<BudgetSetup hideRecommendations />
							<Flex gap={ 1 } justify="flex-start">
								*
								<Link
									href="https://www.google.com/ads/coupons/terms/"
									target="_blank"
									type="external"
								>
									{ __(
										'Terms and Conditions',
										'google-listings-and-ads'
									) }
								</Link>
							</Flex>
						</Flex>
					</AppModal>
				);
			} }
		</CampaignAssetsForm>
	);
}

export default forwardRef( BudgetIncentivePrompt );
