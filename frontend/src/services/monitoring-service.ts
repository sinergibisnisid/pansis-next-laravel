import apiClient from './api-client';
import type {
  VaultMonitor,
  DashboardStats,
  ActivityEvent,
  PaginatedResponse,
} from '@/types';

// Mock Dashboard Stats
const MOCK_STATS: DashboardStats = {
  totalBranches: 48,
  activeVaults: 52,
  activeAlarms: 3,
  onlineDevices: 312,
  totalDevices: 340,
  activeUsers: 24,
  todayActivities: 156,
  mqttConnections: 48,
  serverStatus: 'healthy',
};

// Mock Vault Monitors
const MOCK_VAULTS: VaultMonitor[] = [
  {
    id: 'v-001',
    branchId: 'br-001',
    branchName: 'KCP Bandung Utara',
    branchCode: 'BDG-01',
    vaultName: 'Vault Utama',
    status: 'closed',
    deviceStatus: 'online',
    alarmStatus: null,
    currentUser: null,
    entryTime: null,
    duration: null,
    temperature: 22.5,
    humidity: 45,
    lastActivity: '2024-03-15T08:30:00Z',
    streamUrl: null,
    snapshotUrl: '/images/vault-snapshot-1.jpg',
    devices: [],
  },
  {
    id: 'v-002',
    branchId: 'br-002',
    branchName: 'KCP Bandung Selatan',
    branchCode: 'BDG-02',
    vaultName: 'Vault Utama',
    status: 'open',
    deviceStatus: 'online',
    alarmStatus: null,
    currentUser: 'Budi Santoso',
    entryTime: '2024-03-15T09:15:00Z',
    duration: 342,
    temperature: 23.1,
    humidity: 48,
    lastActivity: '2024-03-15T09:15:00Z',
    streamUrl: 'rtsp://stream.example.com/vault-002',
    snapshotUrl: '/images/vault-snapshot-2.jpg',
    devices: [],
  },
  {
    id: 'v-003',
    branchId: 'br-003',
    branchName: 'KCP Cimahi',
    branchCode: 'CMH-01',
    vaultName: 'Vault Utama',
    status: 'alarm',
    deviceStatus: 'online',
    alarmStatus: 'active',
    currentUser: null,
    entryTime: null,
    duration: null,
    temperature: 24.0,
    humidity: 52,
    lastActivity: '2024-03-15T09:20:00Z',
    streamUrl: 'rtsp://stream.example.com/vault-003',
    snapshotUrl: '/images/vault-snapshot-3.jpg',
    devices: [],
  },
  {
    id: 'v-004',
    branchId: 'br-004',
    branchName: 'KCP Garut',
    branchCode: 'GRT-01',
    vaultName: 'Vault Utama',
    status: 'closed',
    deviceStatus: 'offline',
    alarmStatus: null,
    currentUser: null,
    entryTime: null,
    duration: null,
    temperature: 0,
    humidity: 0,
    lastActivity: '2024-03-14T17:00:00Z',
    streamUrl: null,
    snapshotUrl: null,
    devices: [],
  },
  {
    id: 'v-005',
    branchId: 'br-005',
    branchName: 'KCP Sumedang',
    branchCode: 'SMD-01',
    vaultName: 'Vault Utama',
    status: 'locked',
    deviceStatus: 'online',
    alarmStatus: null,
    currentUser: null,
    entryTime: null,
    duration: null,
    temperature: 21.8,
    humidity: 44,
    lastActivity: '2024-03-15T07:00:00Z',
    streamUrl: null,
    snapshotUrl: '/images/vault-snapshot-5.jpg',
    devices: [],
  },
  {
    id: 'v-006',
    branchId: 'br-006',
    branchName: 'KCP Tasikmalaya',
    branchCode: 'TSK-01',
    vaultName: 'Vault Utama',
    status: 'open',
    deviceStatus: 'online',
    alarmStatus: null,
    currentUser: 'Siti Rahayu',
    entryTime: '2024-03-15T09:05:00Z',
    duration: 920,
    temperature: 23.5,
    humidity: 50,
    lastActivity: '2024-03-15T09:05:00Z',
    streamUrl: 'rtsp://stream.example.com/vault-006',
    snapshotUrl: '/images/vault-snapshot-6.jpg',
    devices: [],
  },
  {
    id: 'v-007',
    branchId: 'br-007',
    branchName: 'KCP Cianjur',
    branchCode: 'CJR-01',
    vaultName: 'Vault Utama',
    status: 'maintenance',
    deviceStatus: 'warning',
    alarmStatus: null,
    currentUser: null,
    entryTime: null,
    duration: null,
    temperature: 22.0,
    humidity: 46,
    lastActivity: '2024-03-15T06:00:00Z',
    streamUrl: null,
    snapshotUrl: '/images/vault-snapshot-7.jpg',
    devices: [],
  },
  {
    id: 'v-008',
    branchId: 'br-008',
    branchName: 'KCP Sukabumi',
    branchCode: 'SKB-01',
    vaultName: 'Vault Utama',
    status: 'closed',
    deviceStatus: 'online',
    alarmStatus: null,
    currentUser: null,
    entryTime: null,
    duration: null,
    temperature: 22.3,
    humidity: 47,
    lastActivity: '2024-03-15T08:45:00Z',
    streamUrl: null,
    snapshotUrl: '/images/vault-snapshot-8.jpg',
    devices: [],
  },
];

