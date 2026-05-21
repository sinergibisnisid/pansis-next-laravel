'use client';

import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const data = [
  { branch: 'BDG-01', access: 24, alarm: 1 },
  { branch: 'BDG-02', access: 18, alarm: 0 },
  { branch: 'CMH-01', access: 15, alarm: 3 },
  { branch: 'GRT-01', access: 12, alarm: 0 },
  { branch: 'SMD-01', access: 20, alarm: 1 },
  { branch: 'TSK-01', access: 16, alarm: 0 },
  { branch: 'CJR-01', access: 8, alarm: 0 },
  { branch: 'SKB-01', access: 22, alarm: 2 },
  { branch: 'BKS-01', access: 14, alarm: 0 },
  { branch: 'DPK-01', access: 19, alarm: 1 },
];

export function BranchActivityChart() {
  return (
    <div className="h-[250px]">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
          <XAxis
            dataKey="branch"
            tick={{ fontSize: 11, fill: '#94a3b8' }}
            axisLine={{ stroke: 'rgba(255,255,255,0.1)' }}
            tickLine={false}
          />
          <YAxis
            tick={{ fontSize: 11, fill: '#94a3b8' }}
            axisLine={{ stroke: 'rgba(255,255,255,0.1)' }}
            tickLine={false}
          />
          <Tooltip
            contentStyle={{
              backgroundColor: 'rgba(15, 23, 42, 0.9)',
              border: '1px solid rgba(255,255,255,0.1)',
              borderRadius: '8px',
              fontSize: '12px',
              color: '#e2e8f0',
            }}
          />
          <Bar dataKey="access" fill="#3b82f6" radius={[4, 4, 0, 0]} name="Access" />
          <Bar dataKey="alarm" fill="#ef4444" radius={[4, 4, 0, 0]} name="Alarm" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}
