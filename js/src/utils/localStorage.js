const localStorageExists =
	typeof window !== 'undefined' && 'localStorage' in window;

const localStorage = {
	get( key ) {
		return localStorageExists ? window.localStorage.getItem( key ) : null;
	},

	set( key, value ) {
		return localStorageExists
			? window.localStorage.setItem( key, value )
			: null;
	},

	remove( key ) {
		return localStorageExists
			? window.localStorage.removeItem( key )
			: null;
	},
};

export default localStorage;
