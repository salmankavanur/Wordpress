import React, { useState, useEffect } from 'react';
import { TabPanel } from '@wordpress/components';
import Dashboard from './components/Dashboard';
import Settings from './components/Settings';
import AuditLogs from './components/AuditLogs';
import SecuritySetup from './components/SecuritySetup';

const AdminApp: React.FC = () => {
	const tabs = [
		{
			name: 'dashboard',
			title: 'Dashboard',
			className: 'dashboard-tab',
		},
		{
			name: 'security',
			title: 'My Security',
			className: 'security-tab',
		},
		{
			name: 'settings',
			title: 'Settings',
			className: 'settings-tab',
		},
		{
			name: 'logs',
			title: 'Audit Logs',
			className: 'logs-tab',
		},
	];

	return (
		<div className="digibayt-2fa-wrap">
			<header className="header">
				<h1>2FA Auth by DigiBayt</h1>
				<p className="description">
					Manage your site's security with advanced 2FA features.
				</p>
			</header>

			<TabPanel
				className="main-tabs"
				activeClass="is-active"
				tabs={ tabs }
			>
				{ ( tab ) => {
					switch ( tab.name ) {
						case 'dashboard':
							return <Dashboard />;
						case 'security':
							return <SecuritySetup />;
						case 'settings':
							return <Settings />;
						case 'logs':
							return <AuditLogs />;
						default:
							return null;
					}
				} }
			</TabPanel>
		</div>
	);
};

export default AdminApp;
