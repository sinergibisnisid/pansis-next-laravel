// ============================================================
// PANSIN ACCESS - Constants
// ============================================================

export const APP_NAME = 'PANSIN ACCESS';
export const APP_DESCRIPTION = 'Smart Vault Monitoring System';
export const APP_VERSION = '1.0.0';
export const COMPANY_NAME = 'Bank BJB';

// API Configuration
export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
export const WS_URL = process.env.NEXT_PUBLIC_WS_URL || 'http://localhost:8000';
export const WEBRTC_URL = process.env.NEXT_PUBLIC_WEBRTC_URL || 'http://localhost:8554';

// Auth Constants
export const TOKEN_KEY = 'pansis_access_token';
export const REFRESH_TOKEN_KEY = 'pansis_refresh_token';
export const TOKEN_EXPIRY_KEY = 'pansis_token_expiry';
export const USER_KEY = 'pansis_user';

// Pagination
export const DEFAULT_PAGE_SIZE = 10;
export const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];

// Realtime
export const HEARTBEAT_INTERVAL = 30000; // 30 seconds
export const RECONNECT_INTERVAL = 5000; // 5 seconds
export const MAX_RECONNECT_ATTEMPTS = 10;

// Theme Colors
export const COLORS = {
  primary: {
    50: '#eff6ff',
    100: '#dbeafe',
    200: '#bfdbfe',
    300: '#93c5fd',
    400: '#60a5fa',
    500: '#1e40af', // Bank BJB Blue
    600: '#1e3a8a',
    700: '#1e3380',
    800: '#172554',
    900: '#0f172a',
  },
  cyan: {
    400: '#22d3ee',
    500: '#06b6d4',
    600: '#0891b2',
  },
  success: '#10b981',
  warning: '#f59e0b',
  danger: '#ef4444',
  info: '#3b82f6',
} as const;

// Status Labels
export const VAULT_STATUS_LABELS: Record<string, string> = {
  open: 'OPEN',
  closed: 'CLOSED',
  locked: 'LOCKED',
  alarm: 'ALARM',
  maintenance: 'MAINTENANCE',
};

export const DEVICE_STATUS_LABELS: Record<string, string> = {
  online: 'Online',
  offline: 'Offline',
  warning: 'Warning',
  error: 'Error',
};

export const ALARM_STATUS_LABELS: Record<string, string> = {
  active: 'Active',
  acknowledged: 'Acknowledged',
  resolved: 'Resolved',
  silenced: 'Silenced',
};

// Status Colors for badges
export const VAULT_STATUS_COLORS: Record<string, string> = {
  open: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
  closed: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
  locked: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
  alarm: 'bg-red-500/20 text-red-400 border-red-500/30',
  maintenance: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
};

export const DEVICE_STATUS_COLORS: Record<string, string> = {
  online: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
  offline: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
  warning: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
  error: 'bg-red-500/20 text-red-400 border-red-500/30',
};

// Navigation Menu Items
export const SIDEBAR_MENU = [
  {
    group: 'Overview',
    items: [
      { label: 'Dashboard', href: '/dashboard', icon: 'LayoutDashboard', permission: 'dashboard.view' as const },
      { label: 'Live Monitoring', href: '/monitoring', icon: 'Monitor', permission: 'monitoring.view' as const },
    ],
  },
  {
    group: 'Management',
    items: [
      { label: 'Organization', href: '/organization', icon: 'Building2', permission: 'organization.view' as const },
      { label: 'Users', href: '/users', icon: 'Users', permission: 'users.view' as const },
      { label: 'Devices', href: '/devices', icon: 'Cpu', permission: 'devices.view' as const },
    ],
  },
  {
    group: 'Operations',
    items: [
      { label: 'Reports', href: '/reports', icon: 'FileText', permission: 'reports.view' as const },
      { label: 'Maintenance', href: '/maintenance', icon: 'Wrench', permission: 'maintenance.view' as const },
      { label: 'Notifications', href: '/notifications', icon: 'Bell', permission: 'notifications.view' as const },
    ],
  },
  {
    group: 'System',
    items: [
      { label: 'MQTT', href: '/mqtt', icon: 'Radio', permission: 'mqtt.view' as const },
      { label: 'Server', href: '/server', icon: 'Server', permission: 'server.view' as const },
      { label: 'Settings', href: '/settings', icon: 'Settings', permission: 'settings.view' as const },
    ],
  },
] as const;

// Device Types
export const DEVICE_TYPES = [
  { value: 'controller', label: 'Controller' },
  { value: 'fingerprint', label: 'Fingerprint Scanner' },
  { value: 'camera', label: 'Camera' },
  { value: 'sensor_door', label: 'Door Sensor' },
  { value: 'sensor_motion', label: 'Motion Sensor' },
  { value: 'sensor_temperature', label: 'Temperature Sensor' },
  { value: 'alarm', label: 'Alarm' },
  { value: 'lock', label: 'Electronic Lock' },
] as const;

// Maintenance Types
export const MAINTENANCE_TYPES = [
  { value: 'cleaning', label: 'Cleaning' },
  { value: 'lubrication', label: 'Lubrication' },
  { value: 'inspection', label: 'Inspection' },
  { value: 'repair', label: 'Repair' },
  { value: 'calibration', label: 'Calibration' },
] as const;

// User Roles
export const USER_ROLES = [
  { value: 'super_admin', label: 'Super Admin' },
  { value: 'admin', label: 'Administrator' },
  { value: 'operator', label: 'Operator' },
  { value: 'viewer', label: 'Viewer' },
  { value: 'auditor', label: 'Auditor' },
] as const;

// Report Export Formats
export const EXPORT_FORMATS = [
  { value: 'pdf', label: 'PDF', icon: 'FileText' },
  { value: 'excel', label: 'Excel', icon: 'FileSpreadsheet' },
  { value: 'csv', label: 'CSV', icon: 'FileDown' },
] as const;
