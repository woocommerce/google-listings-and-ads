/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
import { useAppDispatch } from '.~/data';

const ConnectAds = () => {
	const [ value, setValue ] = useState();
	const [ fetchConnectAdsAccount, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { receiveAdsAccount } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			const data = await fetchConnectAdsAccount();

			receiveAdsAccount( data );
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
						loading={ loading }
						disabled={ ! value }
						onClick={ handleConnectClick }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectAds;
