import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const Dashboard: React.FC = () => {
	const [stats, setStats] = useState({
		total_users: 0,
		enabled_users: 0,
		failed_attempts: 0,
	});

	useEffect(() => {
		apiFetch({ path: '/wp-2fa-digibayt/v1/stats' }).then((res: any) => {
			if (res) setStats(res);
		});
	}, []);

	return (
		<div className="tab-content">
			<h2>Overview</h2>
			<div className="stats-grid">
				<div className="stat-card">
					<h3>Total Users</h3>
					<span className="value">{stats.total_users}</span>
				</div>
				<div className="stat-card">
					<h3>2FA Enabled</h3>
					<span className="value">{stats.enabled_users}</span>
				</div>
				<div className="stat-card">
					<h3>Failed Attempts (24h)</h3>
					<span className="value">{stats.failed_attempts}</span>
				</div>
			</div>
		</div>
	);
};

export default Dashboard;
