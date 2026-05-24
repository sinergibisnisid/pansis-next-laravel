'use client';

import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type ReactNode } from 'react';

interface ChartCardProps {
  title: string;
  subtitle?: string;
  children: ReactNode;
  action?: ReactNode;
  className?: string;
}

export function ChartCard({ title, subtitle, children, action, className }: ChartCardProps) {
  return (
    <Card className={cn('border-border/40 bg-card/50 backdrop-blur-sm', className)}>
      <CardHeader className="flex flex-row items-center justify-between px-3 sm:px-6 pb-2">
        <div className="min-w-0 flex-1">
          <CardTitle className="text-xs sm:text-sm font-medium truncate">{title}</CardTitle>
          {subtitle && (
            <p className="text-[10px] sm:text-xs text-muted-foreground mt-0.5 truncate">{subtitle}</p>
          )}
        </div>
        {action && <div className="shrink-0 ml-2">{action}</div>}
      </CardHeader>
      <CardContent className="pt-0 px-3 sm:px-6">{children}</CardContent>
    </Card>
  );
}
