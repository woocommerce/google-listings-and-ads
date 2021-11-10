/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CardDivider, Notice } from '@wordpress/components';
import { Icon, link as linkIcon } from '@wordpress/icons';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppInputControl from '.~/components/app-input-control';
import './index.scss';

const ReclaimUrlCard = ( props ) => {
	const { id, websiteUrl, onSwitchAccount = noop } = props;
	const { invalidateResolution } = useAppDispatch();
	const [
		fetchClaimOverwrite,
		{ loading, error, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/claim-overwrite`,
		method: 'POST',
		data: { id },
	} );

	const handleReclaimClick = async () => {
		reset();
		await fetchClaimOverwrite( { parse: false } );
		invalidateResolution( 'getGoogleMCAccount', [] );
	};

	return (
		<AccountCard
			className="gla-reclaim-url-card"
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ toAccountText( id ) }
			indicator={
				<AppButton
					isSecondary
					loading={ loading }
					onClick={ onSwitchAccount }
				>
					{ __( 'Switch account', 'google-listings-and-ads' ) }
				</AppButton>
			}
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __( 'Reclaim your URL', 'google-listings-and-ads' ) }
				</Subsection.Title>
				<p>
					{ __(
						'Your URL is currently claimed by another Merchant Center account.',
						'google-listings-and-ads'
					) }
				</p>
				<ContentButtonLayout>
					<AppInputControl
						disabled
						prefix={ <Icon icon={ linkIcon } size={ 24 } /> }
						value={ websiteUrl }
					></AppInputControl>
					<AppButton
						isSecondary
						isDestructive
						loading={ loading }
						eventName="gla_mc_account_reclaim_url_button_click"
						onClick={ handleReclaimClick }
					>
						{ __( 'Reclaim my URL', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
				<Subsection.HelperText>
					{ createInterpolateElement(
						__(
							'If you reclaim this URL, it will cause any existing product listings or ads to stop running, and the other verified account will be notified that they have lost their claim. <link>Learn more</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="setup-mc"
									linkId="claim-url"
									href="https://support.google.com/merchants/answer/176793"
								/>
							),
						}
					) }
				</Subsection.HelperText>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ createInterpolateElement(
							__(
								'<strong>We were unable to reclaim this URL.</strong> You may not have permission to reclaim this URL, or an error might have occurred. Try again later or contact your Google account administrator.',
								'google-listings-and-ads'
							),
							{
								strong: <strong></strong>,
							}
						) }
					</Notice>
				) }
			</Section.Card.Body>
		</AccountCard>
	);
};

export default ReclaimUrlCard;
