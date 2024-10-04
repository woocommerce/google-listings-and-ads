/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/google-ads-account-card/connect-ads/ads-account-select-control';
import AppButton from '.~/components/app-button';
import ContentButtonLayout from '.~/components/content-button-layout';
import LoadingLabel from '.~/components/loading-label/loading-label';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '.~/utils/tracks';

/**
 *
 * @param {Object} props Props.
 * @param {Array} props.accounts Accounts.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {boolean} props.isLoading Whether the card is in a loading state.
 * @param {Function} props.setValue Callback to set the value.
 * @param {string} props.value Ads account ID.
 * @return {JSX.Element} Body component.
 */
const ConnectAdsBody = ( {
	accounts,
	handleConnectClick,
	isLoading,
	setValue,
	value,
} ) => {
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	return (
		<ContentButtonLayout>
			<AdsAccountSelectControl
				accounts={ accounts }
				value={ value }
				onChange={ setValue }
			/>
			{ isLoading ? (
				<LoadingLabel
					text={ __( 'Connecting…', 'google-listings-and-ads' ) }
				/>
			) : (
				<AppButton
					isSecondary
					disabled={ ! value }
					eventName="gla_ads_account_connect_button_click"
					eventProps={ getEventProps( {
						id: Number( value ),
					} ) }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</ContentButtonLayout>
	);
};

export default ConnectAdsBody;
