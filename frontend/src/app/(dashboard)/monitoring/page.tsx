'use client';

import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Search, Filter, Grid3X3, LayoutList, RefreshCw } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { LoadingSkeleton } from '@/components/shared/loading-skeleton';
import { VaultCard } from './components/vault-card';
import { monitoringService } from '@/services';
import type { VaultMonitor } from '@/types';

export default function MonitoringPage() {
  const [vaults, setVaults] = useState<VaultMonitor[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [deviceFilter, setDeviceFilter] = useState<string>('all');

  useEffect(() => {
    const fetchVaults = async () => {
      try {
        const data = await monitoringService.getVaults();
        setVaults(data);
      } catch (error) {
        console.error('Failed to fetch vaults:', error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchVaults();
  }, []);

  const filteredVaults = vaults.filter((vault) => {
    const matchesSearch =
      vault.branchName.toLowerCase().includes(search.toLowerCase()) ||
      vault.branchCode.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || vault.status === statusFilter;
    const matchesDevice = deviceFilter === 'all' || vault.deviceStatus === deviceFilter;
    return matchesSearch && matchesStatus && matchesDevice;
  });

  const handleRefresh = async () => {
    setIsLoading(true);
    try {
      const data = await monitoringService.getVaults();
      setVaults(data);
    } catch (error) {
      console.error('Failed to refresh:', error);
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Live Monitoring</h1>
            <p className="text-sm text-muted-foreground mt-1">Realtime vault status monitoring</p>
          </div>
        </div>
        <LoadingSkeleton variant="card" count={8} />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Live Monitoring</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Realtime vault status monitoring &bull; {filteredVaults.length} vaults
          </p>
        </div>
        <Button variant="outline" size="sm" className="gap-2" onClick={handleRefresh}>
          <RefreshCw className="h-4 w-4" />
          Refresh
        </Button>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search branch name or code..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9 bg-background/50 border-border/40"
          />
        </div>

        <div className="flex items-center gap-2">
          <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v ?? 'all')}>
            <SelectTrigger className="w-[140px] bg-background/50 border-border/40">
              <Filter className="h-4 w-4 mr-2 text-muted-foreground" />
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Status</SelectItem>
              <SelectItem value="open">Open</SelectItem>
              <SelectItem value="closed">Closed</SelectItem>
              <SelectItem value="locked">Locked</SelectItem>
              <SelectItem value="alarm">Alarm</SelectItem>
              <SelectItem value="maintenance">Maintenance</SelectItem>
            </SelectContent>
          </Select>

          <Select value={deviceFilter} onValueChange={(v) => setDeviceFilter(v ?? 'all')}>
            <SelectTrigger className="w-[140px] bg-background/50 border-border/40">
              <SelectValue placeholder="Device" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Devices</SelectItem>
              <SelectItem value="online">Online</SelectItem>
              <SelectItem value="offline">Offline</SelectItem>
              <SelectItem value="warning">Warning</SelectItem>
            </SelectContent>
          </Select>

          <div className="flex items-center border border-border/40 rounded-lg overflow-hidden">
            <Button
              variant={viewMode === 'grid' ? 'secondary' : 'ghost'}
              size="icon"
              className="h-9 w-9 rounded-none"
              onClick={() => setViewMode('grid')}
            >
              <Grid3X3 className="h-4 w-4" />
            </Button>
            <Button
              variant={viewMode === 'list' ? 'secondary' : 'ghost'}
              size="icon"
              className="h-9 w-9 rounded-none"
              onClick={() => setViewMode('list')}
            >
              <LayoutList className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      {/* Vault Grid */}
      <motion.div
        layout
        className={
          viewMode === 'grid'
            ? 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
            : 'space-y-3'
        }
      >
        {filteredVaults.map((vault, index) => (
          <motion.div
            key={vault.id}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, delay: index * 0.05 }}
          >
            <VaultCard vault={vault} viewMode={viewMode} />
          </motion.div>
        ))}
      </motion.div>

      {filteredVaults.length === 0 && (
        <div className="text-center py-12">
          <p className="text-sm text-muted-foreground">No vaults match your filter criteria.</p>
        </div>
      )}
    </div>
  );
}
