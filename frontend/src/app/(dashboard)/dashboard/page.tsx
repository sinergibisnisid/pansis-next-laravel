'use client';

import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import {
  Building2,
  Vault,
  AlertTriangle,
  Cpu,
  Users,
  Activity,
  Radio,
  Server,
} from 'lucide-react';
import { StatCard } from '@/components/cards/stat-card';
import { ChartCard } from '@/components/cards/chart-card';
import { LoadingSkeleton } from '@/components/shared/loading-skeleton';
import { ActivityTimeline } from './components/activity-timeline';
import { VaultStatusChart } from './components/vault-status-chart';
import { BranchActivityChart } from './components/branch-activity-chart';
import { RealtimeLineChart } from './components/realtime-line-chart';
import { SystemHealth } from './components/system-health';
import type { DashboardStats, ActivityEvent } from '@/types';
import { monitoringService } from '@/services';

export default function DashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [activities, setActivities] = useState<ActivityEvent[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [statsData, activitiesData] = await Promise.all([
          monitoringService.getStats(),
          monitoringService.getActivities(),
        ]);
        setStats(statsData);
        setActivities(activitiesData.data);
      } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, []);

  if (isLoading) {
    return (
      <div className="space-y-6">
        <LoadingSkeleton variant="card" count={8} />
        <div className="grid gap-6 lg:grid-cols-2">
          <LoadingSkeleton variant="chart" />
          <LoadingSkeleton variant="chart" />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-sm text-muted-foreground mt-1">
          Realtime monitoring overview - PANSIN ACCESS System
        </p>
      </div>

      {/* Stats Cards */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ staggerChildren: 0.1 }}
        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
      >
        <StatCard
          title="Total Branch"
          value={stats?.totalBranches ?? 0}
          subtitle="Active branches"
          icon={Building2}
          variant="info"
        />
        <StatCard
          title="Active Vault"
          value={stats?.activeVaults ?? 0}
          subtitle="Monitored vaults"
          icon={Vault}
          variant="success"
          pulse
        />
        <StatCard
          title="Active Alarm"
          value={stats?.activeAlarms ?? 0}
          subtitle="Requires attention"
          icon={AlertTriangle}
          variant="danger"
          pulse={!!stats?.activeAlarms}
        />
        <StatCard
          title="Online Device"
          value={`${stats?.onlineDevices ?? 0}/${stats?.totalDevices ?? 0}`}
          subtitle={`${stats ? Math.round((stats.onlineDevices / stats.totalDevices) * 100) : 0}% online`}
          icon={Cpu}
          variant="success"
        />
        <StatCard
          title="Active Users"
          value={stats?.activeUsers ?? 0}
          subtitle="Currently online"
          icon={Users}
          variant="info"
        />
        <StatCard
          title="Today Activity"
          value={stats?.todayActivities ?? 0}
          subtitle="Events recorded"
          icon={Activity}
          variant="default"
          trend={{ value: 12, isPositive: true }}
        />
        <StatCard
          title="MQTT Connection"
          value={stats?.mqttConnections ?? 0}
          subtitle="Active connections"
          icon={Radio}
          variant="success"
          pulse
        />
        <StatCard
          title="Server Status"
          value={stats?.serverStatus === 'healthy' ? 'Healthy' : stats?.serverStatus ?? 'N/A'}
          subtitle="All services running"
          icon={Server}
          variant={stats?.serverStatus === 'healthy' ? 'success' : 'warning'}
        />
      </motion.div>

      {/* Charts Row 1 */}
      <div className="grid gap-6 lg:grid-cols-7">
        <div className="lg:col-span-4">
          <ChartCard title="Branch Activity" subtitle="Vault access frequency per branch">
            <BranchActivityChart />
          </ChartCard>
        </div>
        <div className="lg:col-span-3">
          <ChartCard title="Vault Status" subtitle="Current vault status distribution">
            <VaultStatusChart />
          </ChartCard>
        </div>
      </div>

      {/* Charts Row 2 */}
      <div className="grid gap-6 lg:grid-cols-7">
        <div className="lg:col-span-4">
          <ChartCard title="Realtime Activity" subtitle="Events per hour (last 24h)">
            <RealtimeLineChart />
          </ChartCard>
        </div>
        <div className="lg:col-span-3">
          <SystemHealth />
        </div>
      </div>

      {/* Activity Timeline */}
      <ChartCard title="Live Activity Feed" subtitle="Recent system events">
        <ActivityTimeline activities={activities} />
      </ChartCard>
    </div>
  );
}
