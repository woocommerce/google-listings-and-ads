/**
 * External dependencies
 */
import { CardDivider } from '@wordpress/components';
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
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import SwitchUrlCard from '../switch-url-card';
import ReclaimUrlCard from '../reclaim-url-card';
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const ConnectMCCard = ( props ) => {
	const { onCreateNew = () => {} } = props;
	const [ value, setValue ] = useState();
	const { createNotice } = useDispatchCoreNotices();
	const [
		fetchMCAccounts,
		{ loading, error, response, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { invalidateResolution } = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			await fetchMCAccounts( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( ! [ 409, 403 ].includes( e.status ) ) {
				const body = await e.json();
				const message =
					body.message ||
					__(
						'Unable to connect Merchant Center account. Please try again later.',
						'google-listings-and-ads'
					);
				createNotice( 'error', message );
			}
		}
	};

	const handleSelectAnotherAccount = () => {
		reset();
	};

	if ( response && response.status === 409 ) {
		return (
			<SwitchUrlCard
				id={ error.id }
				message={ error.message }
				claimedUrl={ error.claimed_url }
				newUrl={ error.new_url }
				onSelectAnotherAccount={ handleSelectAnotherAccount }
			/>
		);
	}

	if ( response && response.status === 403 ) {
		return (
			<ReclaimUrlCard id={ error.id } websiteUrl={ error.website_url } />
		);
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ __(
				'Required to sync products and list on Google Shopping',
				'google-listings-and-ads'
			) }
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Select an existing account',
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
						eventName="gla_mc_account_connect_button_click"
						onClick={ handleConnectClick }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<AppButton
					isLink
					disabled={ loading }
					text={ __(
						'Or, create a new Merchant Center account',
						'google-listings-and-ads'
					) }
					onClick={ onCreateNew }
				/>
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectMCCard;
