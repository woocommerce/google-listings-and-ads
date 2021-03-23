/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import SwitchUrlCard from '../switch-url-card';
import ReclaimUrlCard from '../reclaim-url-card';
import AppTextButton from '.~/components/app-text-button';
import OverwriteFeedCard from '../overwrite-feed-card';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const ConnectMCCard = ( props ) => {
	const { onCreateNew = () => {} } = props;
	const [ value, setValue ] = useState();
	const [
		fetchConnectMCAccount,
		{ loading, error, response, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { receiveMCAccount } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			const res = await fetchConnectMCAccount( { parse: false } );
			const account = await res.json();

			receiveMCAccount( account );
		} catch ( e ) {
			if ( ! [ 403, 409 ].includes( e.status ) ) {
				createNotice(
					'error',
					__(
						'Unable to create Merchant Center account. Please try again later.',
						'google-listings-and-ads'
					)
				);
			}
		}
	};

	if ( response?.status === 409 ) {
		if ( error?.action === 'switch-url' ) {
			return (
				<SwitchUrlCard
					id={ error.id }
					message={ error.message }
					claimedUrl={ error.claimed_url }
					newUrl={ error.new_url }
					onSelectAnotherAccount={ reset }
				/>
			);
		} else if ( error?.action === 'feed-overwrite' ) {
			return (
				<OverwriteFeedCard
					id={ error.id }
					url={ error.url }
					onSelectAnotherAccount={ reset }
				/>
			);
		}
	}

	if ( response && response.status === 403 ) {
		return <ReclaimUrlCard websiteUrl={ error.website_url } />;
	}

	return (
		<Section.Card>
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'You have existing Merchant Center accounts',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<ContentButtonLayout>
					<MerchantCenterSelectControl
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
			<Section.Card.Footer>
				<AppTextButton isSecondary onClick={ onCreateNew }>
					{ __(
						'Or, create a new Merchant Center account',
						'google-listings-and-ads'
					) }
				</AppTextButton>
			</Section.Card.Footer>
		</Section.Card>
	);
};

export default ConnectMCCard;
