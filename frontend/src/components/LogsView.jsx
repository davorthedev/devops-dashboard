import React, {useEffect, useState} from "react";
import axios from "axios";

const LogsView = () => {
  const [logs, setLogs] = useState({ failed_logins: 0, suspicious_ips: [] });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchLogs = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await axios.get(`${process.env.REACT_APP_API_URL}/api/logs`);
      console.log(response);
      setLogs(response.data);
    } catch (err) {
      console.error("Failed to fetch log anomalies:", err);
      setError("Failed to fetch log anomalies. Please try again later.");
    } finally {
        setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
    const interval = setInterval(fetchLogs, 60000); // refresh every minute

    return () => clearInterval(interval);
  }, []);

  return (
    <div style={{ marginTop: "100px" }}>
      <h2>Anomaly Logs</h2>

      {loading && <p>Loading data...</p>}
      {error && <p style={{ color: "red" }}>{error}</p>}

      {!loading && !error && (
        <div>
          <p>Failed logins: {logs.failed_logins}</p>
          <p>Suspicious IPs:</p>
          {logs.suspicious_ips.length > 0 ? (
            <ul>
              {logs.suspicious_ips.map((ip) => (
                <li key={ip}>{ip}</li>
              ))}
            </ul>
          ) : (
              <p>None</p>
          )}
        </div>
      )}
    </div>
  );
}

export default LogsView;