'use client';

import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { Wifi, WifiOff } from 'lucide-react';

interface RealtimeIndicatorProps {
  status: 'connected' | 'connecting' | 'disconnected' | 'error';
  showLabel?: boolean;
  className?: string;
}

const statusConfig = {
  connected: {
    color: 'bg-emerald-500',
    label: 'Connected',
    icon: Wifi,
  },
  connecting: {
    color: 'bg-amber-500',
    label: 'Connecting...',
    icon: Wifi,
  },
  disconnected: {
    color: 'bg-slate-500',
    label: 'Disconnected',
    icon: WifiOff,
  },
  error: {
    color: 'bg-red-500',
    label: 'Error',
    icon: WifiOff,
  },
};

export function RealtimeIndicator({ status, showLabel = true, className }: RealtimeIndicatorProps) {
  const config = statusConfig[status];
  const Icon = config.icon;

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <div className="relative">
        <motion.div
          className={cn('h-2.5 w-2.5 rounded-full', config.color)}
          animate={
            status === 'connected'
              ? { scale: [1, 1.2, 1] }
              : status === 'connecting'
              ? { opacity: [1, 0.5, 1] }
              : {}
          }
          transition={{ duration: 2, repeat: Infinity }}
        />
        {status === 'connected' && (
          <span
            className={cn(
              'absolute inset-0 rounded-full animate-ping opacity-40',
              config.color
            )}
          />
        )}
      </div>
      {showLabel && (
        <div className="flex items-center gap-1.5">
          <Icon className="h-3.5 w-3.5 text-muted-foreground" />
          <span className="text-xs text-muted-foreground">{config.label}</span>
        </div>
      )}
    </div>
  );
}
