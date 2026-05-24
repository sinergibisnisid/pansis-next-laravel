'use client';

import { useState } from 'react';
import { FileText, Download, Calendar, Filter } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/tables/data-table';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { type ColumnDef } from '@tanstack/react-table';
import { cn } from '@/lib/utils';
import type { AuditLog } from '@/types';

const mockAuditLogs: AuditLog[] = [
  { id: 'log-001', timestamp: '2024-03-15T09:20:00Z', userId: 'u-001', userName: 'Administrator', branchId: 'hq-001', branchName: 'Kantor Pusat', action: 'User Login', category: 'auth', details: 'Login successful from IP 192.168.1.100', ipAddress: '192.168.1.100', severity: 'info' },
  { id: 'log-002', timestamp: '2024-03-15T09:15:00Z', userId: 'u-002', userName: 'Budi Santoso', branchId: 'br-002', branchName: 'KCP Bandung Selatan', action: 'Vault Opened', category: 'vault_access', details: 'Vault opened with fingerprint authentication', ipAddress: '192.168.2.50', severity: 'info' },
  { id: 'log-003', timestamp: '2024-03-15T09:10:00Z', userId: 'system', userName: 'System', branchId: 'br-003', branchName: 'KCP Cimahi', action: 'Alarm Triggered', category: 'alarm', details: 'Motion sensor triggered - unauthorized movement detected', ipAddress: '192.168.3.1', severity: 'critical' },
  { id: 'log-004', timestamp: '2024-03-15T09:05:00Z', userId: 'u-003', userName: 'Siti Rahayu', branchId: 'br-006', branchName: 'KCP Tasikmalaya', action: 'Vault Opened', category: 'vault_access', details: 'Vault opened with fingerprint + PIN verification', ipAddress: '192.168.6.50', severity: 'info' },
  { id: 'log-005', timestamp: '2024-03-15T08:45:00Z', userId: 'system', userName: 'System', branchId: 'br-004', branchName: 'KCP Garut', action: 'Device Offline', category: 'device', details: 'Controller CTR-2024-004 lost connection', ipAddress: '192.168.4.101', severity: 'warning' },
  { id: 'log-006', timestamp: '2024-03-15T08:30:00Z', userId: 'u-004', userName: 'Ahmad Hidayat', branchId: 'hq-001', branchName: 'Kantor Pusat', action: 'Configuration Changed', category: 'configuration', details: 'MQTT broker settings updated', ipAddress: '192.168.1.105', severity: 'warning' },
  { id: 'log-007', timestamp: '2024-03-15T08:00:00Z', userId: 'system', userName: 'System', branchId: 'br-001', branchName: 'KCP Bandung Utara', action: 'Vault Closed', category: 'vault_access', details: 'Vault auto-locked after timeout', ipAddress: '192.168.1.101', severity: 'info' },
  { id: 'log-008', timestamp: '2024-03-15T07:45:00Z', userId: 'u-007', userName: 'Nina Marlina', branchId: 'hq-001', branchName: 'Kantor Pusat', action: 'Report Generated', category: 'system', details: 'Daily report generated and sent via email', ipAddress: '192.168.1.110', severity: 'info' },
  { id: 'log-009', timestamp: '2024-03-15T06:00:00Z', userId: 'system', userName: 'System', branchId: 'br-007', branchName: 'KCP Cianjur', action: 'Maintenance Started', category: 'maintenance', details: 'Scheduled maintenance for vault inspection', ipAddress: '192.168.7.1', severity: 'info' },
  { id: 'log-010', timestamp: '2024-03-14T23:00:00Z', userId: 'system', userName: 'System', branchId: 'hq-001', branchName: 'All Branches', action: 'System Backup', category: 'system', details: 'Nightly backup completed successfully', ipAddress: '10.0.0.1', severity: 'info' },
];

const severityColors: Record<string, string> = {
  info: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
  warning: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
  critical: 'bg-red-500/20 text-red-400 border-red-500/30',
};

const categoryColors: Record<string, string> = {
  auth: 'bg-purple-500/20 text-purple-400',
  vault_access: 'bg-emerald-500/20 text-emerald-400',
  alarm: 'bg-red-500/20 text-red-400',
  device: 'bg-cyan-500/20 text-cyan-400',
  system: 'bg-slate-500/20 text-slate-400',
  maintenance: 'bg-amber-500/20 text-amber-400',
  configuration: 'bg-blue-500/20 text-blue-400',
};

