import React, { useState, useEffect } from 'react';
import {
	ToggleControl,
	SelectControl,
	Button,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const Settings: React.FC = () => {
	const [ settings, setSettings ] = useState( {
		enforce_admins: false,
		grace_period: 3,
		remember_device: 30,
	} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState< string | null >( null );

	useEffect( () => {
		apiFetch( { path: '/2fa-auth-digibayt/v1/settings' } ).then(
			( res: any ) => {
				if (
					res &&
					typeof res === 'object' &&
					! Array.isArray( res )
				) {
					setSettings( {
						enforce_admins: !! res.enforce_admins,
						grace_period: parseInt( res.grace_period ) || 0,
						remember_device: parseInt( res.remember_device ) || 0,
					} );
				}
			}
		);
	}, [] );

	const saveSettings = () => {
		setIsSaving( true );
		apiFetch( {
			path: '/2fa-auth-digibayt/v1/settings',
			method: 'POST',
			data: settings,
		} ).then( () => {
			setNotice( 'Settings saved successfully.' );
			setIsSaving( false );
			setTimeout( () => setNotice( null ), 3000 );
		} );
	};

	return (
		<div className="tab-content">
			<h2>Global Settings</h2>
			{ notice && (
				<Notice status="success" onRemove={ () => setNotice( null ) }>
					{ notice }
				</Notice>
			) }

			<div className="settings-form">
				<ToggleControl
					label="Enforce 2FA for Administrators"
					help="All users with administrator role must enable 2FA."
					checked={ settings.enforce_admins }
					onChange={ ( val ) =>
						setSettings( { ...settings, enforce_admins: val } )
					}
				/>

				<SelectControl
					label="Grace Period (Days)"
					help="Days allowed to set up 2FA after enforcement."
					value={ settings.grace_period.toString() }
					options={ [
						{ label: '0 Days', value: '0' },
						{ label: '3 Days', value: '3' },
						{ label: '7 Days', value: '7' },
						{ label: '14 Days', value: '14' },
					] }
					onChange={ ( val ) =>
						setSettings( {
							...settings,
							grace_period: parseInt( val ),
						} )
					}
				/>

				<SelectControl
					label="Remember Device (Days)"
					help="How long to trust a device after successful 2FA."
					value={ settings.remember_device.toString() }
					options={ [
						{ label: 'Do not remember', value: '0' },
						{ label: '7 Days', value: '7' },
						{ label: '30 Days', value: '30' },
						{ label: '90 Days', value: '90' },
					] }
					onChange={ ( val ) =>
						setSettings( {
							...settings,
							remember_device: parseInt( val ),
						} )
					}
				/>

				<Button variant="primary" isBusy={ isSaving } onClick={ saveSettings }>
					Save Settings
				</Button>
			</div>
		</div>
	);
};

export default Settings;
