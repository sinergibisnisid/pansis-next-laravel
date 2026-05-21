import apiClient from './api-client';
import type {
  LoginCredentials,
  OTPVerification,
  User,
  AuthTokens,
} from '@/types';

// Mock data for development
const MOCK_USER: User = {
  id: '1',
  username: 'admin',
  email: 'admin@bankbjb.co.id',
  fullName: 'Administrator',
  role: 'super_admin',
  permissions: [
    'dashboard.view',
    'monitoring.view',
    'monitoring.control',
    'organization.view',
    'organization.manage',
    'users.view',
    'users.manage',
    'devices.view',
    'devices.manage',
    'reports.view',
    'reports.export',
    'maintenance.view',
    'maintenance.manage',
    'mqtt.view',
    'mqtt.manage',
    'notifications.view',
    'notifications.manage',
    'settings.view',
    'settings.manage',
    'server.view',
  ],
  branchId: 'hq-001',
  branchName: 'Kantor Pusat',
  avatar: undefined,
  status: 'active',
  lastLogin: new Date().toISOString(),
  createdAt: '2024-01-01T00:00:00Z',
  updatedAt: new Date().toISOString(),
};

const MOCK_TOKENS: AuthTokens = {
  accessToken: 'mock-access-token-xyz',
  refreshToken: 'mock-refresh-token-xyz',
  expiresAt: Date.now() + 3600000,
};

export const authService = {
  login: async (credentials: LoginCredentials): Promise<{ user: User; tokens: AuthTokens }> => {
    // Mock implementation for development
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 1500));
      if (credentials.username === 'admin' && credentials.password === 'admin123') {
        return { user: MOCK_USER, tokens: MOCK_TOKENS };
      }
      throw new Error('Invalid credentials');
    }

    const response = await apiClient.post('/auth/login', credentials);
    return response.data;
  },

  verifyOTP: async (data: OTPVerification): Promise<{ verified: boolean }> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 1000));
      if (data.otp === '123456') {
        return { verified: true };
      }
      throw new Error('Invalid OTP');
    }

    const response = await apiClient.post('/auth/verify-otp', data);
    return response.data;
  },

  forgotPassword: async (email: string): Promise<{ message: string }> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      await new Promise((resolve) => setTimeout(resolve, 1000));
      return { message: 'Reset link sent to your email' };
    }

    const response = await apiClient.post('/auth/forgot-password', { email });
    return response.data;
  },

  refreshToken: async (refreshToken: string): Promise<AuthTokens> => {
    const response = await apiClient.post('/auth/refresh', { refreshToken });
    return response.data;
  },

  logout: async (): Promise<void> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      return;
    }
    await apiClient.post('/auth/logout');
  },

  getProfile: async (): Promise<User> => {
    if (process.env.NEXT_PUBLIC_USE_MOCK === 'true' || !process.env.NEXT_PUBLIC_API_URL) {
      return MOCK_USER;
    }
    const response = await apiClient.get('/auth/profile');
    return response.data;
  },
};
