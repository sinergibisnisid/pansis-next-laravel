'use client';

import { useState } from 'react';
import { Plus, Cpu, Wifi, WifiOff, Signal, Activity } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/tables/data-table';
import { StatusBadge } from '@/components/shared/status-badge';
import { StatCard } from '@/components/cards/stat-card';
import { type ColumnDef } from '@tanstack/react-table';
import { Progress } from '@/components/ui/progress';
import type { Device } from '@/types';

const mockDevices: Device[] = [
  { id: 'd-001', name: 'Controller Vault BDG-01', type: 'controller', serialNumber: 'CTR-2024-001', model: 'PanCtrl v3', firmware: '3.2.1', ipAddress: '192.168.1.101', macAddress: 'AA:BB:CC:DD:01:01', branchId: 'br-001', branchName: 'KCP Bandung Utara', vaultId: 'v-001', status: 'online', signalQuality: 95, lastHeartbeat: '2024-03-15T09:30:00Z', mqttTopic: 'vault/bdg01/controller', installedAt: '2024-01-15', lastMaintenance: '2024-03-01', createdAt: '2024-01-15', updatedAt: '2024-03-15' },
  { id: 'd-002', name: 'Fingerprint BDG-01', type: 'fingerprint', serialNumber: 'FPR-2024-001', model: 'BioScan X5', firmware: '2.1.0', ipAddress: '192.168.1.102', macAddress: 'AA:BB:CC:DD:01:02', branchId: 'br-001', branchName: 'KCP Bandung Utara', vaultId: 'v-001', status: 'online', signalQuality: 88, lastHeartbeat: '2024-03-15T09:29:00Z', mqttTopic: 'vault/bdg01/fingerprint', installedAt: '2024-01-15', lastMaintenance: '2024-02-28', createdAt: '2024-01-15', updatedAt: '2024-03-15' },
  { id: 'd-003', name: 'Camera Vault BDG-02', type: 'camera', serialNumber: 'CAM-2024-001', model: 'SecureCam HD', firmware: '4.0.2', ipAddress: '192.168.2.101', macAddress: 'AA:BB:CC:DD:02:01', branchId: 'br-002', branchName: 'KCP Bandung Selatan', vaultId: 'v-002', status: 'online', signalQuality: 92, lastHeartbeat: '2024-03-15T09:30:00Z', mqttTopic: 'vault/bdg02/camera', installedAt: '2024-01-20', lastMaintenance: '2024-03-05', createdAt: '2024-01-20', updatedAt: '2024-03-15' },
  { id: 'd-004', name: 'Door Sensor CMH-01', type: 'sensor_door', serialNumber: 'DSR-2024-001', model: 'MagSense Pro', firmware: '1.5.3', ipAddress: '192.168.3.101', macAddress: 'AA:BB:CC:DD:03:01', branchId: 'br-003', branchName: 'KCP Cimahi', vaultId: 'v-003', status: 'online', signalQuality: 78, lastHeartbeat: '2024-03-15T09:28:00Z', mqttTopic: 'vault/cmh01/door', installedAt: '2024-02-01', lastMaintenance: null, createdAt: '2024-02-01', updatedAt: '2024-03-15' },
  { id: 'd-005', name: 'Controller Vault GRT-01', type: 'controller', serialNumber: 'CTR-2024-004', model: 'PanCtrl v3', firmware: '3.2.1', ipAddress: '192.168.4.101', macAddress: 'AA:BB:CC:DD:04:01', branchId: 'br-004', branchName: 'KCP Garut', vaultId: 'v-004', status: 'offline', signalQuality: 0, lastHeartbeat: '2024-03-14T17:00:00Z', mqttTopic: 'vault/grt01/controller', installedAt: '2024-02-10', lastMaintenance: '2024-03-01', createdAt: '2024-02-10', updatedAt: '2024-03-14' },
  { id: 'd-006', name: 'Alarm System SMD-01', type: 'alarm', serialNumber: 'ALM-2024-001', model: 'AlertMax 200', firmware: '2.3.0', ipAddress: '192.168.5.101', macAddress: 'AA:BB:CC:DD:05:01', branchId: 'br-005', branchName: 'KCP Sumedang', vaultId: 'v-005', status: 'online', signalQuality: 90, lastHeartbeat: '2024-03-15T09:30:00Z', mqttTopic: 'vault/smd01/alarm', installedAt: '2024-02-15', lastMaintenance: '2024-03-10', createdAt: '2024-02-15', updatedAt: '2024-03-15' },
  { id: 'd-007', name: 'Motion Sensor CJR-01', type: 'sensor_motion', serialNumber: 'MSR-2024-001', model: 'MotionEye v2', firmware: '1.2.1', ipAddress: '192.168.7.102', macAddress: 'AA:BB:CC:DD:07:02', branchId: 'br-007', branchName: 'KCP Cianjur', vaultId: 'v-007', status: 'warning', signalQuality: 45, lastHeartbeat: '2024-03-15T09:15:00Z', mqttTopic: 'vault/cjr01/motion', installedAt: '2024-03-01', lastMaintenance: null, createdAt: '2024-03-01', updatedAt: '2024-03-15' },
  { id: 'd-008', name: 'Electronic Lock TSK-01', type: 'lock', serialNumber: 'LCK-2024-001', model: 'SecureLock X1', firmware: '3.0.0', ipAddress: '192.168.6.103', macAddress: 'AA:BB:CC:DD:06:03', branchId: 'br-006', branchName: 'KCP Tasikmalaya', vaultId: 'v-006', status: 'online', signalQuality: 97, lastHeartbeat: '2024-03-15T09:30:00Z', mqttTopic: 'vault/tsk01/lock', installedAt: '2024-02-20', lastMaintenance: '2024-03-12', createdAt: '2024-02-20', updatedAt: '2024-03-15' },
];

