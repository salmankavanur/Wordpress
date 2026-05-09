import React, { useState, useEffect } from 'react';
import { Button, TextControl, Notice, Modal } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { QRCodeCanvas } from 'qrcode.react';

const SecuritySetup: React.FC = () => {
	const [ config, setConfig ] = useState< any >( null );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ totpSetup, setTotpSetup ] = useState< any >( null );
	const [ verificationCode, setVerificationCode ] = useState( '' );
	const [ notice, setNotice ] = useState< {
		type: 'success' | 'error';
		msg: string;
	} | null >( null );
	const [ backupCodes, setBackupCodes ] = useState< string[] >( [] );

	useEffect( () => {
		fetchConfig();
	}, [] );

	const fetchConfig = () => {
		// Use timestamp to bypass any REST API caching
		apiFetch( {
			path: `/2fa-auth-digibayt/v1/user/config?_=${ Date.now() }`,
		} ).then( ( res: any ) => {
			if ( res ) setConfig( res );
		} );
	};

	const startTotpSetup = () => {
		apiFetch( {
			path: `/2fa-auth-digibayt/v1/user/totp/setup?_=${ Date.now() }`,
		} ).then( ( res: any ) => {
			setTotpSetup( res );
			setIsModalOpen( true );
		} );
	};

	const verifyTotp = () => {
		apiFetch( {
			path: '/2fa-auth-digibayt/v1/user/totp/verify',
			method: 'POST',
			data: { code: verificationCode },
		} )
			.then( () => {
				setNotice( {
					type: 'success',
					msg: 'TOTP enabled successfully!',
				} );
				setIsModalOpen( false );
				setConfig( { ...config, enabled: true } ); // Immediate update
				fetchConfig(); // Background refresh
			} )
			.catch( ( err ) => {
				setNotice( {
					type: 'error',
					msg: err.message || 'Invalid code.',
				} );
			} );
	};

	const generateBackupCodes = () => {
		apiFetch( {
			path: '/2fa-auth-digibayt/v1/user/backup-codes/generate',
			method: 'POST',
		} ).then( ( res: any ) => {
			setBackupCodes( res.codes );
		} );
	};

	if ( ! config ) return <p>Loading security settings...</p>;

	return (
		<div className="tab-content">
			<h2>My Security Setup</h2>
			{ notice && (
				<Notice
					status={ notice.type }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.msg }
				</Notice>
			) }

			<div className="setup-section">
				<h3>Two-Factor Authentication Status</h3>
				<p>
					Status:{ ' ' }
					<strong>{ config.enabled ? 'Enabled' : 'Disabled' }</strong>
				</p>

				{ ! config.enabled ? (
					<Button isPrimary onClick={ startTotpSetup }>
						Enable 2FA (TOTP)
					</Button>
				) : (
					<p className="success-text">
						Your account is secured with 2FA.
					</p>
				) }
			</div>

			{ config.enabled && (
				<div className="setup-section" style={ { marginTop: '20px' } }>
					<h3>Backup Codes</h3>
					<p>
						Backup codes can be used to access your account if you
						lose your phone.
					</p>
					<Button isSecondary onClick={ generateBackupCodes }>
						Generate New Backup Codes
					</Button>

					{ backupCodes.length > 0 && (
						<div className="backup-codes-list">
							<h4>Your New Backup Codes</h4>
							<p>
								Save these codes in a safe place. Each code can
								only be used once.
							</p>
							<ul>
								{ backupCodes.map( ( code, i ) => (
									<li key={ i }>
										<code>{ code }</code>
									</li>
								) ) }
							</ul>
						</div>
					) }
				</div>
			) }

			{ isModalOpen && (
				<Modal
					title="Setup Authenticator App"
					onRequestClose={ () => setIsModalOpen( false ) }
				>
					<div className="totp-setup-modal">
						<p>
							1. Scan the QR code below with your authenticator
							app (e.g., Google Authenticator, Authy).
						</p>
						<div
							className="qr-code"
							style={ {
								padding: '20px',
								background: '#fff',
								display: 'inline-block',
								border: '1px solid #ddd',
								marginTop: '15px',
							} }
						>
							<QRCodeCanvas
								value={ totpSetup.qr_url }
								size={ 200 }
								level="H"
							/>
						</div>
						<p style={ { marginTop: '10px' } }>
							<strong>Manual Entry Code:</strong>{ ' ' }
							<code>{ totpSetup.secret }</code>
						</p>

						<p style={ { marginTop: '20px' } }>
							2. Enter the 6-digit code from the app:
						</p>
						<TextControl
							value={ verificationCode }
							onChange={ setVerificationCode }
							placeholder="123456"
						/>
						<div className="modal-actions">
							<Button isPrimary onClick={ verifyTotp }>
								Verify & Enable
							</Button>
							<Button
								isTertiary
								onClick={ () => setIsModalOpen( false ) }
							>
								Cancel
							</Button>
						</div>
					</div>
				</Modal>
			) }
		</div>
	);
};

export default SecuritySetup;