const columns: ColumnDef<AuditLog>[] = [
  {
    accessorKey: 'timestamp',
    header: 'Time',
    cell: ({ row }) => (
      <span className="text-xs font-mono text-muted-foreground">
        {new Date(row.original.timestamp).toLocaleString('id-ID', {
          day: '2-digit',
          month: 'short',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
        })}
      </span>
    ),
  },
  {
    accessorKey: 'severity',
    header: 'Level',
    cell: ({ row }) => (
      <Badge variant="outline" className={cn('text-[10px]', severityColors[row.original.severity])}>
        {row.original.severity}
      </Badge>
    ),
  },
  {
    accessorKey: 'action',
    header: 'Action',
    cell: ({ row }) => <span className="text-sm font-medium">{row.original.action}</span>,
  },
  {
    accessorKey: 'category',
    header: 'Category',
    cell: ({ row }) => (
      <span className={cn('text-[10px] px-2 py-0.5 rounded-full font-medium', categoryColors[row.original.category])}>
        {row.original.category.replace('_', ' ')}
      </span>
    ),
  },
  {
    accessorKey: 'userName',
    header: 'User',
    cell: ({ row }) => <span className="text-sm">{row.original.userName}</span>,
  },
  {
    accessorKey: 'branchName',
    header: 'Branch',
    cell: ({ row }) => <span className="text-xs text-muted-foreground">{row.original.branchName}</span>,
  },
  {
    accessorKey: 'details',
    header: 'Details',
    cell: ({ row }) => (
      <span className="text-xs text-muted-foreground max-w-[200px] truncate block">
        {row.original.details}
      </span>
    ),
  },
];

export default function ReportsPage() {
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [severityFilter, setSeverityFilter] = useState('all');

  const filteredLogs = mockAuditLogs.filter((log) => {
    const matchesCategory = categoryFilter === 'all' || log.category === categoryFilter;
    const matchesSeverity = severityFilter === 'all' || log.severity === severityFilter;
    return matchesCategory && matchesSeverity;
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div className="min-w-0">
          <h1 className="text-xl sm:text-2xl font-bold tracking-tight">Reports & Audit Log</h1>
          <p className="text-xs sm:text-sm text-muted-foreground mt-1">
            System audit trail and activity reports
          </p>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <Button variant="outline" size="sm" className="gap-1.5 text-xs">
            <Download className="h-3.5 w-3.5" />
            PDF
          </Button>
          <Button variant="outline" size="sm" className="gap-1.5 text-xs">
            <Download className="h-3.5 w-3.5" />
            Excel
          </Button>
          <Button variant="outline" size="sm" className="gap-1.5 text-xs">
            <Download className="h-3.5 w-3.5" />
            CSV
          </Button>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-2 sm:gap-3">
        <Select value={categoryFilter} onValueChange={(v) => setCategoryFilter(v ?? 'all')}>
          <SelectTrigger className="w-[140px] sm:w-[160px] bg-background/50 border-border/40 text-xs sm:text-sm">
            <Filter className="h-3.5 w-3.5 mr-1.5 text-muted-foreground" />
            <SelectValue placeholder="Category" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Categories</SelectItem>
            <SelectItem value="auth">Authentication</SelectItem>
            <SelectItem value="vault_access">Vault Access</SelectItem>
            <SelectItem value="alarm">Alarm</SelectItem>
            <SelectItem value="device">Device</SelectItem>
            <SelectItem value="system">System</SelectItem>
            <SelectItem value="maintenance">Maintenance</SelectItem>
            <SelectItem value="configuration">Configuration</SelectItem>
          </SelectContent>
        </Select>

        <Select value={severityFilter} onValueChange={(v) => setSeverityFilter(v ?? 'all')}>
          <SelectTrigger className="w-[120px] sm:w-[140px] bg-background/50 border-border/40 text-xs sm:text-sm">
            <SelectValue placeholder="Severity" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Levels</SelectItem>
            <SelectItem value="info">Info</SelectItem>
            <SelectItem value="warning">Warning</SelectItem>
            <SelectItem value="critical">Critical</SelectItem>
          </SelectContent>
        </Select>

        <Button variant="outline" size="sm" className="gap-1.5 border-border/40 text-xs">
          <Calendar className="h-3.5 w-3.5" />
          <span className="hidden sm:inline">Date Range</span>
        </Button>
      </div>

      {/* Table */}
      <DataTable
        columns={columns}
        data={filteredLogs}
        searchKey="action"
        searchPlaceholder="Search audit logs..."
      />
    </div>
  );
}
