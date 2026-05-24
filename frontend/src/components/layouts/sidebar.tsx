'use client';

import { useEffect } from 'react';
import { usePathname } from 'next/navigation';
import Link from 'next/link';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '@/lib/utils';
import { useSidebarStore } from '@/stores';
import { SIDEBAR_MENU, APP_NAME } from '@/constants';
import {
  LayoutDashboard,
  Monitor,
  Building2,
  Users,
  Cpu,
  FileText,
  Wrench,
  Bell,
  Radio,
  Server,
  Settings,
  ChevronLeft,
  Shield,
  X,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  LayoutDashboard,
  Monitor,
  Building2,
  Users,
  Cpu,
  FileText,
  Wrench,
  Bell,
  Radio,
  Server,
  Settings,
};

export function Sidebar() {
  const pathname = usePathname();
  const { isCollapsed, isMobileOpen, setMobileOpen, toggle } = useSidebarStore();

  // Close mobile sidebar on route change
  useEffect(() => {
    setMobileOpen(false);
  }, [pathname, setMobileOpen]);

  // Close mobile sidebar on resize to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 1024) {
        setMobileOpen(false);
      }
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [setMobileOpen]);

  return (
    <>
      {/* Mobile Overlay */}
      <AnimatePresence>
        {isMobileOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm lg:hidden"
            onClick={() => setMobileOpen(false)}
          />
        )}
      </AnimatePresence>

      {/* Mobile Sidebar Drawer */}
      <motion.aside
        initial={false}
        animate={{
          x: isMobileOpen ? 0 : -280,
        }}
        transition={{ duration: 0.3, ease: 'easeInOut' }}
        className="fixed left-0 top-0 z-50 flex h-screen w-[280px] flex-col border-r border-border/40 bg-card/95 backdrop-blur-xl lg:hidden"
      >
        {/* Mobile Close Button */}
        <div className="absolute right-3 top-3">
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8 text-muted-foreground hover:text-foreground"
            onClick={() => setMobileOpen(false)}
          >
            <X className="h-4 w-4" />
          </Button>
        </div>

        {/* Mobile Logo */}
        <div className="flex h-16 items-center px-4">
          <Link href="/dashboard" className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/20">
              <Shield className="h-5 w-5 text-white" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight">{APP_NAME}</h1>
              <p className="text-[10px] text-muted-foreground">Smart Vault Monitoring</p>
            </div>
          </Link>
        </div>

        <Separator className="opacity-40" />

        {/* Mobile Navigation */}
        <ScrollArea className="flex-1 px-3 py-4">
          <nav className="space-y-6">
            {SIDEBAR_MENU.map((group) => (
              <div key={group.group}>
                <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                  {group.group}
                </p>
                <div className="space-y-1">
                  {group.items.map((item) => {
                    const Icon = iconMap[item.icon];
                    const isActive = pathname === item.href;
                    return (
                      <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                          'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
                          isActive
                            ? 'bg-gradient-to-r from-blue-600/20 to-cyan-500/10 text-blue-400 border border-blue-500/20 shadow-sm shadow-blue-500/5'
                            : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        )}
                      >
                        {Icon && <Icon className={cn('h-4.5 w-4.5', isActive && 'text-blue-400')} />}
                        <span>{item.label}</span>
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </nav>
        </ScrollArea>
      </motion.aside>

      {/* Desktop Sidebar */}
      <motion.aside
        initial={false}
        animate={{ width: isCollapsed ? 72 : 260 }}
        transition={{ duration: 0.2, ease: 'easeInOut' }}
        className="fixed left-0 top-0 z-40 hidden h-screen flex-col border-r border-border/40 bg-card/80 backdrop-blur-xl lg:flex"
      >
      {/* Logo */}
      <div className="flex h-16 items-center justify-between px-4">
        <Link href="/dashboard" className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/20">
            <Shield className="h-5 w-5 text-white" />
          </div>
          <AnimatePresence>
            {!isCollapsed && (
              <motion.div
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -10 }}
                transition={{ duration: 0.15 }}
              >
                <h1 className="text-sm font-bold tracking-tight">{APP_NAME}</h1>
                <p className="text-[10px] text-muted-foreground">Smart Vault Monitoring</p>
              </motion.div>
            )}
          </AnimatePresence>
        </Link>
      </div>

      <Separator className="opacity-40" />

      {/* Navigation */}
      <ScrollArea className="flex-1 px-3 py-4">
        <nav className="space-y-6">
          {SIDEBAR_MENU.map((group) => (
            <div key={group.group}>
              <AnimatePresence>
                {!isCollapsed && (
                  <motion.p
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground"
                  >
                    {group.group}
                  </motion.p>
                )}
              </AnimatePresence>
              <div className="space-y-1">
                {group.items.map((item) => {
                  const Icon = iconMap[item.icon];
                  const isActive = pathname === item.href;

                  const linkContent = (
                    <Link
                      href={item.href}
                      className={cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
                        isActive
                          ? 'bg-gradient-to-r from-blue-600/20 to-cyan-500/10 text-blue-400 border border-blue-500/20 shadow-sm shadow-blue-500/5'
                          : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                      )}
                    >
                      <div className="relative">
                        {Icon && <Icon className={cn('h-4.5 w-4.5', isActive && 'text-blue-400')} />}
                        {isActive && (
                          <motion.div
                            layoutId="sidebar-active"
                            className="absolute -left-[21px] top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-blue-500"
                            transition={{ type: 'spring', stiffness: 300, damping: 30 }}
                          />
                        )}
                      </div>
                      <AnimatePresence>
                        {!isCollapsed && (
                          <motion.span
                            initial={{ opacity: 0, x: -10 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -10 }}
                            transition={{ duration: 0.15 }}
                          >
                            {item.label}
                          </motion.span>
                        )}
                      </AnimatePresence>
                    </Link>
                  );

                  if (isCollapsed) {
                    return (
                      <Tooltip key={item.href}>
                        <TooltipTrigger>
                          {linkContent}
                        </TooltipTrigger>
                        <TooltipContent side="right" className="font-medium">
                          {item.label}
                        </TooltipContent>
                      </Tooltip>
                    );
                  }

                  return <div key={item.href}>{linkContent}</div>;
                })}
              </div>
            </div>
          ))}
        </nav>
      </ScrollArea>

      {/* Collapse Toggle */}
      <div className="border-t border-border/40 p-3">
        <Button
          variant="ghost"
          size="sm"
          onClick={toggle}
          className="w-full justify-center gap-2 text-muted-foreground hover:text-foreground"
        >
          <motion.div animate={{ rotate: isCollapsed ? 180 : 0 }} transition={{ duration: 0.2 }}>
            <ChevronLeft className="h-4 w-4" />
          </motion.div>
          <AnimatePresence>
            {!isCollapsed && (
              <motion.span
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="text-xs"
              >
                Collapse
              </motion.span>
            )}
          </AnimatePresence>
        </Button>
      </div>
    </motion.aside>
    </>
  );
}
