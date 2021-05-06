/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AppButton from '.~/components/app-button';
import AppTextButton from '.~/components/app-text-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import './index.scss';

/**
 * @param {Object} props Props.
 */
const SwitchUrlCard = ( props ) => {
	const {
		id,
		message,
		claimedUrl,
		newUrl,
		onSelectAnotherAccount = () => {},
	} = props;
	const { invalidateResolution } = useAppDispatch();
	const [ fetchMCAccountSwitchUrl, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/switch-url`,
		method: 'POST',
		data: { id },
	} );

	const handleSwitch = async () => {
		await fetchMCAccountSwitchUrl();
		invalidateResolution( 'getGoogleMCAccount', [] );
	};

	const handleUseDifferentMCClick = () => {
		onSelectAnotherAccount();
	};

	return (
		<Section.Card className="gla-switch-url-card">
			<Section.Card.Body>
				<ContentButtonLayout>
					<Subsection.Title>{ toAccountText( id ) }</Subsection.Title>
				</ContentButtonLayout>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>{ message }</Subsection.Title>
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
					</div>
					<div className="buttons">
						<AppButton
							isSecondary
							loading={ loading }
							onClick={ handleSwitch }
						>
							{ __(
								'Switch to my new URL',
								'google-listings-and-ads'
							) }
						</AppButton>
					</div>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<AppTextButton
					isSecondary
					onClick={ handleUseDifferentMCClick }
				>
					{ __(
						'Or, use a different Merchant Center account',
						'google-listings-and-ads'
					) }
				</AppTextButton>
			</Section.Card.Footer>
		</Section.Card>
	);
};

export default SwitchUrlCard;
