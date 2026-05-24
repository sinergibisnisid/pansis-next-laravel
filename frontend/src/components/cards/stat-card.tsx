'use client';

import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: LucideIcon;
  trend?: {
    value: number;
    isPositive: boolean;
  };
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
  className?: string;
  pulse?: boolean;
}

const variantStyles = {
  default: 'from-slate-500/10 to-slate-600/5 border-slate-500/20',
  success: 'from-emerald-500/10 to-emerald-600/5 border-emerald-500/20',
  warning: 'from-amber-500/10 to-amber-600/5 border-amber-500/20',
  danger: 'from-red-500/10 to-red-600/5 border-red-500/20',
  info: 'from-blue-500/10 to-blue-600/5 border-blue-500/20',
};

const iconVariantStyles = {
  default: 'bg-slate-500/20 text-slate-400',
  success: 'bg-emerald-500/20 text-emerald-400',
  warning: 'bg-amber-500/20 text-amber-400',
  danger: 'bg-red-500/20 text-red-400',
  info: 'bg-blue-500/20 text-blue-400',
};

export function StatCard({
  title,
  value,
  subtitle,
  icon: Icon,
  trend,
  variant = 'default',
  className,
  pulse = false,
}: StatCardProps) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
      className={cn(
        'relative overflow-hidden rounded-xl border bg-gradient-to-br p-3 sm:p-4 backdrop-blur-sm',
        variantStyles[variant],
        className
      )}
    >
      <div className="flex items-start justify-between">
        <div className="space-y-0.5 sm:space-y-1 min-w-0 flex-1">
          <p className="text-[10px] sm:text-xs font-medium uppercase tracking-wider text-muted-foreground truncate">
            {title}
          </p>
          <div className="flex items-baseline gap-1 sm:gap-2">
            <h3 className="text-lg sm:text-2xl font-bold tracking-tight truncate">{value}</h3>
            {trend && (
              <span
                className={cn(
                  'text-[10px] sm:text-xs font-medium shrink-0',
                  trend.isPositive ? 'text-emerald-400' : 'text-red-400'
                )}
              >
                {trend.isPositive ? '+' : ''}{trend.value}%
              </span>
            )}
          </div>
          {subtitle && (
            <p className="text-[10px] sm:text-xs text-muted-foreground truncate hidden sm:block">{subtitle}</p>
          )}
        </div>
        <div className={cn('rounded-lg p-2 sm:p-2.5 shrink-0', iconVariantStyles[variant])}>
          {pulse ? (
            <div className="relative">
              <Icon className="h-4 w-4 sm:h-5 sm:w-5" />
              <span className="absolute -right-1 -top-1 h-2 w-2 sm:h-2.5 sm:w-2.5">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-current opacity-75" />
                <span className="relative inline-flex h-full w-full rounded-full bg-current" />
              </span>
            </div>
          ) : (
            <Icon className="h-4 w-4 sm:h-5 sm:w-5" />
          )}
        </div>
      </div>

      {/* Decorative gradient */}
      <div className="absolute -bottom-4 -right-4 h-24 w-24 rounded-full bg-gradient-to-br from-current opacity-5 blur-2xl" />
    </motion.div>
  );
}
