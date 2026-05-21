'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Building2,
  Filter,
  Video,
  VideoOff,
  Maximize2,
  User,
  Clock,
  Thermometer,
  AlertTriangle,
} from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface VaultStream {
  id: string;
  branchName: string;
  branchCode: string;
  organization: string;
  status: 'online' | 'offline';
  vaultStatus: 'open' | 'closed' | 'alarm' | 'locked';
  currentUser: string | null;
  temperature: number;
  duration: string | null;
}

const mockStreams: VaultStream[] = [
  { id: 'v-001', branchName: 'KCP Bandung Utara', branchCode: 'BDG-01', organization: 'Kanwil Bandung', status: 'online', vaultStatus: 'closed', currentUser: null, temperature: 22.5, duration: null },
  { id: 'v-002', branchName: 'KCP Bandung Selatan', branchCode: 'BDG-02', organization: 'Kanwil Bandung', status: 'online', vaultStatus: 'open', currentUser: 'Budi Santoso', temperature: 23.1, duration: '05:42' },
  { id: 'v-003', branchName: 'KCP Cimahi', branchCode: 'CMH-01', organization: 'Kanwil Bandung', status: 'online', vaultStatus: 'alarm', currentUser: null, temperature: 24.0, duration: null },
  { id: 'v-004', branchName: 'KCP Garut', branchCode: 'GRT-01', organization: 'Kanwil Priangan', status: 'offline', vaultStatus: 'closed', currentUser: null, temperature: 0, duration: null },
  { id: 'v-005', branchName: 'KCP Sumedang', branchCode: 'SMD-01', organization: 'Kanwil Priangan', status: 'online', vaultStatus: 'locked', currentUser: null, temperature: 21.8, duration: null },
  { id: 'v-006', branchName: 'KCP Tasikmalaya', branchCode: 'TSK-01', organization: 'Kanwil Priangan', status: 'online', vaultStatus: 'open', currentUser: 'Siti Rahayu', temperature: 23.5, duration: '15:20' },
  { id: 'v-007', branchName: 'KCP Cianjur', branchCode: 'CJR-01', organization: 'Kanwil Sukabumi', status: 'online', vaultStatus: 'closed', currentUser: null, temperature: 22.0, duration: null },
  { id: 'v-008', branchName: 'KCP Sukabumi', branchCode: 'SKB-01', organization: 'Kanwil Sukabumi', status: 'online', vaultStatus: 'closed', currentUser: null, temperature: 22.3, duration: null },
  { id: 'v-009', branchName: 'KCP Bogor', branchCode: 'BGR-01', organization: 'Kanwil Bogor', status: 'online', vaultStatus: 'closed', currentUser: null, temperature: 23.0, duration: null },
  { id: 'v-010', branchName: 'KCP Depok', branchCode: 'DPK-01', organization: 'Kanwil Bogor', status: 'online', vaultStatus: 'open', currentUser: 'Ahmad Hidayat', temperature: 22.8, duration: '02:15' },
  { id: 'v-011', branchName: 'KCP Bekasi', branchCode: 'BKS-01', organization: 'Kanwil Jakarta', status: 'online', vaultStatus: 'closed', currentUser: null, temperature: 23.2, duration: null },
  { id: 'v-012', branchName: 'KCP Karawang', branchCode: 'KRW-01', organization: 'Kanwil Jakarta', status: 'online', vaultStatus: 'locked', currentUser: null, temperature: 22.7, duration: null },
];

const organizations = ['Semua Organisasi', 'Kanwil Bandung', 'Kanwil Priangan', 'Kanwil Sukabumi', 'Kanwil Bogor', 'Kanwil Jakarta'];

const vaultStatusConfig: Record<string, { label: string; color: string; bgColor: string }> = {
  open: { label: 'OPEN', color: 'text-emerald-400', bgColor: 'bg-emerald-500/20 border-emerald-500/30' },
  closed: { label: 'CLOSED', color: 'text-slate-400', bgColor: 'bg-slate-500/20 border-slate-500/30' },
  alarm: { label: 'ALARM', color: 'text-red-400', bgColor: 'bg-red-500/20 border-red-500/30' },
  locked: { label: 'LOCKED', color: 'text-blue-400', bgColor: 'bg-blue-500/20 border-blue-500/30' },
};

