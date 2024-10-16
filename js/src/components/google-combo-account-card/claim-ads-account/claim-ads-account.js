/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ClaimAdsAccountButton from './claim-ads-account-button';
import Section from '.~/wcdl/section';
import useWindowFocus from '.~/hooks/useWindowFocus';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import { useAppDispatch } from '.~/data';
import './claim-ads-account.scss';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';

/**
 * ClaimAdsAccount component.
 *
 * @return {JSX.Element} ClaimAdsAccount component.
 */
const ClaimAdsAccount = () => {
	const [ , forceUpdate ] = useState( 0 );
	const isWindowFocused = useWindowFocus();
	const { hasAccess, hasFinishedResolution, inviteLink, step } =
		useGoogleAdsAccountStatus();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();
	const { invalidateResolution } = useAppDispatch();
	const claimButtonClickedRef = useRef( false );

	useEffect( () => {
		if ( isWindowFocused && claimButtonClickedRef.current ) {
			invalidateResolution( 'getGoogleAdsAccountStatus' );
			forceUpdate( ( prev ) => prev + 1 );
		}
	}, [ isWindowFocused, invalidateResolution ] );

	useEffect( () => {
		if ( claimButtonClickedRef.current && hasFinishedResolution ) {
			claimButtonClickedRef.current = false;
		}

		if ( hasAccess === true && step === 'conversion_action' ) {
			upsertAdsAccount();
		}
	}, [ hasAccess, hasFinishedResolution, step, upsertAdsAccount ] );

	const isLoading =
		loading || ( ! hasFinishedResolution && claimButtonClickedRef.current );

	if (
		hasAccess ||
		( ! hasFinishedResolution && ! claimButtonClickedRef.current )
	) {
		return null;
	}

	return (
		<Section.Card.Body className="gla-claim-ads-account-section">
			<Flex gap={ 4 }>
				<FlexBlock>
					<div className="gla-claim-ads-account-box">
						<h4>
							{ __(
								'Claim your Google Ads account',
								'google-listings-and-ads'
							) }
						</h4>
						<p>
							{ __(
								'You need to accept the invitation to the Google Ads account we created for you. This gives you access to Google Ads and sets up conversion measurement. You must claim your account in the next 20 days.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							{ __(
								'After accepting the invitation, you’ll be prompted to set up billing. We highly recommend doing this to avoid having to do it later on.',
								'google-listings-and-ads'
							) }
						</p>
						<ClaimAdsAccountButton
							inviteLink={ inviteLink }
							loading={ isLoading }
							onClick={ () => {
								claimButtonClickedRef.current = true;
							} }
						/>
					</div>
				</FlexBlock>
			</Flex>
		</Section.Card.Body>
	);
};

export default ClaimAdsAccount;
