/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AppTextButton from '.~/components/app-text-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import ContentButtonLayout from '.~/components/content-button-layout';
import betaExistingProductListingsStatement from './betaExistingProductListingsStatement';

/**
 * This is used temporarily for beta testing purpose. For production roll out, we should remove this and use the above SwitchUrlCard instead.
 *
 * @param {Object} props Props.
 */
const BetaSwitchUrlCard = ( props ) => {
	const { id, message, onSelectAnotherAccount = () => {} } = props;

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
							{ betaExistingProductListingsStatement }
						</Subsection.HelperText>
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

export default BetaSwitchUrlCard;
