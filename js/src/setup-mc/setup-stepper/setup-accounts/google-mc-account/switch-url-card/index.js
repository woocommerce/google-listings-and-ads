/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import ReclaimUrlCard from '../reclaim-url-card';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppInputLinkControl from '.~/components/app-input-link-control';
import './index.scss';

const SwitchUrlCard = ( props ) => {
	const { id, claimedUrl, newUrl, onSelectAnotherAccount = () => {} } = props;
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [
		fetchMCAccountSwitchUrl,
		{ loading, error, response },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/switch-url`,
		method: 'POST',
		data: { id },
	} );

	const handleSwitch = async () => {
		try {
			await fetchMCAccountSwitchUrl( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( e.status !== 403 ) {
				const body = await e.json();
				const errorMessage =
					body.message ||
					__(
						'Unable to switch to your new URL. Please try again later.',
						'google-listings-and-ads'
					);
				createNotice( 'error', errorMessage );
			}
		}
	};

	const handleUseDifferentMCClick = () => {
		onSelectAnotherAccount();
	};

	if ( response && response.status === 403 ) {
		return (
			<ReclaimUrlCard
				id={ error.id }
				websiteUrl={ error.website_url }
				onSwitchAccount={ handleUseDifferentMCClick }
			/>
		);
	}

	return (
		<AccountCard
			className="gla-switch-url-card"
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ toAccountText( id ) }
			indicator={
				<AppButton
					isSecondary
					disabled={ loading }
					onClick={ handleUseDifferentMCClick }
				>
					{ __( 'Switch account', 'google-listings-and-ads' ) }
				</AppButton>
			}
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Switch to this new URL',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<Subsection.Body>
					{ createInterpolateElement(
						__(
							'This Merchant Center account already has a verified and claimed URL, <claimedurl/>.',
							'google-listings-and-ads'
						),
						{
							claimedurl: <span>{ claimedUrl }</span>,
						}
					) }
				</Subsection.Body>
				<ContentButtonLayout>
					<AppInputLinkControl disabled value={ newUrl } />
					<AppButton
						isSecondary
						loading={ loading }
						eventName="gla_mc_account_reclaim_url_button_click"
						onClick={ handleSwitch }
					>
						{ __(
							'Switch to this new URL',
							'google-listings-and-ads'
						) }
					</AppButton>
				</ContentButtonLayout>
				<Subsection.HelperText>
					{ createInterpolateElement(
						__(
							'If you switch your claimed URL to <newurl />, you will lose your claim to <claimedurl />. This will cause any existing product listings tied to <claimedurl /> to stop running.',
							'google-listings-and-ads'
						),
						{
							newurl: <span>{ newUrl }</span>,
							claimedurl: <span>{ claimedUrl }</span>,
						}
					) }
				</Subsection.HelperText>
			</Section.Card.Body>
		</AccountCard>
	);
};

export default SwitchUrlCard;
