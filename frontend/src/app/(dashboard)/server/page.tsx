'use client';

import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import {
  Server,
  Cpu,
  HardDrive,
  MemoryStick,
  Network,
  Activity,
  CheckCircle,
  AlertCircle,
  XCircle,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';

function generateMetricData() {
  const data = [];
  for (let i = 30; i >= 0; i--) {
    data.push({
      time: `${i}m`,
      cpu: Math.floor(Math.random() * 30) + 25,
      ram: Math.floor(Math.random() * 15) + 55,
      network: Math.floor(Math.random() * 50) + 20,
    });
  }
  return data;
}

const services = [
  { name: 'Next.js Frontend', status: 'healthy' as const, port: 3000, uptime: '15d 9h', memory: '256MB' },
  { name: 'API Gateway', status: 'healthy' as const, port: 8000, uptime: '15d 9h', memory: '512MB' },
  { name: 'WebSocket Server', status: 'healthy' as const, port: 8001, uptime: '15d 9h', memory: '128MB' },
  { name: 'MQTT Broker', status: 'healthy' as const, port: 8883, uptime: '15d 9h', memory: '256MB' },
  { name: 'PostgreSQL', status: 'healthy' as const, port: 5432, uptime: '15d 9h', memory: '1.2GB' },
  { name: 'Redis Cache', status: 'degraded' as const, port: 6379, uptime: '3d 2h', memory: '384MB' },
  { name: 'Stream Server', status: 'healthy' as const, port: 8554, uptime: '15d 9h', memory: '768MB' },
  { name: 'Nginx Proxy', status: 'healthy' as const, port: 443, uptime: '15d 9h', memory: '64MB' },
];

const statusIcons = {
  healthy: CheckCircle,
  degraded: AlertCircle,
  down: XCircle,
};

const statusColors = {
  healthy: 'text-emerald-400 bg-emerald-500/20 border-emerald-500/30',
  degraded: 'text-amber-400 bg-amber-500/20 border-amber-500/30',
  down: 'text-red-400 bg-red-500/20 border-red-500/30',
};

export default function ServerPage() {
  const [metricData, setMetricData] = useState<{ time: string; cpu: number; ram: number; network: number }[]>([]);
  const [cpuUsage, setCpuUsage] = useState(34);
  const [ramUsage, setRamUsage] = useState(62);
  const [storageUsage] = useState(45);
  const [networkIn, setNetworkIn] = useState(12.5);
  const [networkOut, setNetworkOut] = useState(8.3);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMetricData(generateMetricData());
    setMounted(true);
  }, []);

  useEffect(() => {
    if (!mounted) return;
    const interval = setInterval(() => {
      setCpuUsage(Math.floor(Math.random() * 30) + 25);
      setRamUsage(Math.floor(Math.random() * 15) + 55);
      setNetworkIn(+(Math.random() * 20 + 5).toFixed(1));
      setNetworkOut(+(Math.random() * 15 + 3).toFixed(1));

      setMetricData((prev) => {
        const newData = [...prev.slice(1)];
        newData.push({
          time: '0m',
          cpu: Math.floor(Math.random() * 30) + 25,
          ram: Math.floor(Math.random() * 15) + 55,
          network: Math.floor(Math.random() * 50) + 20,
        });
        return newData;
      });
    }, 3000);

    return () => clearInterval(interval);
  }, [mounted]);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Server Monitoring</h1>
        <p className="text-sm text-muted-foreground mt-1">
          Realtime server resource usage and service health
        </p>
      </div>

      {/* Resource Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          className="rounded-xl border border-border/40 bg-card/50 p-4 space-y-3"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Cpu className="h-4 w-4 text-blue-400" />
              <span className="text-xs font-medium text-muted-foreground">CPU Usage</span>
            </div>
            <span className="text-lg font-bold">{cpuUsage}%</span>
          </div>
          <Progress value={cpuUsage} className="h-2" />
          <p className="text-[10px] text-muted-foreground">8 cores / 16 threads</p>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="rounded-xl border border-border/40 bg-card/50 p-4 space-y-3"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <MemoryStick className="h-4 w-4 text-cyan-400" />
              <span className="text-xs font-medium text-muted-foreground">RAM Usage</span>
            </div>
            <span className="text-lg font-bold">{ramUsage}%</span>
          </div>
          <Progress value={ramUsage} className="h-2" />
          <p className="text-[10px] text-muted-foreground">{(32 * ramUsage / 100).toFixed(1)} GB / 32 GB</p>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="rounded-xl border border-border/40 bg-card/50 p-4 space-y-3"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <HardDrive className="h-4 w-4 text-emerald-400" />
              <span className="text-xs font-medium text-muted-foreground">Storage</span>
            </div>
            <span className="text-lg font-bold">{storageUsage}%</span>
          </div>
          <Progress value={storageUsage} className="h-2" />
          <p className="text-[10px] text-muted-foreground">450 GB / 1 TB</p>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="rounded-xl border border-border/40 bg-card/50 p-4 space-y-3"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Network className="h-4 w-4 text-amber-400" />
              <span className="text-xs font-medium text-muted-foreground">Network</span>
            </div>
            <Activity className="h-4 w-4 text-emerald-400" />
          </div>
          <div className="flex items-center justify-between text-xs">
            <span className="text-muted-foreground">In: <span className="text-foreground font-medium">{networkIn} MB/s</span></span>
            <span className="text-muted-foreground">Out: <span className="text-foreground font-medium">{networkOut} MB/s</span></span>
          </div>
          <p className="text-[10px] text-muted-foreground">1 Gbps interface</p>
        </motion.div>
      </div>

      {/* Realtime Chart */}
      <Card className="border-border/40 bg-card/50">
        <CardHeader className="pb-2">
          <CardTitle className="text-sm font-medium">Resource Usage (Last 30 minutes)</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="h-[280px]">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={metricData} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
                <defs>
                  <linearGradient id="cpuGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="ramGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#06b6d4" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#06b6d4" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="netGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#f59e0b" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#f59e0b" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
                <XAxis dataKey="time" tick={{ fontSize: 10, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 10, fill: '#94a3b8' }} axisLine={false} tickLine={false} domain={[0, 100]} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    border: '1px solid rgba(255,255,255,0.1)',
                    borderRadius: '8px',
                    fontSize: '12px',
                    color: '#e2e8f0',
                  }}
                />
                <Area type="monotone" dataKey="cpu" stroke="#3b82f6" strokeWidth={2} fill="url(#cpuGrad)" name="CPU %" />
                <Area type="monotone" dataKey="ram" stroke="#06b6d4" strokeWidth={2} fill="url(#ramGrad)" name="RAM %" />
                <Area type="monotone" dataKey="network" stroke="#f59e0b" strokeWidth={1.5} fill="url(#netGrad)" name="Network %" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </CardContent>
      </Card>

      {/* Services */}
      <Card className="border-border/40 bg-card/50">
        <CardHeader className="pb-3">
          <CardTitle className="text-sm font-medium">Service Health</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-2 sm:grid-cols-2">
            {services.map((service) => {
              const Icon = statusIcons[service.status];
              return (
                <div
                  key={service.name}
                  className="flex items-center justify-between rounded-lg border border-border/30 bg-muted/20 p-3 hover:bg-muted/30 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div className={cn('flex h-8 w-8 items-center justify-center rounded-lg border', statusColors[service.status])}>
                      <Icon className="h-4 w-4" />
                    </div>
                    <div>
                      <p className="text-sm font-medium">{service.name}</p>
                      <p className="text-[10px] text-muted-foreground">Port {service.port} &bull; {service.uptime}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <Badge variant="outline" className={cn('text-[9px]', statusColors[service.status])}>
                      {service.status}
                    </Badge>
                    <p className="text-[10px] text-muted-foreground mt-1">{service.memory}</p>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
