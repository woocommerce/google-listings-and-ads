/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/ads-account-select-control';
import AppButton from '.~/components/app-button';
import ContentButtonLayout from '.~/components/content-button-layout';
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
		<Section.Card>
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
					<AppButton
						isSecondary
						loading={ loading || isResolving }
						disabled={ ! value }
						eventName="gla_ads_account_connect_button_click"
						onClick={ handleConnectClick }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
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
		</Section.Card>
	);
};

export default ConnectAds;