const columns: ColumnDef<Device>[] = [
  {
    accessorKey: 'name',
    header: 'Device',
    cell: ({ row }) => (
      <div className="flex items-center gap-3">
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-500/10 border border-cyan-500/20">
          <Cpu className="h-4 w-4 text-cyan-400" />
        </div>
        <div>
          <p className="text-sm font-medium">{row.original.name}</p>
          <p className="text-xs text-muted-foreground">{row.original.serialNumber}</p>
        </div>
      </div>
    ),
  },
  {
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => (
      <span className="text-xs capitalize px-2 py-1 rounded-md bg-muted/50">
        {row.original.type.replace('_', ' ')}
      </span>
    ),
  },
  {
    accessorKey: 'branchName',
    header: 'Branch',
    cell: ({ row }) => <span className="text-sm">{row.original.branchName}</span>,
  },
  {
    accessorKey: 'ipAddress',
    header: 'IP Address',
    cell: ({ row }) => (
      <span className="text-xs font-mono text-muted-foreground">{row.original.ipAddress}</span>
    ),
  },
  {
    accessorKey: 'signalQuality',
    header: 'Signal',
    cell: ({ row }) => (
      <div className="flex items-center gap-2 w-24">
        <Progress value={row.original.signalQuality} className="h-1.5" />
        <span className="text-xs text-muted-foreground">{row.original.signalQuality}%</span>
      </div>
    ),
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => (
      <StatusBadge
        status={row.original.status}
        type="device"
        pulse={row.original.status === 'online'}
      />
    ),
  },
];

export default function DevicesPage() {
  const [devices] = useState(mockDevices);

  const onlineCount = devices.filter((d) => d.status === 'online').length;
  const offlineCount = devices.filter((d) => d.status === 'offline').length;
  const warningCount = devices.filter((d) => d.status === 'warning').length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between gap-2">
        <div className="min-w-0">
          <h1 className="text-xl sm:text-2xl font-bold tracking-tight">Device Management</h1>
          <p className="text-xs sm:text-sm text-muted-foreground mt-1">
            Monitor and manage all connected devices
          </p>
        </div>
        <Button size="sm" className="gap-2 shrink-0 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
          <Plus className="h-4 w-4" />
          <span className="hidden sm:inline">Add Device</span>
        </Button>
      </div>

      {/* Stats */}
      <div className="grid gap-3 grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total Devices" value={devices.length} icon={Cpu} variant="info" subtitle="Registered devices" />
        <StatCard title="Online" value={onlineCount} icon={Wifi} variant="success" pulse subtitle="Connected & active" />
        <StatCard title="Offline" value={offlineCount} icon={WifiOff} variant="danger" subtitle="Not responding" />
        <StatCard title="Warning" value={warningCount} icon={Signal} variant="warning" subtitle="Needs attention" />
      </div>

      {/* Table */}
      <DataTable
        columns={columns}
        data={devices}
        searchKey="name"
        searchPlaceholder="Search devices..."
      />
    </div>
  );
}
