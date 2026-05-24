'use client';

import { useState } from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend,
  AreaChart,
  Area,
} from 'recharts';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Building2, TrendingUp, Calendar, ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

const branchUtilizationData = [
  { branch: 'BDG-01', name: 'KCP Bandung Utara', akses: 45, durasi: 120, alarm: 2 },
  { branch: 'BDG-02', name: 'KCP Bandung Selatan', akses: 38, durasi: 95, alarm: 0 },
  { branch: 'CMH-01', name: 'KCP Cimahi', akses: 32, durasi: 88, alarm: 5 },
  { branch: 'GRT-01', name: 'KCP Garut', akses: 28, durasi: 72, alarm: 1 },
  { branch: 'SMD-01', name: 'KCP Sumedang', akses: 42, durasi: 110, alarm: 0 },
  { branch: 'TSK-01', name: 'KCP Tasikmalaya', akses: 35, durasi: 92, alarm: 1 },
  { branch: 'CJR-01', name: 'KCP Cianjur', akses: 22, durasi: 58, alarm: 0 },
  { branch: 'SKB-01', name: 'KCP Sukabumi', akses: 40, durasi: 105, alarm: 3 },
  { branch: 'BGR-01', name: 'KCP Bogor', akses: 36, durasi: 98, alarm: 0 },
  { branch: 'DPK-01', name: 'KCP Depok', akses: 30, durasi: 80, alarm: 2 },
  { branch: 'BKS-01', name: 'KCP Bekasi', akses: 33, durasi: 85, alarm: 1 },
  { branch: 'KRW-01', name: 'KCP Karawang', akses: 25, durasi: 65, alarm: 0 },
];

const weeklyTrendData = [
  { day: 'Sen', akses: 120, alarm: 5 },
  { day: 'Sel', akses: 135, alarm: 3 },
  { day: 'Rab', akses: 128, alarm: 8 },
  { day: 'Kam', akses: 142, alarm: 2 },
  { day: 'Jum', akses: 110, alarm: 4 },
  { day: 'Sab', akses: 45, alarm: 1 },
  { day: 'Min', akses: 20, alarm: 0 },
];

const statusDistribution = [
  { name: 'Normal', value: 38, color: '#10b981' },
  { name: 'Perlu Perhatian', value: 8, color: '#f59e0b' },
  { name: 'Alarm', value: 3, color: '#ef4444' },
  { name: 'Maintenance', value: 3, color: '#6366f1' },
];

const periods = ['Hari Ini', 'Minggu Ini', 'Bulan Ini'];

