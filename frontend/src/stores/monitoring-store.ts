import { create } from 'zustand';
import type { VaultMonitor, VaultStatusUpdate, AlarmEvent, DashboardStats } from '@/types';

interface MonitoringState {
  vaults: VaultMonitor[];
  stats: DashboardStats | null;
  activeAlarms: AlarmEvent[];
  selectedVault: VaultMonitor | null;
  filter: MonitoringFilter;
  setVaults: (vaults: VaultMonitor[]) => void;
  updateVaultStatus: (update: VaultStatusUpdate) => void;
  setStats: (stats: DashboardStats) => void;
  addAlarm: (alarm: AlarmEvent) => void;
  removeAlarm: (vaultId: string) => void;
  setSelectedVault: (vault: VaultMonitor | null) => void;
  setFilter: (filter: Partial<MonitoringFilter>) => void;
  resetFilter: () => void;
}

interface MonitoringFilter {
  branchId: string | null;
  status: string | null;
  deviceStatus: string | null;
  search: string;
}

const defaultFilter: MonitoringFilter = {
  branchId: null,
  status: null,
  deviceStatus: null,
  search: '',
};

export const useMonitoringStore = create<MonitoringState>((set) => ({
  vaults: [],
  stats: null,
  activeAlarms: [],
  selectedVault: null,
  filter: defaultFilter,

  setVaults: (vaults) => set({ vaults }),

  updateVaultStatus: (update) =>
    set((state) => ({
      vaults: state.vaults.map((vault) =>
        vault.id === update.vaultId
          ? {
              ...vault,
              status: update.status,
              deviceStatus: update.deviceStatus,
              currentUser: update.currentUser,
              lastActivity: update.timestamp,
            }
          : vault
      ),
    })),

  setStats: (stats) => set({ stats }),

  addAlarm: (alarm) =>
    set((state) => ({
      activeAlarms: [alarm, ...state.activeAlarms].slice(0, 50),
    })),

  removeAlarm: (vaultId) =>
    set((state) => ({
      activeAlarms: state.activeAlarms.filter((a) => a.vaultId !== vaultId),
    })),

  setSelectedVault: (selectedVault) => set({ selectedVault }),

  setFilter: (filter) =>
    set((state) => ({
      filter: { ...state.filter, ...filter },
    })),

  resetFilter: () => set({ filter: defaultFilter }),
}));
