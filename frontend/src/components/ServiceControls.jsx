import React, {useEffect, useState} from "react";
import axios from "axios";

const ServiceControls = () => {
  const [services, setServices] = useState([]);

  const monitoredService = ["nginx", "postgres", "mysql"];

  const fetchStatuses = async () => {
    try {
      const res = await axios.get(`${process.env.REACT_APP_API_URL}/api/services/status`);
      setServices(res.data);
    } catch (err) {
      console.error("Failed to fetch services:", err);
    }
  }

  useEffect(() => {
    fetchStatuses();
    const interval = setInterval(fetchStatuses, 30_000); // refresh every 30 seconds

    return () => clearInterval(interval);
  }, [])

  const controlService = async (serviceName, action) => {
    try {
      await axios.get(`${process.env.REACT_APP_API_URL}/api/services/${action}/${serviceName}`);
      fetchStatuses();
    } catch (err) {
      console.error(`Failed to ${action} ${serviceName}:`, err);
    }
  }

    return (
      <div>
        <h2>Service Controls</h2>
        {services.map((srv) => (
        <div key={srv.name} style={{ marginBottom: "10px" }}>
          <span>
            {srv.name} -{" "}
              <strong style={{ color: srv.success ? "green" : "red" }}>
              {srv.status}
            </strong>
          </span>
          <button onClick={() => controlService(srv.name, "start")}>Start</button>
          <button onClick={() => controlService(srv.name, "stop")}>Stop</button>
          <button onClick={() => controlService(srv.name, "restart")}>Restart</button>
        </div>
      ))}
      </div>
    );
}

export default ServiceControls;