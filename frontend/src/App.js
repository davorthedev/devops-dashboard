import React from 'react';
import MetricsChart from './components/MetricsChart.jsx';
import LogsView from "./components/LogsView.jsx";
import ServiceControls from './components/ServiceControls.jsx';

function App() {
  return (
    <div style={{ padding: '20px', fontFamily: 'Arial' }}>
      <h1>DevOps Dashboard</h1>
      <MetricsChart />
      <LogsView />
      <ServiceControls />
    </div>
  );
}

export default App;