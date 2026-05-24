'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Shield,
  LogIn,
  Monitor,
  BarChart3,
  Building2,
  Filter,
  Video,
  Eye,
  EyeOff,
  Lock,
  User,
  Loader2,
  X,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { APP_NAME } from '@/constants';
import { cn } from '@/lib/utils';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores';
import { authService } from '@/services';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { LiveStreamGrid } from './components/live-stream-grid';
import { BranchUtilizationChart } from './components/branch-utilization-chart';

const loginSchema = z.object({
  username: z.string().min(1, 'Username wajib diisi'),
  password: z.string().min(1, 'Password wajib diisi'),
  rememberMe: z.boolean().optional(),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LandingPage() {
  const [activeTab, setActiveTab] = useState<'livestream' | 'utilization'>('livestream');
  const [showLoginModal, setShowLoginModal] = useState(false);

  return (
    <div className="min-h-screen bg-background text-foreground flex flex-col">
      {/* Navbar */}
      <nav className="sticky top-0 z-50 border-b border-border/40 bg-background/90 backdrop-blur-xl">
        <div className="mx-auto flex items-center justify-between px-4 py-3">
          {/* Logo */}
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/20">
              <Shield className="h-5 w-5 text-white" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight">{APP_NAME}</h1>
              <p className="text-[10px] text-muted-foreground">Smart Vault Monitoring System</p>
            </div>
          </div>

          {/* Tab Navigation */}
          <div className="hidden sm:flex items-center gap-1 rounded-lg border border-border/60 bg-muted/50 p-1">
            <button
              onClick={() => setActiveTab('livestream')}
              className={cn(
                'flex items-center gap-2 rounded-md px-4 py-1.5 text-xs font-medium transition-all',
                activeTab === 'livestream'
                  ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-sm'
                  : 'text-muted-foreground hover:text-foreground'
              )}
            >
              <Monitor className="h-3.5 w-3.5" />
              Live Stream
            </button>
            <button
              onClick={() => setActiveTab('utilization')}
              className={cn(
                'flex items-center gap-2 rounded-md px-4 py-1.5 text-xs font-medium transition-all',
                activeTab === 'utilization'
                  ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-sm'
                  : 'text-muted-foreground hover:text-foreground'
              )}
            >
              <BarChart3 className="h-3.5 w-3.5" />
              Utilisasi Cabang
            </button>
          </div>

          {/* Login Button */}
          <Button
            onClick={() => setShowLoginModal(true)}
            size="sm"
            className="gap-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white"
          >
            <LogIn className="h-3.5 w-3.5" />
            Login
          </Button>
        </div>

        {/* Mobile Tab */}
        <div className="sm:hidden flex items-center gap-1 px-4 pb-3">
          <button
            onClick={() => setActiveTab('livestream')}
            className={cn(
              'flex-1 flex items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-all',
              activeTab === 'livestream'
                ? 'bg-primary/10 text-primary border border-primary/30'
                : 'text-muted-foreground'
            )}
          >
            <Monitor className="h-3.5 w-3.5" />
            Live Stream
          </button>
          <button
            onClick={() => setActiveTab('utilization')}
            className={cn(
              'flex-1 flex items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-all',
              activeTab === 'utilization'
                ? 'bg-primary/10 text-primary border border-primary/30'
                : 'text-muted-foreground'
            )}
          >
            <BarChart3 className="h-3.5 w-3.5" />
            Utilisasi
          </button>
        </div>
      </nav>

      {/* Main Content */}
      <main className="flex-1 p-4">
        {activeTab === 'livestream' ? <LiveStreamGrid /> : <BranchUtilizationChart />}
      </main>

      {/* Footer */}
      <footer className="border-t border-border/40 py-3 px-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="relative flex h-2 w-2">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75" />
              <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
            </span>
            <span className="text-[10px] text-muted-foreground">System Online</span>
          </div>
          <span className="text-[10px] text-muted-foreground/70">
            {APP_NAME} v1.0 &copy; 2025 Bank BJB
          </span>
        </div>
      </footer>

      {/* Login Modal */}
      {showLoginModal && (
        <LoginModal onClose={() => setShowLoginModal(false)} />
      )}
    </div>
  );
}

// ============================================================
// Login Modal Component
// ============================================================
function LoginModal({ onClose }: { onClose: () => void }) {
  const router = useRouter();
  const { login } = useAuthStore();
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: { username: '', password: '', rememberMe: false },
  });

  const onSubmit = async (data: LoginFormData) => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await authService.login({
        username: data.username,
        password: data.password,
        rememberMe: data.rememberMe,
      });
      login(result.user, result.tokens);
      router.push('/dashboard');
    } catch {
      setError('Username atau password salah. Coba admin / admin123');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center">
      {/* Backdrop */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal */}
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        transition={{ duration: 0.3 }}
        className="relative z-10 w-full max-w-md mx-4"
      >
        <div className="rounded-2xl border border-border/60 bg-card/95 p-6 sm:p-8 backdrop-blur-xl shadow-2xl">
          {/* Close Button */}
          <button
            onClick={onClose}
            className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
          >
            <X className="h-5 w-5" />
          </button>

          {/* Header */}
          <div className="text-center mb-6">
            <div className="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/30">
              <Shield className="h-7 w-7 text-white" />
            </div>
            <h2 className="text-xl font-bold">Login Admin Panel</h2>
            <p className="text-xs text-muted-foreground mt-1">Masuk untuk mengakses dashboard monitoring</p>
          </div>

          {/* Error */}
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              className="mb-4 rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-2.5 text-xs text-red-500 dark:text-red-400"
            >
              {error}
            </motion.div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-1.5">
              <label className="text-xs font-medium text-foreground/80">Username</label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  {...register('username')}
                  placeholder="Masukkan username"
                  className={cn(
                    'pl-10 bg-muted/30 border-border/60 focus:border-primary/50',
                    errors.username && 'border-red-500/50'
                  )}
                  disabled={isLoading}
                />
              </div>
              {errors.username && <p className="text-[10px] text-red-500 dark:text-red-400">{errors.username.message}</p>}
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-medium text-foreground/80">Password</label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  {...register('password')}
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Masukkan password"
                  className={cn(
                    'pl-10 pr-10 bg-muted/30 border-border/60 focus:border-primary/50',
                    errors.password && 'border-red-500/50'
                  )}
                  disabled={isLoading}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {errors.password && <p className="text-[10px] text-red-500 dark:text-red-400">{errors.password.message}</p>}
            </div>

            <div className="flex items-center gap-2">
              <Checkbox
                id="remember"
                {...register('rememberMe')}
                className="border-border data-[state=checked]:bg-primary"
              />
              <label htmlFor="remember" className="text-xs text-muted-foreground cursor-pointer">
                Ingat saya
              </label>
            </div>

            <Button
              type="submit"
              disabled={isLoading}
              className="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white font-medium shadow-lg shadow-blue-500/25"
            >
              {isLoading ? (
                <span className="flex items-center gap-2">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Memproses...
                </span>
              ) : (
                'Masuk'
              )}
            </Button>
          </form>

          <p className="mt-4 text-center text-[10px] text-muted-foreground/60">
            Dilindungi oleh sistem keamanan enterprise
          </p>
        </div>
      </motion.div>
    </div>
  );
}
