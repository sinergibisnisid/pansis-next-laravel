'use client';

import { type ReactNode, useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Sidebar } from './sidebar';
import { Topbar } from './topbar';
import { useSidebarStore } from '@/stores';
import { useHydration } from '@/hooks';
import { cn } from '@/lib/utils';

interface DashboardLayoutProps {
  children: ReactNode;
}

export function DashboardLayout({ children }: DashboardLayoutProps) {
  const { isCollapsed } = useSidebarStore();
  const hydrated = useHydration();
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const checkMobile = () => setIsMobile(window.innerWidth < 1024);
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Prevent layout shift during hydration
  const sidebarWidth = hydrated ? (isCollapsed ? 72 : 260) : 260;

  return (
    <div className="min-h-screen bg-background">
      {/* Sidebar */}
      <Sidebar />

      {/* Main Content */}
      <motion.div
        initial={false}
        animate={{ marginLeft: isMobile ? 0 : sidebarWidth }}
        transition={{ duration: 0.2, ease: 'easeInOut' }}
        className="flex min-h-screen flex-col"
      >
        {/* Topbar */}
        <Topbar />

        {/* Page Content */}
        <main className="flex-1 p-3 sm:p-4 lg:p-6">
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
          >
            {children}
          </motion.div>
        </main>
      </motion.div>
    </div>
  );
}