export function LiveStreamGrid() {
  const [orgFilter, setOrgFilter] = useState('Semua Organisasi');
  const [branchHighlight, setBranchHighlight] = useState<string | null>(null);

  const filteredStreams = mockStreams.filter((stream) => {
    if (orgFilter !== 'Semua Organisasi' && stream.organization !== orgFilter) return false;
    return true;
  });

  return (
    <div className="space-y-4">
      {/* Filters */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <Select value={orgFilter} onValueChange={(v) => setOrgFilter(v ?? 'Semua Organisasi')}>
            <SelectTrigger className="w-[200px] bg-white/5 border-white/10 text-white text-xs">
              <Building2 className="h-3.5 w-3.5 mr-2 text-slate-400" />
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {organizations.map((org) => (
                <SelectItem key={org} value={org}>{org}</SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={branchHighlight ?? 'all'} onValueChange={(v) => setBranchHighlight(v === 'all' ? null : (v ?? null))}>
            <SelectTrigger className="w-[180px] bg-white/5 border-white/10 text-white text-xs">
              <Filter className="h-3.5 w-3.5 mr-2 text-slate-400" />
              <SelectValue placeholder="Highlight Cabang" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Cabang</SelectItem>
              {filteredStreams.map((s) => (
                <SelectItem key={s.id} value={s.id}>{s.branchName}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {/* Legend */}
        <div className="flex items-center gap-3 text-[10px]">
          <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-emerald-500" /> OPEN</span>
          <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-slate-500" /> CLOSED</span>
          <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-blue-500" /> LOCKED</span>
          <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-red-500" /> ALARM</span>
        </div>
      </div>

      {/* Stream Grid */}
      <div className="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {filteredStreams.map((stream, index) => {
          const isHighlighted = branchHighlight === null || branchHighlight === stream.id;
          const statusCfg = vaultStatusConfig[stream.vaultStatus];
          const isAlarm = stream.vaultStatus === 'alarm';

          return (
            <motion.div
              key={stream.id}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: isHighlighted ? 1 : 0.3, y: 0 }}
              transition={{ duration: 0.3, delay: index * 0.03 }}
              className={cn(
                'relative overflow-hidden rounded-xl border transition-all',
                isAlarm
                  ? 'border-red-500/40 shadow-lg shadow-red-500/10'
                  : isHighlighted
                  ? 'border-white/10 hover:border-blue-500/30'
                  : 'border-white/5',
                'bg-white/[0.02]'
              )}
            >
              {/* Alarm Pulse */}
              {isAlarm && (
                <motion.div
                  animate={{ opacity: [0.05, 0.15, 0.05] }}
                  transition={{ duration: 1.5, repeat: Infinity }}
                  className="absolute inset-0 bg-red-500/10"
                />
              )}

              {/* Video Area */}
              <div className="relative h-36 bg-slate-900/80 flex items-center justify-center">
                {stream.status === 'online' ? (
                  <>
                    {/* Simulated video feed background */}
                    <div className="absolute inset-0 bg-gradient-to-br from-slate-800/50 to-slate-900/80" />
                    <Video className="h-8 w-8 text-slate-700" />

                    {/* LIVE indicator */}
                    <div className="absolute top-2 left-2 flex items-center gap-1.5 rounded bg-black/60 px-1.5 py-0.5">
                      <span className="relative flex h-1.5 w-1.5">
                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75" />
                        <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-red-500" />
                      </span>
                      <span className="text-[9px] font-medium text-white/80">LIVE</span>
                    </div>

                    {/* Vault Status Badge */}
                    <div className="absolute top-2 right-2">
                      <Badge variant="outline" className={cn('text-[9px] px-1.5 py-0 border', statusCfg.bgColor, statusCfg.color)}>
                        {statusCfg.label}
                      </Badge>
                    </div>

                    {/* Fullscreen */}
                    <div className="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <Button variant="ghost" size="icon" className="h-6 w-6 text-white/60 hover:text-white bg-black/40">
                        <Maximize2 className="h-3 w-3" />
                      </Button>
                    </div>

                    {/* Alarm Overlay */}
                    {isAlarm && (
                      <motion.div
                        animate={{ opacity: [0.4, 0.8, 0.4] }}
                        transition={{ duration: 1, repeat: Infinity }}
                        className="absolute inset-0 flex items-center justify-center bg-red-900/30"
                      >
                        <AlertTriangle className="h-10 w-10 text-red-400/80" />
                      </motion.div>
                    )}
                  </>
                ) : (
                  <div className="flex flex-col items-center gap-1">
                    <VideoOff className="h-6 w-6 text-slate-700" />
                    <span className="text-[9px] text-slate-600 font-medium">OFFLINE</span>
                  </div>
                )}
              </div>

              {/* Info */}
              <div className="p-2.5 space-y-1.5">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs font-medium leading-tight truncate">{stream.branchName}</p>
                    <p className="text-[10px] text-slate-500">{stream.branchCode} &bull; {stream.organization}</p>
                  </div>
                  <div className={cn('h-2 w-2 rounded-full', stream.status === 'online' ? 'bg-emerald-500' : 'bg-slate-600')} />
                </div>

                {/* Current User */}
                {stream.currentUser && (
                  <div className="flex items-center justify-between rounded bg-white/5 px-2 py-1">
                    <span className="flex items-center gap-1.5 text-[10px]">
                      <User className="h-3 w-3 text-blue-400" />
                      {stream.currentUser}
                    </span>
                    <span className="flex items-center gap-1 text-[10px] text-slate-400">
                      <Clock className="h-2.5 w-2.5" />
                      {stream.duration}
                    </span>
                  </div>
                )}

                {/* Temperature */}
                {stream.temperature > 0 && (
                  <div className="flex items-center gap-1 text-[10px] text-slate-500">
                    <Thermometer className="h-3 w-3" />
                    {stream.temperature}°C
                  </div>
                )}
              </div>
            </motion.div>
          );
        })}
      </div>
    </div>
  );
}
