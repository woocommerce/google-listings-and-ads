js/s/**
* Internal dependencies
*/
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Call `useAppSelectDispatch` with `"getIsSsl"`.
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
const useSsl = () => {
  return useAppSelectDispatch( 'getIsSsl' );
};

export default useSsl;rc/hooks/useSsl.js
