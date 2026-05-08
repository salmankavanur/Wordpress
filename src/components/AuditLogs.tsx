import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const AuditLogs: React.FC = () => {
	const [ logs, setLogs ] = useState( [] );

	useEffect( () => {
		apiFetch( { path: '/wp-2fa-digibayt/v1/logs' } ).then( ( res: any ) => {
			if ( res ) setLogs( res );
		} );
	}, [] );

	return (
		<div className="tab-content">
			<h2>Security Audit Logs</h2>
			<table className="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Date</th>
						<th>User</th>
						<th>Event</th>
						<th>IP Address</th>
					</tr>
				</thead>
				<tbody>
					{ logs.length === 0 ? (
						<tr>
							<td colSpan={ 4 }>No logs found.</td>
						</tr>
					) : (
						logs.map( ( log: any ) => (
							<tr key={ log.id }>
								<td>{ log.created_at }</td>
								<td>{ log.user_id }</td>
								<td>{ log.event_type }</td>
								<td>{ log.ip_address }</td>
							</tr>
						) )
					) }
				</tbody>
			</table>
		</div>
	);
};

export default AuditLogs;
