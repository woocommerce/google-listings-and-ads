/**
 * Display account info.
 *
 * @param {Object} props Props.
 * @param {Object} props.account Account object.
 * @param {string} props.text Text to display.
 */
const AccountInfo = ( { account, text } ) => {
	if ( ! account?.id ) {
		return null;
	}

	return <p>{ text }</p>;
};

export default AccountInfo;
