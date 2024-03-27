/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useRestAPIAuthURLRedirect from '.~/hooks/useRestAPIAuthURLRedirect';

/**
 * Button to initiate auth process for WP Rest API
 *
 * @param {Object} params The component params
 * @return {JSX.Element} The button.
 */
const EnableNewProductSyncButton = ( params ) => {
	const [ handleRestAPIAuthURLRedirect ] = useRestAPIAuthURLRedirect();

	return (
		<AppButton
			isSecondary
			onClick={ handleRestAPIAuthURLRedirect }
			{ ...params }
		/>
	);
};

export default EnableNewProductSyncButton;
