'use client';

import { useEffect, useRef, useCallback } from 'react';
import { io, type Socket } from 'socket.io-client';
import { WS_URL, RECONNECT_INTERVAL, MAX_RECONNECT_ATTEMPTS } from '@/constants';
import { useWebSocketStore } from '@/stores';
import { useMonitoringStore } from '@/stores';
import { useNotificationStore } from '@/stores';
import type { VaultStatusUpdate, AlarmEvent, Notification } from '@/types';
import { generateId } from '@/lib/utils';

export function useSocket() {
  const socketRef = useRef<Socket | null>(null);
  const reconnectTimerRef = useRef<NodeJS.Timeout | null>(null);

  const { setStatus, setReconnectAttempts, setLastConnected, setError, reconnectAttempts } =
    useWebSocketStore();
  const { updateVaultStatus, addAlarm } = useMonitoringStore();
  const { addNotification } = useNotificationStore();

  const connect = useCallback(() => {
    if (socketRef.current?.connected) return;

    setStatus('connecting');

    const socket = io(WS_URL, {
      transports: ['websocket'],
      reconnection: false,
      timeout: 10000,
    });

    socket.on('connect', () => {
      setStatus('connected');
      setLastConnected(new Date().toISOString());
      setReconnectAttempts(0);
      setError(null);
    });

    socket.on('disconnect', () => {
      setStatus('disconnected');
      attemptReconnect();
    });

    socket.on('connect_error', (error) => {
      setStatus('error');
      setError(error.message);
      attemptReconnect();
    });

    // Vault status updates
    socket.on('vault:status', (data: VaultStatusUpdate) => {
      updateVaultStatus(data);
    });

    // Alarm events
    socket.on('alarm:triggered', (data: AlarmEvent) => {
      addAlarm(data);
      addNotification({
        id: generateId(),
        title: 'Alarm Triggered',
        message: `${data.alarmType} at ${data.branchName}`,
        type: 'error',
        category: 'alarm',
        read: false,
        timestamp: data.timestamp,
      });
    });

    // Notification events
    socket.on('notification', (data: Notification) => {
      addNotification(data);
    });

    socketRef.current = socket;
  }, [setStatus, setLastConnected, setReconnectAttempts, setError, updateVaultStatus, addAlarm, addNotification]);

  const attemptReconnect = useCallback(() => {
    if (reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
      setError('Max reconnection attempts reached');
      return;
    }

    reconnectTimerRef.current = setTimeout(() => {
      setReconnectAttempts(reconnectAttempts + 1);
      connect();
    }, RECONNECT_INTERVAL);
  }, [reconnectAttempts, setReconnectAttempts, setError, connect]);

  const disconnect = useCallback(() => {
    if (reconnectTimerRef.current) {
      clearTimeout(reconnectTimerRef.current);
    }
    if (socketRef.current) {
      socketRef.current.disconnect();
      socketRef.current = null;
    }
    setStatus('disconnected');
  }, [setStatus]);

  const emit = useCallback((event: string, data?: unknown) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit(event, data);
    }
  }, []);

  const on = useCallback((event: string, handler: (...args: unknown[]) => void) => {
    socketRef.current?.on(event, handler);
    return () => {
      socketRef.current?.off(event, handler);
    };
  }, []);

  useEffect(() => {
    return () => {
      disconnect();
    };
  }, [disconnect]);

  return { connect, disconnect, emit, on, socket: socketRef.current };
}
