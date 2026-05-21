'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Wrench,
  Calendar,
  CheckCircle,
  Clock,
  AlertCircle,
  Plus,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { MaintenanceSchedule } from '@/types';

const mockSchedules: MaintenanceSchedule[] = [
  { id: 'm-001', branchId: 'br-001', branchName: 'KCP Bandung Utara', vaultId: 'v-001', vaultName: 'Vault Utama', type: 'inspection', status: 'completed', scheduledDate: '2024-03-10T09:00:00Z', completedDate: '2024-03-10T11:30:00Z', assignedTo: 'Teknisi A', notes: 'Inspeksi rutin bulanan', createdAt: '2024-03-01' },
  { id: 'm-002', branchId: 'br-002', branchName: 'KCP Bandung Selatan', vaultId: 'v-002', vaultName: 'Vault Utama', type: 'cleaning', status: 'scheduled', scheduledDate: '2024-03-20T09:00:00Z', completedDate: null, assignedTo: 'Teknisi B', notes: 'Pembersihan sensor dan kamera', createdAt: '2024-03-05' },
  { id: 'm-003', branchId: 'br-003', branchName: 'KCP Cimahi', vaultId: 'v-003', vaultName: 'Vault Utama', type: 'repair', status: 'in_progress', scheduledDate: '2024-03-15T08:00:00Z', completedDate: null, assignedTo: 'Teknisi C', notes: 'Perbaikan motion sensor yang error', createdAt: '2024-03-12' },
  { id: 'm-004', branchId: 'br-005', branchName: 'KCP Sumedang', vaultId: 'v-005', vaultName: 'Vault Utama', type: 'lubrication', status: 'scheduled', scheduledDate: '2024-03-22T10:00:00Z', completedDate: null, assignedTo: 'Teknisi A', notes: 'Pelumasan mekanisme pintu vault', createdAt: '2024-03-08' },
  { id: 'm-005', branchId: 'br-007', branchName: 'KCP Cianjur', vaultId: 'v-007', vaultName: 'Vault Utama', type: 'inspection', status: 'in_progress', scheduledDate: '2024-03-15T06:00:00Z', completedDate: null, assignedTo: 'Teknisi D', notes: 'Inspeksi dan kalibrasi sensor', createdAt: '2024-03-10' },
  { id: 'm-006', branchId: 'br-004', branchName: 'KCP Garut', vaultId: 'v-004', vaultName: 'Vault Utama', type: 'repair', status: 'overdue', scheduledDate: '2024-03-12T09:00:00Z', completedDate: null, assignedTo: 'Teknisi B', notes: 'Perbaikan controller yang offline', createdAt: '2024-03-05' },
  { id: 'm-007', branchId: 'br-006', branchName: 'KCP Tasikmalaya', vaultId: 'v-006', vaultName: 'Vault Utama', type: 'calibration', status: 'completed', scheduledDate: '2024-03-08T09:00:00Z', completedDate: '2024-03-08T12:00:00Z', assignedTo: 'Teknisi C', notes: 'Kalibrasi fingerprint scanner', createdAt: '2024-03-01' },
  { id: 'm-008', branchId: 'br-008', branchName: 'KCP Sukabumi', vaultId: 'v-008', vaultName: 'Vault Utama', type: 'cleaning', status: 'completed', scheduledDate: '2024-03-05T09:00:00Z', completedDate: '2024-03-05T10:30:00Z', assignedTo: 'Teknisi A', notes: 'Pembersihan rutin', createdAt: '2024-02-28' },
];

const statusConfig: Record<string, { color: string; icon: typeof CheckCircle; label: string }> = {
  scheduled: { color: 'bg-blue-500/20 text-blue-400 border-blue-500/30', icon: Calendar, label: 'Scheduled' },
  in_progress: { color: 'bg-amber-500/20 text-amber-400 border-amber-500/30', icon: Clock, label: 'In Progress' },
  completed: { color: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30', icon: CheckCircle, label: 'Completed' },
  overdue: { color: 'bg-red-500/20 text-red-400 border-red-500/30', icon: AlertCircle, label: 'Overdue' },
  cancelled: { color: 'bg-slate-500/20 text-slate-400 border-slate-500/30', icon: AlertCircle, label: 'Cancelled' },
};

const typeColors: Record<string, string> = {
  cleaning: 'bg-cyan-500/20 text-cyan-400',
  lubrication: 'bg-amber-500/20 text-amber-400',
  inspection: 'bg-blue-500/20 text-blue-400',
  repair: 'bg-red-500/20 text-red-400',
  calibration: 'bg-purple-500/20 text-purple-400',
};

export default function MaintenancePage() {
  const [filter, setFilter] = useState<string>('all');

  const filtered = filter === 'all' ? mockSchedules : mockSchedules.filter((s) => s.status === filter);

  const counts = {
    all: mockSchedules.length,
    scheduled: mockSchedules.filter((s) => s.status === 'scheduled').length,
    in_progress: mockSchedules.filter((s) => s.status === 'in_progress').length,
    completed: mockSchedules.filter((s) => s.status === 'completed').length,
    overdue: mockSchedules.filter((s) => s.status === 'overdue').length,
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Maintenance</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Schedule and track vault maintenance activities
          </p>
        </div>
        <Button className="gap-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
          <Plus className="h-4 w-4" />
          Schedule Maintenance
        </Button>
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-2 overflow-x-auto pb-1">
        {Object.entries(counts).map(([key, count]) => (
          <Button
            key={key}
            variant={filter === key ? 'secondary' : 'ghost'}
            size="sm"
            className="gap-2 whitespace-nowrap"
            onClick={() => setFilter(key)}
          >
            {key === 'all' ? 'All' : key.replace('_', ' ')}
            <Badge variant="outline" className="h-5 min-w-[20px] text-[10px]">
              {count}
            </Badge>
          </Button>
        ))}
      </div>

      {/* Timeline */}
      <div className="space-y-3">
        {filtered.map((schedule, index) => {
          const config = statusConfig[schedule.status];
          const StatusIcon = config.icon;

          return (
            <motion.div
              key={schedule.id}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: index * 0.05 }}
              className={cn(
                'relative flex gap-4 rounded-xl border p-4 transition-all hover:bg-muted/20',
                schedule.status === 'overdue'
                  ? 'border-red-500/30 bg-red-500/5'
                  : 'border-border/40 bg-card/50'
              )}
            >
              {/* Status Icon */}
              <div className={cn('flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border', config.color)}>
                <StatusIcon className="h-5 w-5" />
              </div>

              {/* Content */}
              <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <p className="text-sm font-medium">{schedule.branchName}</p>
                    <p className="text-xs text-muted-foreground mt-0.5">{schedule.vaultName}</p>
                  </div>
                  <Badge variant="outline" className={cn('text-[10px] shrink-0', config.color)}>
                    {config.label}
                  </Badge>
                </div>

                <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                  <span className={cn('px-2 py-0.5 rounded-full font-medium', typeColors[schedule.type])}>
                    {schedule.type}
                  </span>
                  <span className="flex items-center gap-1">
                    <Calendar className="h-3 w-3" />
                    {new Date(schedule.scheduledDate).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                    })}
                  </span>
                  <span className="flex items-center gap-1">
                    <Wrench className="h-3 w-3" />
                    {schedule.assignedTo}
                  </span>
                </div>

                {schedule.notes && (
                  <p className="mt-2 text-xs text-muted-foreground/80">{schedule.notes}</p>
                )}
              </div>
            </motion.div>
          );
        })}
      </div>
    </div>
  );
}
