/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import ContentButtonLayout from '../../content-button-layout';
import AccountId from '../account-id';
import './index.scss';

const SwitchUrlCard = ( props ) => {
	const { id, message, onSelectAnotherAccount = () => {} } = props;

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
							// TODO: handle switch to new URL.
							// loading={ loading }
							// disabled={ ! value }
							// onClick={ handleConnectClick }
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
