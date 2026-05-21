import { create } from 'zustand';

type ConnectionStatus = 'connecting' | 'connected' | 'disconnected' | 'error';

interface WebSocketState {
  status: ConnectionStatus;
  reconnectAttempts: number;
  lastConnected: string | null;
  error: string | null;
  setStatus: (status: ConnectionStatus) => void;
  setReconnectAttempts: (attempts: number) => void;
  setLastConnected: (time: string) => void;
  setError: (error: string | null) => void;
  reset: () => void;
}

export const useWebSocketStore = create<WebSocketState>((set) => ({
  status: 'disconnected',
  reconnectAttempts: 0,
  lastConnected: null,
  error: null,

  setStatus: (status) => set({ status }),
  setReconnectAttempts: (reconnectAttempts) => set({ reconnectAttempts }),
  setLastConnected: (lastConnected) => set({ lastConnected }),
  setError: (error) => set({ error }),
  reset: () =>
    set({
      status: 'disconnected',
      reconnectAttempts: 0,
      error: null,
    }),
}));
