import React, { useState, useEffect } from 'react';
import { Button, TextControl, Notice, Modal } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const SecuritySetup: React.FC = () => {
	const [config, setConfig] = useState<any>(null);
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [totpSetup, setTotpSetup] = useState<any>(null);
	const [verificationCode, setVerificationCode] = useState('');
	const [notice, setNotice] = useState<{ type: 'success' | 'error', msg: string } | null>(null);
	const [backupCodes, setBackupCodes] = useState<string[]>([]);

	useEffect(() => {
		fetchConfig();
	}, []);

	const fetchConfig = () => {
		apiFetch({ path: '/wp-2fa-digibayt/v1/user/config' }).then((res: any) => {
			setConfig(res);
		});
	};

	const startTotpSetup = () => {
		apiFetch({ path: '/wp-2fa-digibayt/v1/user/totp/setup' }).then((res: any) => {
			setTotpSetup(res);
			setIsModalOpen(true);
		});
	};

	const verifyTotp = () => {
		apiFetch({
			path: '/wp-2fa-digibayt/v1/user/totp/verify',
			method: 'POST',
			data: { code: verificationCode },
		}).then(() => {
			setNotice({ type: 'success', msg: 'TOTP enabled successfully!' });
			setIsModalOpen(false);
			fetchConfig();
		}).catch((err) => {
			setNotice({ type: 'error', msg: err.message || 'Invalid code.' });
		});
	};

	const generateBackupCodes = () => {
		apiFetch({
			path: '/wp-2fa-digibayt/v1/user/backup-codes/generate',
			method: 'POST',
		}).then((res: any) => {
			setBackupCodes(res.codes);
		});
	};

	if (!config) return <p>Loading security settings...</p>;

	return (
		<div className="tab-content">
			<h2>My Security Setup</h2>
			{notice && <Notice status={notice.type} onRemove={() => setNotice(null)}>{notice.msg}</Notice>}

			<div className="setup-section">
				<h3>Two-Factor Authentication Status</h3>
				<p>Status: <strong>{config.enabled ? 'Enabled' : 'Disabled'}</strong></p>
				
				{!config.enabled ? (
					<Button isPrimary onClick={startTotpSetup}>Enable 2FA (TOTP)</Button>
				) : (
					<p className="success-text">Your account is secured with 2FA.</p>
				)}
			</div>

			{config.enabled && (
				<div className="setup-section" style={{ marginTop: '20px' }}>
					<h3>Backup Codes</h3>
					<p>Backup codes can be used to access your account if you lose your phone.</p>
					<Button isSecondary onClick={generateBackupCodes}>Generate New Backup Codes</Button>
					
					{backupCodes.length > 0 && (
						<div className="backup-codes-list">
							<h4>Your New Backup Codes</h4>
							<p>Save these codes in a safe place. Each code can only be used once.</p>
							<ul>
								{backupCodes.map((code, i) => <li key={i}><code>{code}</code></li>)}
							</ul>
						</div>
					)}
				</div>
			)}

			{isModalOpen && (
				<Modal title="Setup Authenticator App" onRequestClose={() => setIsModalOpen(false)}>
					<div className="totp-setup-modal">
						<p>1. Scan the QR code below with your authenticator app (e.g., Google Authenticator, Authy).</p>
						<div className="qr-code">
							{totpSetup.qr_url.startsWith('http') ? (
								<img src={totpSetup.qr_url} alt="QR Code" />
							) : (
								<div className="qr-fallback" style={{ padding: '20px', background: '#f0f0f1', wordBreak: 'break-all' }}>
									<p><strong>Manual Entry Code:</strong></p>
									<code>{totpSetup.secret}</code>
									<p style={{ marginTop: '10px' }}><small>Scan the URL below or enter the secret manually in your app:</small></p>
									<p style={{ fontSize: '10px' }}>{totpSetup.qr_url}</p>
								</div>
							)}
						</div>
						<p>2. Enter the 6-digit code from the app:</p>
						<TextControl
							value={verificationCode}
							onChange={setVerificationCode}
							placeholder="123456"
						/>
						<div className="modal-actions">
							<Button isPrimary onClick={verifyTotp}>Verify & Enable</Button>
							<Button isTertiary onClick={() => setIsModalOpen(false)}>Cancel</Button>
						</div>
					</div>
				</Modal>
			)}
		</div>
	);
};

export default SecuritySetup;
