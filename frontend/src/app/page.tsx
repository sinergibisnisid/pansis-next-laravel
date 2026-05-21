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
    <div className="min-h-screen bg-[#0a0e1a] text-white flex flex-col">
      {/* Navbar */}
      <nav className="sticky top-0 z-50 border-b border-white/5 bg-[#0a0e1a]/90 backdrop-blur-xl">
        <div className="mx-auto flex items-center justify-between px-4 py-3">
          {/* Logo */}
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/20">
              <Shield className="h-5 w-5 text-white" />
            </div>
            <div>
              <h1 className="text-sm font-bold tracking-tight">{APP_NAME}</h1>
              <p className="text-[10px] text-slate-400">Smart Vault Monitoring System</p>
            </div>
          </div>

          {/* Tab Navigation */}
          <div className="hidden sm:flex items-center gap-1 rounded-lg border border-white/10 bg-white/5 p-1">
            <button
              onClick={() => setActiveTab('livestream')}
              className={cn(
                'flex items-center gap-2 rounded-md px-4 py-1.5 text-xs font-medium transition-all',
                activeTab === 'livestream'
                  ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-sm'
                  : 'text-slate-400 hover:text-white'
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
                  : 'text-slate-400 hover:text-white'
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
            className="gap-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400"
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
                ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30'
                : 'text-slate-400'
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
                ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30'
                : 'text-slate-400'
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
      <footer className="border-t border-white/5 py-3 px-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="relative flex h-2 w-2">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75" />
              <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
            </span>
            <span className="text-[10px] text-slate-500">System Online</span>
          </div>
          <span className="text-[10px] text-slate-600">
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
        <div className="rounded-2xl border border-white/10 bg-[#0f1629]/95 p-8 backdrop-blur-xl shadow-2xl shadow-blue-500/10">
          {/* Close Button */}
          <button
            onClick={onClose}
            className="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors"
          >
            <X className="h-5 w-5" />
          </button>

          {/* Header */}
          <div className="text-center mb-6">
            <div className="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/30">
              <Shield className="h-7 w-7 text-white" />
            </div>
            <h2 className="text-xl font-bold text-white">Login Admin Panel</h2>
            <p className="text-xs text-slate-400 mt-1">Masuk untuk mengakses dashboard monitoring</p>
          </div>

          {/* Error */}
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              className="mb-4 rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-2.5 text-xs text-red-400"
            >
              {error}
            </motion.div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-1.5">
              <label className="text-xs font-medium text-slate-300">Username</label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <Input
                  {...register('username')}
                  placeholder="Masukkan username"
                  className={cn(
                    'pl-10 bg-white/5 border-white/10 text-white placeholder:text-slate-500 focus:border-blue-500/50',
                    errors.username && 'border-red-500/50'
                  )}
                  disabled={isLoading}
                />
              </div>
              {errors.username && <p className="text-[10px] text-red-400">{errors.username.message}</p>}
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-medium text-slate-300">Password</label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <Input
                  {...register('password')}
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Masukkan password"
                  className={cn(
                    'pl-10 pr-10 bg-white/5 border-white/10 text-white placeholder:text-slate-500 focus:border-blue-500/50',
                    errors.password && 'border-red-500/50'
                  )}
                  disabled={isLoading}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {errors.password && <p className="text-[10px] text-red-400">{errors.password.message}</p>}
            </div>

            <div className="flex items-center gap-2">
              <Checkbox
                id="remember"
                {...register('rememberMe')}
                className="border-white/20 data-[state=checked]:bg-blue-600"
              />
              <label htmlFor="remember" className="text-xs text-slate-400 cursor-pointer">
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

          <p className="mt-4 text-center text-[10px] text-slate-600">
            Dilindungi oleh sistem keamanan enterprise
          </p>
        </div>
      </motion.div>
    </div>
  );
}
