'use client';

import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { VAULT_STATUS_COLORS, DEVICE_STATUS_COLORS } from '@/constants';

interface StatusBadgeProps {
  status: string;
  type?: 'vault' | 'device' | 'custom';
  label?: string;
  pulse?: boolean;
  className?: string;
}

export function StatusBadge({ status, type = 'vault', label, pulse = false, className }: StatusBadgeProps) {
  const colors = type === 'vault' ? VAULT_STATUS_COLORS : DEVICE_STATUS_COLORS;
  const colorClass = colors[status] || 'bg-slate-500/20 text-slate-400 border-slate-500/30';

  return (
    <Badge
      variant="outline"
      className={cn(
        'gap-1.5 font-medium text-[10px] uppercase tracking-wider px-2 py-0.5',
        colorClass,
        className
      )}
    >
      {pulse && (
        <span className="relative flex h-2 w-2">
          <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-current opacity-75" />
          <span className="relative inline-flex h-2 w-2 rounded-full bg-current" />
        </span>
      )}
      {label || status}
    </Badge>
  );
}
