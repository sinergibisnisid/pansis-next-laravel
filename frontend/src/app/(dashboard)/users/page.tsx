'use client';

import { useState } from 'react';
import { Plus, UserPlus, Shield, Mail } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/tables/data-table';
import { StatusBadge } from '@/components/shared/status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { type ColumnDef } from '@tanstack/react-table';
import { getInitials } from '@/lib/utils';
import type { User } from '@/types';

const mockUsers: User[] = [
  { id: '1', username: 'admin', email: 'admin@bankbjb.co.id', fullName: 'Administrator', role: 'super_admin', permissions: [], branchId: 'hq-001', branchName: 'Kantor Pusat', status: 'active', lastLogin: '2024-03-15T09:00:00Z', createdAt: '2024-01-01', updatedAt: '2024-03-15' },
  { id: '2', username: 'budi.santoso', email: 'budi@bankbjb.co.id', fullName: 'Budi Santoso', role: 'operator', permissions: [], branchId: 'br-002', branchName: 'KCP Bandung Selatan', status: 'active', lastLogin: '2024-03-15T09:15:00Z', createdAt: '2024-01-15', updatedAt: '2024-03-15' },
  { id: '3', username: 'siti.rahayu', email: 'siti@bankbjb.co.id', fullName: 'Siti Rahayu', role: 'operator', permissions: [], branchId: 'br-006', branchName: 'KCP Tasikmalaya', status: 'active', lastLogin: '2024-03-15T09:05:00Z', createdAt: '2024-01-20', updatedAt: '2024-03-15' },
  { id: '4', username: 'ahmad.hidayat', email: 'ahmad@bankbjb.co.id', fullName: 'Ahmad Hidayat', role: 'admin', permissions: [], branchId: 'hq-001', branchName: 'Kantor Pusat', status: 'active', lastLogin: '2024-03-15T08:30:00Z', createdAt: '2024-02-01', updatedAt: '2024-03-14' },
  { id: '5', username: 'dewi.lestari', email: 'dewi@bankbjb.co.id', fullName: 'Dewi Lestari', role: 'viewer', permissions: [], branchId: 'br-001', branchName: 'KCP Bandung Utara', status: 'active', lastLogin: '2024-03-14T16:00:00Z', createdAt: '2024-02-10', updatedAt: '2024-03-14' },
  { id: '6', username: 'rudi.hermawan', email: 'rudi@bankbjb.co.id', fullName: 'Rudi Hermawan', role: 'operator', permissions: [], branchId: 'br-004', branchName: 'KCP Garut', status: 'inactive', lastLogin: '2024-03-10T10:00:00Z', createdAt: '2024-02-15', updatedAt: '2024-03-10' },
  { id: '7', username: 'nina.marlina', email: 'nina@bankbjb.co.id', fullName: 'Nina Marlina', role: 'auditor', permissions: [], branchId: 'hq-001', branchName: 'Kantor Pusat', status: 'active', lastLogin: '2024-03-15T07:45:00Z', createdAt: '2024-02-20', updatedAt: '2024-03-15' },
  { id: '8', username: 'eko.prasetyo', email: 'eko@bankbjb.co.id', fullName: 'Eko Prasetyo', role: 'operator', permissions: [], branchId: 'br-005', branchName: 'KCP Sumedang', status: 'suspended', lastLogin: '2024-03-01T09:00:00Z', createdAt: '2024-03-01', updatedAt: '2024-03-05' },
];

const roleColors: Record<string, string> = {
  super_admin: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
  admin: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
  operator: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
  viewer: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
  auditor: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
};

const columns: ColumnDef<User>[] = [
  {
    accessorKey: 'fullName',
    header: 'User',
    cell: ({ row }) => (
      <div className="flex items-center gap-3">
        <Avatar className="h-9 w-9 border border-border/40">
          <AvatarFallback className="bg-gradient-to-br from-blue-600/80 to-cyan-500/80 text-[10px] font-bold text-white">
            {getInitials(row.original.fullName)}
          </AvatarFallback>
        </Avatar>
        <div>
          <p className="text-sm font-medium">{row.original.fullName}</p>
          <p className="text-xs text-muted-foreground flex items-center gap-1">
            <Mail className="h-3 w-3" />
            {row.original.email}
          </p>
        </div>
      </div>
    ),
  },
  {
    accessorKey: 'role',
    header: 'Role',
    cell: ({ row }) => (
      <Badge variant="outline" className={roleColors[row.original.role]}>
        <Shield className="h-3 w-3 mr-1" />
        {row.original.role.replace('_', ' ')}
      </Badge>
    ),
  },
  {
    accessorKey: 'branchName',
    header: 'Branch',
    cell: ({ row }) => <span className="text-sm">{row.original.branchName}</span>,
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => (
      <StatusBadge
        status={row.original.status}
        type="device"
        label={row.original.status}
        pulse={row.original.status === 'active'}
      />
    ),
  },
  {
    accessorKey: 'lastLogin',
    header: 'Last Login',
    cell: ({ row }) => (
      <span className="text-xs text-muted-foreground">
        {row.original.lastLogin
          ? new Date(row.original.lastLogin).toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
            })
          : 'Never'}
      </span>
    ),
  },
];

export default function UsersPage() {
  const [users] = useState(mockUsers);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">User Management</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Manage users, roles, and permissions
          </p>
        </div>
        <Button className="gap-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
          <UserPlus className="h-4 w-4" />
          Add User
        </Button>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-4">
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Total Users</p>
          <p className="text-2xl font-bold mt-1">{users.length}</p>
        </div>
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Active</p>
          <p className="text-2xl font-bold mt-1 text-emerald-400">{users.filter((u) => u.status === 'active').length}</p>
        </div>
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Operators</p>
          <p className="text-2xl font-bold mt-1">{users.filter((u) => u.role === 'operator').length}</p>
        </div>
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Admins</p>
          <p className="text-2xl font-bold mt-1">{users.filter((u) => u.role === 'admin' || u.role === 'super_admin').length}</p>
        </div>
      </div>

      {/* Table */}
      <DataTable
        columns={columns}
        data={users}
        searchKey="fullName"
        searchPlaceholder="Search users..."
      />
    </div>
  );
}
