/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AccountCard from '.~/components/account-card';
import './connect-account-card.scss';

/**
 * ConnectAccountCard component renders an account card with a title, helper text, body, and footer sections.
 *
 * @param {Object} props The component properties.
 * @param {string} props.title The title of the account card.
 * @param {string} props.helperText The helper text for the account card.
 * @param {JSX.Element} props.body The content for the body of the account card.
 * @param {JSX.Element} props.footer The content for the footer of the account card.
 */
const ConnectAccountCard = ( { title, helperText, body, footer } ) => {
	return (
		<AccountCard
			className="gla-google-combo-connect-account-card"
			title={ title }
			helper={ helperText }
		>
			<Section.Card.Body className="gla-google-combo-connect-account-card__body">
				{ body }
			</Section.Card.Body>
			<Section.Card.Footer className="gla-google-combo-connect-account-card__footer">
				{ footer }
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectAccountCard;