export function BranchUtilizationChart() {
  const [period, setPeriod] = useState('Minggu Ini');
  const [currentPage, setCurrentPage] = useState(1);
  const totalPages = 2;

  return (
    <div className="space-y-6">
      {/* Header & Filter */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold">Utilisasi Kantor Cabang</h2>
          <p className="text-xs text-muted-foreground mt-0.5">Statistik penggunaan vault per cabang</p>
        </div>
        <Select value={period} onValueChange={(v) => setPeriod(v ?? 'Minggu Ini')}>
          <SelectTrigger className="w-[150px] bg-muted/50 border-border/60 text-xs">
            <Calendar className="h-3.5 w-3.5 mr-2 text-muted-foreground" />
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {periods.map((p) => (
              <SelectItem key={p} value={p}>{p}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Page 1: Summary + Charts */}
      {currentPage === 1 && (
        <>
          {/* Summary Cards */}
          <div className="grid gap-2 sm:gap-3 grid-cols-2 lg:grid-cols-4">
            <div className="rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
              <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Total Akses</p>
              <p className="text-xl sm:text-2xl font-bold mt-1">406</p>
              <p className="text-[10px] text-emerald-500 dark:text-emerald-400 mt-0.5 flex items-center gap-1">
                <TrendingUp className="h-3 w-3" /> +12%
              </p>
            </div>
            <div className="rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
              <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Rata-rata Durasi</p>
              <p className="text-xl sm:text-2xl font-bold mt-1">8.5<span className="text-xs sm:text-sm text-muted-foreground">min</span></p>
              <p className="text-[10px] text-muted-foreground mt-0.5">Per sesi akses</p>
            </div>
            <div className="rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
              <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Total Alarm</p>
              <p className="text-xl sm:text-2xl font-bold text-red-500 dark:text-red-400 mt-1">15</p>
              <p className="text-[10px] text-red-500/70 dark:text-red-400/70 mt-0.5">3 belum ditangani</p>
            </div>
            <div className="rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
              <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Cabang Aktif</p>
              <p className="text-xl sm:text-2xl font-bold mt-1">11<span className="text-xs sm:text-sm text-muted-foreground">/12</span></p>
              <p className="text-[10px] text-emerald-500 dark:text-emerald-400 mt-0.5">91.7% online</p>
            </div>
          </div>

          {/* Charts Grid */}
          <div className="grid gap-4 lg:grid-cols-3">
            {/* Bar Chart - Akses per Cabang */}
            <div className="lg:col-span-2 rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
              <h3 className="text-xs sm:text-sm font-medium mb-3 sm:mb-4">Frekuensi Akses per Cabang</h3>
              <div className="h-[220px] sm:h-[300px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={branchUtilizationData} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-border/40" />
                    <XAxis
                      dataKey="branch"
                      tick={{ fontSize: 10 }}
                      className="text-muted-foreground"
                      axisLine={false}
                      tickLine={false}
                    />
                    <YAxis
                      tick={{ fontSize: 10 }}
                      className="text-muted-foreground"
                      axisLine={false}
                      tickLine={false}
                    />
                    <Tooltip
                      contentStyle={{
                        backgroundColor: 'hsl(var(--card))',
                        border: '1px solid hsl(var(--border))',
                        borderRadius: '8px',
                        fontSize: '11px',
                        color: 'hsl(var(--foreground))',
                      }}
                      formatter={(value, name) => [String(value), name === 'akses' ? 'Akses' : name === 'alarm' ? 'Alarm' : String(name)]}
                    />
                    <Bar dataKey="akses" fill="#3b82f6" radius={[4, 4, 0, 0]} name="akses" />
                    <Bar dataKey="alarm" fill="#ef4444" radius={[4, 4, 0, 0]} name="alarm" />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>

            {/* Pie Chart - Status Distribution */}
            <div className="rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
              <h3 className="text-xs sm:text-sm font-medium mb-3 sm:mb-4">Distribusi Status Vault</h3>
              <div className="h-[250px] sm:h-[300px]">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={statusDistribution}
                      cx="50%"
                      cy="45%"
                      innerRadius={55}
                      outerRadius={85}
                      paddingAngle={4}
                      dataKey="value"
                      stroke="none"
                    >
                      {statusDistribution.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip
                      contentStyle={{
                        backgroundColor: 'hsl(var(--card))',
                        border: '1px solid hsl(var(--border))',
                        borderRadius: '8px',
                        fontSize: '11px',
                        color: 'hsl(var(--foreground))',
                      }}
                    />
                    <Legend
                      verticalAlign="bottom"
                      height={36}
                      formatter={(value) => (
                        <span className="text-[10px] text-muted-foreground">{value}</span>
                      )}
                    />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </>
      )}

      {/* Page 2: Weekly Trend */}
      {currentPage === 2 && (
        <div className="rounded-xl border border-border/60 bg-card/50 p-3 sm:p-4">
          <h3 className="text-xs sm:text-sm font-medium mb-3 sm:mb-4">Tren Akses Mingguan</h3>
          <div className="h-[300px] sm:h-[400px]">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={weeklyTrendData} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
                <defs>
                  <linearGradient id="landingAkses" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" className="stroke-border/40" />
                <XAxis
                  dataKey="day"
                  tick={{ fontSize: 11 }}
                  className="text-muted-foreground"
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis
                  tick={{ fontSize: 11 }}
                  className="text-muted-foreground"
                  axisLine={false}
                  tickLine={false}
                />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: '8px',
                    fontSize: '11px',
                    color: 'hsl(var(--foreground))',
                  }}
                />
                <Area type="monotone" dataKey="akses" stroke="#3b82f6" strokeWidth={2} fill="url(#landingAkses)" name="Akses" />
                <Area type="monotone" dataKey="alarm" stroke="#ef4444" strokeWidth={1.5} fill="none" name="Alarm" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>
      )}

      {/* Pagination */}
      <div className="flex items-center justify-between pt-2">
        <p className="text-[10px] sm:text-xs text-muted-foreground">
          {currentPage === 1 ? 'Ringkasan & Distribusi' : 'Tren Mingguan'} &bull; Halaman {currentPage}/{totalPages}
        </p>
        <div className="flex items-center gap-1.5">
          <Button
            variant="outline"
            size="icon"
            className="h-7 w-7 sm:h-8 sm:w-8 border-border/60"
            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
            disabled={currentPage === 1}
          >
            <ChevronLeft className="h-3.5 w-3.5" />
          </Button>
          <span className="text-xs text-muted-foreground px-2">
            {currentPage}/{totalPages}
          </span>
          <Button
            variant="outline"
            size="icon"
            className="h-7 w-7 sm:h-8 sm:w-8 border-border/60"
            onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
            disabled={currentPage === totalPages}
          >
            <ChevronRight className="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>
    </div>
  );
}
