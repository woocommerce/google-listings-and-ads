/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/ads-account-select-control';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ContentButtonLayout from '.~/components/content-button-layout';
import LoadingLabel from '.~/components/loading-label';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import './index.scss';

const ConnectAds = ( props ) => {
	const { onCreateNew = () => {} } = props;
	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const [ fetchConnectAdsAccount ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { refetchGoogleAdsAccount } = useGoogleAdsAccount();
	const { createNotice } = useDispatchCoreNotices();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		setLoading( true );
		try {
			await fetchConnectAdsAccount();
			await refetchGoogleAdsAccount();
		} catch ( error ) {
			setLoading( false );
			createNotice(
				'error',
				__(
					'Unable to connect your Google Ads account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
		// Wait for the upper layer component to switch to connected account card,
		// so here doesn't reset the `isLoading` to false.
	};

	return (
		<AccountCard
			className="gla-connect-ads"
			appearance={ APPEARANCE.GOOGLE_ADS }
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Connect your Google Ads account',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<ContentButtonLayout>
					<AdsAccountSelectControl
						value={ value }
						onChange={ setValue }
					/>
					{ isLoading ? (
						<LoadingLabel
							text={ __(
								'Connecting…',
								'google-listings-and-ads'
							) }
						/>
					) : (
						<AppButton
							isSecondary
							disabled={ ! value }
							eventName="gla_ads_account_connect_button_click"
							onClick={ handleConnectClick }
						>
							{ __( 'Connect', 'google-listings-and-ads' ) }
						</AppButton>
					) }
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<Button isLink onClick={ onCreateNew }>
					{ __(
						'Or, create a new Google Ads account',
						'google-listings-and-ads'
					) }
				</Button>
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectAds;
