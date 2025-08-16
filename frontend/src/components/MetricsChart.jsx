import React, {useEffect, useState} from "react";
import axios from "axios";
import { Line } from "react-chartjs-2";
import {
  Chart as ChartJS,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Legend,
  Tooltip
} from "chart.js";

ChartJS.register(LineElement, CategoryScale, LinearScale, PointElement, Legend, Tooltip);

const MetricsChart = () => {
  const [metrics, setMetrics] = useState([]);

  useEffect(() => {
    fetchMetrics();
    const interval = setInterval(fetchMetrics, 30000); // every 30 seconds

    return () => clearInterval(interval);
  }, []);

  const fetchMetrics = async () => {
    try {
      const res = await axios.get(`${process.env.REACT_APP_API_URL}/api/metrics/history`);
      console.log(res);
      setMetrics(res.data.reverse());
    } catch (err) {
      console.error('Failed to fetch metrics: ', err);
    }
  };

  const labels = metrics.map((metric) => metric.recordedAt);
  const data = {
    labels,
    datasets: [
      {
        label: "CPU %",
        data: metrics.map((metric) => metric.cpu),
        borderColor: "red",
        fill: false,
      },
      {
        label: "Memory %",
        data: metrics.map((metric) => metric.memory),
        borderColor: "blue",
        fill: false,
      },
      {
        label: "Disk %",
        data: metrics.map((metric) => metric.disk),
        borderColor: "green",
        fill: false,
      },
    ],
  };

  return (
    <div style={{ height:"400px"}}>
      <h2>System Metrics for last 24 hours</h2>
      <Line data={data} options={{ responsive: true , maintainAspectRatio: false }} />
    </div>
  );
}

export default MetricsChart;