import { render, createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import AdminApp from './AdminApp';
import './style.css';

console.log('WP 2FA DigiBayt: Script loaded');

const startApp = () => {
    console.log('WP 2FA DigiBayt: Initializing...');
	const container = document.getElementById( 'wp-2fa-digibayt-admin' );
	
	if ( !container ) {
        console.error('WP 2FA DigiBayt: Container not found!');
        return;
    }

    try {
        // Diagnostic: Check for required globals
        const missing = [];
        if (!window.wp) missing.push('wp');
        if (window.wp && !window.wp.element) missing.push('wp.element');
        if (window.wp && !window.wp.components) missing.push('wp.components');
        
        if (missing.length > 0) {
            container.innerHTML = 'Error: Missing WordPress packages: ' + missing.join(', ');
            console.error('WP 2FA DigiBayt: Missing dependencies', missing);
            return;
        }

        container.innerHTML = '';
        
        if ( createRoot ) {
            console.log('WP 2FA DigiBayt: Using createRoot');
            createRoot( container ).render( <AdminApp /> );
        } else {
            console.log('WP 2FA DigiBayt: Using legacy render');
            render( <AdminApp />, container );
        }
        console.log('WP 2FA DigiBayt: Mount successful');
    } catch ( e ) {
        console.error( 'WP 2FA DigiBayt: Render Error', e );
        container.innerHTML = 'Error starting Security Dashboard. Please check browser console (F12).';
    }
};

// Try immediately if DOM is already ready, otherwise wait
if ( document.readyState === 'complete' || document.readyState === 'interactive' ) {
    startApp();
} else {
    domReady( startApp );
}
