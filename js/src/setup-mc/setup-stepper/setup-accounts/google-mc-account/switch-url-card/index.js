/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '../../content-button-layout';
import AccountId from '../account-id';
import './index.scss';

const SwitchUrlCard = ( props ) => {
	const {
		id,
		message,
		claimedUrl,
		newUrl,
		onSelectAnotherAccount = () => {},
	} = props;
	const { receiveMCAccount } = useAppDispatch();
	const [ fetchMCAccountSwitchUrl, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/switch-url`,
		method: 'POST',
		data: { id },
	} );

	const handleSwitch = async () => {
		const account = await fetchMCAccountSwitchUrl();

		receiveMCAccount( account );
	};

	return (
		<Section.Card className="gla-switch-url-card">
			<Section.Card.Body>
				<ContentButtonLayout>
					<Subsection.Title>
						<AccountId id={ id } />
					</Subsection.Title>
				</ContentButtonLayout>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>{ message }</Subsection.Title>
						<Subsection.HelperText>
							{ __(
								'If you switch your claimed URL to your new URL, you will lose your claim to the old one. This will cause any existing product listings tied to the old one to stop running.',
								'google-listings-and-ads'
							) }
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
						<AppButton onClick={ onSelectAnotherAccount }>
							{ __(
								'Select another account',
								'google-listings-and-ads'
							) }
						</AppButton>
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
		</Section.Card>
	);
};

export default SwitchUrlCard;
