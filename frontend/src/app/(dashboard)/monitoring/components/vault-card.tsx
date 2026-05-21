'use client';

import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { formatDuration } from '@/lib/utils';
import { StatusBadge } from '@/components/shared/status-badge';
import {
  User,
  Clock,
  Thermometer,
  Droplets,
  Maximize2,
  Video,
  AlertTriangle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { VaultMonitor } from '@/types';

interface VaultCardProps {
  vault: VaultMonitor;
  viewMode: 'grid' | 'list';
}

export function VaultCard({ vault, viewMode }: VaultCardProps) {
  const [duration, setDuration] = useState(vault.duration || 0);

  useEffect(() => {
    if (vault.status === 'open' && vault.entryTime) {
      const interval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - new Date(vault.entryTime!).getTime()) / 1000);
        setDuration(elapsed);
      }, 1000);
      return () => clearInterval(interval);
    }
  }, [vault.status, vault.entryTime]);

  const isAlarm = vault.status === 'alarm';
  const isOpen = vault.status === 'open';

  if (viewMode === 'list') {
    return (
      <div
        className={cn(
          'flex items-center gap-4 rounded-xl border p-4 transition-all hover:bg-muted/20',
          isAlarm
            ? 'border-red-500/30 bg-red-500/5'
            : 'border-border/40 bg-card/50 backdrop-blur-sm'
        )}
      >
        {/* Status Indicator */}
        <div className="relative">
          <div
            className={cn(
              'h-3 w-3 rounded-full',
              vault.deviceStatus === 'online' ? 'bg-emerald-500' : 'bg-slate-500'
            )}
          />
          {vault.deviceStatus === 'online' && (
            <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-40" />
          )}
        </div>

        {/* Branch Info */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <p className="text-sm font-medium truncate">{vault.branchName}</p>
            <StatusBadge status={vault.status} type="vault" pulse={isAlarm || isOpen} />
          </div>
          <p className="text-xs text-muted-foreground mt-0.5">{vault.branchCode} &bull; {vault.vaultName}</p>
        </div>

        {/* Current User */}
        {vault.currentUser && (
          <div className="flex items-center gap-2 text-xs">
            <User className="h-3.5 w-3.5 text-muted-foreground" />
            <span>{vault.currentUser}</span>
            <Clock className="h-3.5 w-3.5 text-muted-foreground ml-2" />
            <span className="font-mono">{formatDuration(duration)}</span>
          </div>
        )}

        {/* Environment */}
        <div className="hidden lg:flex items-center gap-3 text-xs text-muted-foreground">
          <span className="flex items-center gap-1">
            <Thermometer className="h-3.5 w-3.5" />
            {vault.temperature}°C
          </span>
          <span className="flex items-center gap-1">
            <Droplets className="h-3.5 w-3.5" />
            {vault.humidity}%
          </span>
        </div>

        {/* Actions */}
        <Button variant="ghost" size="icon" className="h-8 w-8">
          <Maximize2 className="h-4 w-4" />
        </Button>
      </div>
    );
  }

  return (
    <motion.div
      whileHover={{ scale: 1.01 }}
      className={cn(
        'relative overflow-hidden rounded-xl border transition-all',
        isAlarm
          ? 'border-red-500/30 bg-red-500/5 shadow-lg shadow-red-500/10'
          : 'border-border/40 bg-card/50 backdrop-blur-sm hover:border-border/60'
      )}
    >
      {/* Alarm Pulse */}
      {isAlarm && (
        <motion.div
          animate={{ opacity: [0.1, 0.3, 0.1] }}
          transition={{ duration: 2, repeat: Infinity }}
          className="absolute inset-0 bg-red-500/5"
        />
      )}

      {/* Stream Preview */}
      <div className="relative h-32 bg-slate-900/50 flex items-center justify-center overflow-hidden">
        {vault.deviceStatus === 'online' ? (
          <>
            <div className="absolute inset-0 bg-gradient-to-b from-transparent to-black/60" />
            <Video className="h-8 w-8 text-slate-600" />
            <div className="absolute top-2 left-2 flex items-center gap-1.5">
              <span className="relative flex h-2 w-2">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75" />
                <span className="relative inline-flex h-2 w-2 rounded-full bg-red-500" />
              </span>
              <span className="text-[10px] font-medium text-white/80">LIVE</span>
            </div>
            <div className="absolute top-2 right-2">
              <Button variant="ghost" size="icon" className="h-6 w-6 text-white/70 hover:text-white">
                <Maximize2 className="h-3.5 w-3.5" />
              </Button>
            </div>
          </>
        ) : (
          <div className="text-center">
            <Video className="h-8 w-8 text-slate-700 mx-auto" />
            <p className="text-[10px] text-slate-600 mt-1">OFFLINE</p>
          </div>
        )}

        {/* Alarm Overlay */}
        {isAlarm && (
          <motion.div
            animate={{ opacity: [0.5, 1, 0.5] }}
            transition={{ duration: 1, repeat: Infinity }}
            className="absolute inset-0 flex items-center justify-center bg-red-900/40"
          >
            <AlertTriangle className="h-10 w-10 text-red-400" />
          </motion.div>
        )}
      </div>

      {/* Card Content */}
      <div className="p-3 space-y-3">
        {/* Header */}
        <div className="flex items-start justify-between">
          <div>
            <p className="text-sm font-medium leading-tight">{vault.branchName}</p>
            <p className="text-[10px] text-muted-foreground mt-0.5">
              {vault.branchCode} &bull; {vault.vaultName}
            </p>
          </div>
          <StatusBadge status={vault.status} type="vault" pulse={isAlarm || isOpen} />
        </div>

        {/* Current User */}
        {vault.currentUser && (
          <div className="flex items-center justify-between rounded-lg bg-muted/30 px-2.5 py-2">
            <div className="flex items-center gap-2">
              <User className="h-3.5 w-3.5 text-blue-400" />
              <span className="text-xs font-medium">{vault.currentUser}</span>
            </div>
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <Clock className="h-3 w-3" />
              <span className="font-mono text-[11px]">{formatDuration(duration)}</span>
            </div>
          </div>
        )}

        {/* Environment & Status */}
        <div className="flex items-center justify-between text-[11px] text-muted-foreground">
          <div className="flex items-center gap-3">
            {vault.temperature > 0 && (
              <span className="flex items-center gap-1">
                <Thermometer className="h-3 w-3" />
                {vault.temperature}°C
              </span>
            )}
            {vault.humidity > 0 && (
              <span className="flex items-center gap-1">
                <Droplets className="h-3 w-3" />
                {vault.humidity}%
              </span>
            )}
          </div>
          <StatusBadge status={vault.deviceStatus} type="device" />
        </div>
      </div>
    </motion.div>
  );
}
