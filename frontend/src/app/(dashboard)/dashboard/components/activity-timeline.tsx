'use client';

import { formatDate } from '@/lib/utils';
import { cn } from '@/lib/utils';
import type { ActivityEvent } from '@/types';
import {
  DoorOpen,
  DoorClosed,
  AlertTriangle,
  WifiOff,
  LogIn,
  Wrench,
} from 'lucide-react';

interface ActivityTimelineProps {
  activities: ActivityEvent[];
}

const eventIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  vault_open: DoorOpen,
  vault_close: DoorClosed,
  alarm: AlertTriangle,
  device_offline: WifiOff,
  login: LogIn,
  maintenance: Wrench,
};

const severityColors: Record<string, string> = {
  info: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
  warning: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
  critical: 'bg-red-500/20 text-red-400 border-red-500/30',
};

export function ActivityTimeline({ activities }: ActivityTimelineProps) {
  return (
    <div className="space-y-3 max-h-[400px] overflow-y-auto pr-2">
      {activities.map((activity, index) => {
        const Icon = eventIcons[activity.type] || LogIn;
        return (
          <div
            key={activity.id}
            className={cn(
              'flex items-start gap-3 rounded-lg border border-border/30 bg-muted/20 p-3 transition-colors hover:bg-muted/40',
              index === 0 && 'border-l-2 border-l-blue-500'
            )}
          >
            <div className={cn('rounded-lg p-2 border', severityColors[activity.severity])}>
              <Icon className="h-4 w-4" />
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium truncate">{activity.title}</p>
                <span className="text-[10px] text-muted-foreground whitespace-nowrap">
                  {formatDate(activity.timestamp, 'relative')}
                </span>
              </div>
              <p className="text-xs text-muted-foreground mt-0.5 truncate">
                {activity.description}
              </p>
              <p className="text-[10px] text-muted-foreground/70 mt-1">
                {activity.branchName}
                {activity.userName && ` • ${activity.userName}`}
              </p>
            </div>
          </div>
        );
      })}
    </div>
  );
}