// Mock Activity Events
const MOCK_ACTIVITIES: ActivityEvent[] = [
  {
    id: 'act-001',
    timestamp: '2024-03-15T09:28:00Z',
    type: 'vault_open',
    title: 'Vault Opened',
    description: 'Budi Santoso membuka vault KCP Bandung Selatan',
    branchName: 'KCP Bandung Selatan',
    severity: 'info',
    userId: 'u-002',
    userName: 'Budi Santoso',
  },
  {
    id: 'act-002',
    timestamp: '2024-03-15T09:25:00Z',
    type: 'alarm',
    title: 'Alarm Triggered',
    description: 'Motion sensor triggered di vault KCP Cimahi',
    branchName: 'KCP Cimahi',
    severity: 'critical',
  },
  {
    id: 'act-003',
    timestamp: '2024-03-15T09:20:00Z',
    type: 'device_offline',
    title: 'Device Offline',
    description: 'Controller vault KCP Garut tidak merespon',
    branchName: 'KCP Garut',
    severity: 'warning',
  },
  {
    id: 'act-004',
    timestamp: '2024-03-15T09:15:00Z',
    type: 'vault_open',
    title: 'Vault Opened',
    description: 'Siti Rahayu membuka vault KCP Tasikmalaya',
    branchName: 'KCP Tasikmalaya',
    severity: 'info',
    userId: 'u-003',
    userName: 'Siti Rahayu',
  },
  {
    id: 'act-005',
    timestamp: '2024-03-15T09:00:00Z',
    type: 'login',
    title: 'User Login',
    description: 'Admin login dari IP 192.168.1.100',
    branchName: 'Kantor Pusat',
    severity: 'info',
    userId: 'u-001',
    userName: 'Administrator',
  },
  {
    id: 'act-006',
    timestamp: '2024-03-15T08:30:00Z',
    type: 'maintenance',
    title: 'Maintenance Started',
    description: 'Jadwal maintenance vault KCP Cianjur dimulai',
    branchName: 'KCP Cianjur',
    severity: 'info',
  },
  {
    id: 'act-007',
    timestamp: '2024-03-15T07:30:00Z',
    type: 'vault_close',
    title: 'Vault Closed',
    description: 'Vault KCP Bandung Utara ditutup oleh sistem',
    branchName: 'KCP Bandung Utara',
    severity: 'info',
  },
];

export const monitoringService = {
  getStats: async (): Promise<DashboardStats> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 500));
      return MOCK_STATS;
    }
    const response = await apiClient.get('/monitoring/stats');
    return response.data;
  },

  getVaults: async (): Promise<VaultMonitor[]> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 800));
      return MOCK_VAULTS;
    }
    const response = await apiClient.get('/monitoring/vaults');
    return response.data;
  },

  getVaultById: async (id: string): Promise<VaultMonitor> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 300));
      const vault = MOCK_VAULTS.find((v) => v.id === id);
      if (!vault) throw new Error('Vault not found');
      return vault;
    }
    const response = await apiClient.get(`/monitoring/vaults/${id}`);
    return response.data;
  },

  getActivities: async (page = 1, pageSize = 20): Promise<PaginatedResponse<ActivityEvent>> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 500));
      return {
        data: MOCK_ACTIVITIES,
        total: MOCK_ACTIVITIES.length,
        page,
        pageSize,
        totalPages: 1,
      };
    }
    const response = await apiClient.get('/monitoring/activities', {
      params: { page, pageSize },
    });
    return response.data;
  },
};
