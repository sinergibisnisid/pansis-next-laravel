'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, Building2, Search, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DataTable } from '@/components/tables/data-table';
import { StatusBadge } from '@/components/shared/status-badge';
import { type ColumnDef } from '@tanstack/react-table';
import type { Organization } from '@/types';

const mockOrganizations: Organization[] = [
  { id: '1', name: 'Kantor Pusat Bank BJB', code: 'HQ-001', type: 'headquarters', address: 'Jl. Naripan No.12-14', city: 'Bandung', province: 'Jawa Barat', phone: '022-4234868', email: 'pusat@bankbjb.co.id', status: 'active', totalVaults: 2, totalDevices: 16, createdAt: '2024-01-01', updatedAt: '2024-03-15' },
  { id: '2', name: 'KCP Bandung Utara', code: 'BDG-01', type: 'branch', parentId: '1', address: 'Jl. Ir. H. Juanda No.45', city: 'Bandung', province: 'Jawa Barat', phone: '022-2500123', email: 'bdg01@bankbjb.co.id', status: 'active', totalVaults: 1, totalDevices: 8, createdAt: '2024-01-15', updatedAt: '2024-03-10' },
  { id: '3', name: 'KCP Bandung Selatan', code: 'BDG-02', type: 'branch', parentId: '1', address: 'Jl. Soekarno-Hatta No.100', city: 'Bandung', province: 'Jawa Barat', phone: '022-7500456', email: 'bdg02@bankbjb.co.id', status: 'active', totalVaults: 1, totalDevices: 8, createdAt: '2024-01-20', updatedAt: '2024-03-12' },
  { id: '4', name: 'KCP Cimahi', code: 'CMH-01', type: 'branch', parentId: '1', address: 'Jl. Raya Cimahi No.25', city: 'Cimahi', province: 'Jawa Barat', phone: '022-6600789', email: 'cmh01@bankbjb.co.id', status: 'active', totalVaults: 1, totalDevices: 8, createdAt: '2024-02-01', updatedAt: '2024-03-14' },
  { id: '5', name: 'KCP Garut', code: 'GRT-01', type: 'branch', parentId: '1', address: 'Jl. Ahmad Yani No.50', city: 'Garut', province: 'Jawa Barat', phone: '0262-231456', email: 'grt01@bankbjb.co.id', status: 'inactive', totalVaults: 1, totalDevices: 8, createdAt: '2024-02-10', updatedAt: '2024-03-01' },
  { id: '6', name: 'KCP Sumedang', code: 'SMD-01', type: 'branch', parentId: '1', address: 'Jl. Mayor Abdurachman No.15', city: 'Sumedang', province: 'Jawa Barat', phone: '0261-201234', email: 'smd01@bankbjb.co.id', status: 'active', totalVaults: 1, totalDevices: 8, createdAt: '2024-02-15', updatedAt: '2024-03-13' },
  { id: '7', name: 'KCP Tasikmalaya', code: 'TSK-01', type: 'branch', parentId: '1', address: 'Jl. HZ Mustofa No.30', city: 'Tasikmalaya', province: 'Jawa Barat', phone: '0265-331567', email: 'tsk01@bankbjb.co.id', status: 'active', totalVaults: 1, totalDevices: 8, createdAt: '2024-02-20', updatedAt: '2024-03-11' },
  { id: '8', name: 'KCP Cianjur', code: 'CJR-01', type: 'branch', parentId: '1', address: 'Jl. Dr. Muwardi No.20', city: 'Cianjur', province: 'Jawa Barat', phone: '0263-261890', email: 'cjr01@bankbjb.co.id', status: 'active', totalVaults: 1, totalDevices: 6, createdAt: '2024-03-01', updatedAt: '2024-03-15' },
];

const columns: ColumnDef<Organization>[] = [
  {
    accessorKey: 'name',
    header: 'Organization',
    cell: ({ row }) => (
      <div className="flex items-center gap-3">
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/10 border border-blue-500/20">
          <Building2 className="h-4 w-4 text-blue-400" />
        </div>
        <div>
          <p className="text-sm font-medium">{row.original.name}</p>
          <p className="text-xs text-muted-foreground">{row.original.code}</p>
        </div>
      </div>
    ),
  },
  {
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => (
      <span className="text-xs capitalize px-2 py-1 rounded-md bg-muted/50">
        {row.original.type === 'headquarters' ? 'HQ' : 'Branch'}
      </span>
    ),
  },
  {
    accessorKey: 'city',
    header: 'Location',
    cell: ({ row }) => (
      <div className="flex items-center gap-1.5 text-sm">
        <MapPin className="h-3.5 w-3.5 text-muted-foreground" />
        {row.original.city}
      </div>
    ),
  },
  {
    accessorKey: 'totalVaults',
    header: 'Vaults',
    cell: ({ row }) => <span className="text-sm">{row.original.totalVaults}</span>,
  },
  {
    accessorKey: 'totalDevices',
    header: 'Devices',
    cell: ({ row }) => <span className="text-sm">{row.original.totalDevices}</span>,
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => (
      <StatusBadge
        status={row.original.status}
        type="device"
        label={row.original.status === 'active' ? 'Active' : 'Inactive'}
        pulse={row.original.status === 'active'}
      />
    ),
  },
];

export default function OrganizationPage() {
  const [search, setSearch] = useState('');

  const filtered = mockOrganizations.filter(
    (org) =>
      org.name.toLowerCase().includes(search.toLowerCase()) ||
      org.code.toLowerCase().includes(search.toLowerCase()) ||
      org.city.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Organization</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Manage headquarters and branch offices
          </p>
        </div>
        <Button className="gap-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
          <Plus className="h-4 w-4" />
          Add Branch
        </Button>
      </div>

      {/* Stats */}
      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        className="grid gap-4 sm:grid-cols-3"
      >
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Total Organizations</p>
          <p className="text-2xl font-bold mt-1">{mockOrganizations.length}</p>
        </div>
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Active Branches</p>
          <p className="text-2xl font-bold mt-1">{mockOrganizations.filter((o) => o.status === 'active').length}</p>
        </div>
        <div className="rounded-xl border border-border/40 bg-card/50 p-4">
          <p className="text-xs text-muted-foreground">Total Vaults</p>
          <p className="text-2xl font-bold mt-1">{mockOrganizations.reduce((sum, o) => sum + o.totalVaults, 0)}</p>
        </div>
      </motion.div>

      {/* Search */}
      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          placeholder="Search organization..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-9 bg-background/50 border-border/40"
        />
      </div>

      {/* Table */}
      <DataTable columns={columns} data={filtered} searchKey="name" searchPlaceholder="Filter by name..." />
    </div>
  );
}
