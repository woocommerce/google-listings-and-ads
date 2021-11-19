/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, CardDivider, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/ads-account-select-control';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import LoadingLabel from '.~/components/loading-label';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

const ConnectAds = ( props ) => {
	const { onCreateNew = () => {} } = props;
	const [ value, setValue ] = useState();
	const [ fetchConnectAdsAccount, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { refetchGoogleAdsAccount, isResolving } = useGoogleAdsAccount();
	const { createNotice } = useDispatchCoreNotices();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			await fetchConnectAdsAccount();
			refetchGoogleAdsAccount();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google Ads account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AccountCard appearance={ APPEARANCE.GOOGLE_ADS }>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Connect your Google Ads account',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<Flex>
					<AdsAccountSelectControl
						value={ value }
						onChange={ setValue }
					/>
					{ loading || isResolving ? (
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
				</Flex>
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
