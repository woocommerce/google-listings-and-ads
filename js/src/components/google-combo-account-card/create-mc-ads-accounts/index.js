/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCreateMCAccount from '../../google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from '../../../hooks/useUpsertAdsAccount';

/**
 * Create MC and Ads accounts.
 */
const CreateMCAdsAccounts = () => {
    const [merchantCenterAccountCreated, setMerchantCenterAccountCreated] = useState( false );
    const [googleAdsAccountCreated, setGoogleAdsAccountCreated] = useState( false );

    const [ handleCreateMCAccount, { loading, error, response } ] = useCreateMCAccount();

    useEffect( () => {
        if ( ! loading && ! merchantCenterAccountCreated ) {
            handleCreateMCAccount();
            setMerchantCenterAccountCreated( true );
        }

        console.log( 'response', response );
        console.log( 'error', error );
        console.log( 'loading', loading );
    }, [
        loading,
        response,
        error,
        merchantCenterAccountCreated,
    ]);

    return <div>Creating MC and Ads account.</div>
};

export default CreateMCAdsAccounts;
