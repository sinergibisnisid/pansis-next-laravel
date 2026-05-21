'use client';

import { useCurrentTime } from '@/hooks';
import { useAuthStore, useNotificationStore, useWebSocketStore } from '@/stores';
import { useSidebarStore } from '@/stores';
import { RealtimeIndicator } from '@/components/shared/realtime-indicator';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Bell,
  LogOut,
  Menu,
  Search,
  User,
  Settings,
  Clock,
} from 'lucide-react';
import { formatDate, getInitials } from '@/lib/utils';
import { motion } from 'framer-motion';
import { useRouter } from 'next/navigation';

export function Topbar() {
  const time = useCurrentTime();
  const router = useRouter();
  const { user, logout } = useAuthStore();
  const { unreadCount, setOpen } = useNotificationStore();
  const { status } = useWebSocketStore();
  const { setMobileOpen } = useSidebarStore();

  const handleLogout = () => {
    logout();
    router.push('/login');
  };

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border/40 bg-background/80 px-6 backdrop-blur-xl">
      {/* Left Section */}
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          className="lg:hidden"
          onClick={() => setMobileOpen(true)}
        >
          <Menu className="h-5 w-5" />
        </Button>

        {/* Search */}
        <div className="hidden md:flex items-center gap-2 rounded-lg border border-border/40 bg-muted/30 px-3 py-1.5">
          <Search className="h-4 w-4 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search branches, vaults, users..."
            className="w-64 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
          />
          <kbd className="hidden lg:inline-flex h-5 items-center gap-1 rounded border border-border/40 bg-muted/50 px-1.5 text-[10px] font-medium text-muted-foreground">
            Ctrl+K
          </kbd>
        </div>
      </div>

      {/* Right Section */}
      <div className="flex items-center gap-3">
        {/* Realtime Status */}
        <div className="hidden md:flex">
          <RealtimeIndicator status={status} />
        </div>

        {/* Clock */}
        <div className="hidden lg:flex items-center gap-1.5 text-xs text-muted-foreground">
          <Clock className="h-3.5 w-3.5" />
          <span className="font-mono">{time ? formatDate(time, 'time') : '--:--:--'}</span>
        </div>

        {/* Notifications */}
        <Button
          variant="ghost"
          size="icon"
          className="relative"
          onClick={() => setOpen(true)}
        >
          <Bell className="h-4.5 w-4.5" />
          {unreadCount > 0 && (
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              className="absolute -right-0.5 -top-0.5"
            >
              <Badge className="h-4.5 min-w-[18px] rounded-full bg-red-500 px-1 text-[10px] font-bold text-white border-0">
                {unreadCount > 99 ? '99+' : unreadCount}
              </Badge>
            </motion.div>
          )}
        </Button>

        {/* User Menu */}
        <DropdownMenu>
          <DropdownMenuTrigger className="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-accent transition-colors outline-none">
            <Avatar className="h-8 w-8 border border-border/40">
              <AvatarFallback className="bg-gradient-to-br from-blue-600 to-cyan-500 text-xs font-bold text-white">
                {user ? getInitials(user.fullName) : 'U'}
              </AvatarFallback>
            </Avatar>
            <div className="hidden md:block text-left">
              <p className="text-xs font-medium leading-none">{user?.fullName || 'User'}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">{user?.role || 'Role'}</p>
            </div>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-56">
            <DropdownMenuGroup>
              <DropdownMenuLabel>
                <div>
                  <p className="text-sm font-medium">{user?.fullName}</p>
                  <p className="text-xs text-muted-foreground">{user?.email}</p>
                </div>
              </DropdownMenuLabel>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuItem onClick={() => router.push('/settings')}>
                <User className="mr-2 h-4 w-4" />
                Profile
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.push('/settings')}>
                <Settings className="mr-2 h-4 w-4" />
                Settings
              </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuItem onClick={handleLogout} className="text-red-400 focus:text-red-400">
                <LogOut className="mr-2 h-4 w-4" />
                Logout
              </DropdownMenuItem>
            </DropdownMenuGroup>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  );
}
