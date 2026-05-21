'use client';

import { cn } from '@/lib/utils';
import { Progress } from '@/components/ui/progress';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, AlertCircle, XCircle } from 'lucide-react';

const services = [
  { name: 'API Server', status: 'healthy' as const, responseTime: 45, uptime: 99.9 },
  { name: 'WebSocket Server', status: 'healthy' as const, responseTime: 12, uptime: 99.8 },
  { name: 'MQTT Broker', status: 'healthy' as const, responseTime: 8, uptime: 99.9 },
  { name: 'Database', status: 'healthy' as const, responseTime: 23, uptime: 99.7 },
  { name: 'Redis Cache', status: 'degraded' as const, responseTime: 156, uptime: 98.5 },
  { name: 'Stream Server', status: 'healthy' as const, responseTime: 34, uptime: 99.6 },
];

const metrics = [
  { label: 'CPU Usage', value: 34, max: 100, color: 'bg-blue-500' },
  { label: 'RAM Usage', value: 62, max: 100, color: 'bg-cyan-500' },
  { label: 'Storage', value: 45, max: 100, color: 'bg-emerald-500' },
  { label: 'Network', value: 28, max: 100, color: 'bg-amber-500' },
];

const statusIcons = {
  healthy: CheckCircle,
  degraded: AlertCircle,
  down: XCircle,
};

const statusColors = {
  healthy: 'text-emerald-400',
  degraded: 'text-amber-400',
  down: 'text-red-400',
};

export function SystemHealth() {
  return (
    <Card className="border-border/40 bg-card/50 backdrop-blur-sm">
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium">System Health</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Resource Metrics */}
        <div className="space-y-3">
          {metrics.map((metric) => (
            <div key={metric.label} className="space-y-1.5">
              <div className="flex items-center justify-between">
                <span className="text-xs text-muted-foreground">{metric.label}</span>
                <span className="text-xs font-medium">{metric.value}%</span>
              </div>
              <Progress value={metric.value} className="h-1.5" />
            </div>
          ))}
        </div>

        {/* Services */}
        <div className="border-t border-border/40 pt-3">
          <p className="text-xs font-medium text-muted-foreground mb-2">Services</p>
          <div className="space-y-2">
            {services.map((service) => {
              const Icon = statusIcons[service.status];
              return (
                <div
                  key={service.name}
                  className="flex items-center justify-between rounded-md px-2 py-1.5 hover:bg-muted/30 transition-colors"
                >
                  <div className="flex items-center gap-2">
                    <Icon className={cn('h-3.5 w-3.5', statusColors[service.status])} />
                    <span className="text-xs">{service.name}</span>
                  </div>
                  <span className="text-[10px] text-muted-foreground">
                    {service.responseTime}ms
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
