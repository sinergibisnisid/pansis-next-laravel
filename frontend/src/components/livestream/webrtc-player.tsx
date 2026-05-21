'use client';

import { useEffect, useRef, useState, useCallback } from 'react';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import {
  Maximize2,
  Minimize2,
  Volume2,
  VolumeX,
  RefreshCw,
  Wifi,
  WifiOff,
} from 'lucide-react';
import { Button } from '@/components/ui/button';

interface WebRTCPlayerProps {
  streamUrl: string | null;
  className?: string;
  autoPlay?: boolean;
  muted?: boolean;
  showControls?: boolean;
  onStatusChange?: (status: 'connecting' | 'connected' | 'disconnected' | 'error') => void;
}

export function WebRTCPlayer({
  streamUrl,
  className,
  autoPlay = true,
  muted: initialMuted = true,
  showControls = true,
  onStatusChange,
}: WebRTCPlayerProps) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const peerConnectionRef = useRef<RTCPeerConnection | null>(null);
  const [status, setStatus] = useState<'connecting' | 'connected' | 'disconnected' | 'error'>('disconnected');
  const [isMuted, setIsMuted] = useState(initialMuted);
  const [isFullscreen, setIsFullscreen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const updateStatus = useCallback((newStatus: typeof status) => {
    setStatus(newStatus);
    onStatusChange?.(newStatus);
  }, [onStatusChange]);

  const connect = useCallback(async () => {
    if (!streamUrl) {
      updateStatus('disconnected');
      return;
    }

    updateStatus('connecting');

    try {
      const pc = new RTCPeerConnection({
        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
      });

      pc.addTransceiver('video', { direction: 'recvonly' });
      pc.addTransceiver('audio', { direction: 'recvonly' });

      pc.ontrack = (event) => {
        if (videoRef.current && event.streams[0]) {
          videoRef.current.srcObject = event.streams[0];
          updateStatus('connected');
        }
      };

      pc.oniceconnectionstatechange = () => {
        switch (pc.iceConnectionState) {
          case 'connected':
            updateStatus('connected');
            break;
          case 'disconnected':
          case 'closed':
            updateStatus('disconnected');
            break;
          case 'failed':
            updateStatus('error');
            break;
        }
      };

      const offer = await pc.createOffer();
      await pc.setLocalDescription(offer);

      // In production, this would send the offer to a signaling server
      // For demo, we simulate a connection
      peerConnectionRef.current = pc;

      // Simulate connection for demo
      setTimeout(() => {
        if (status === 'connecting') {
          updateStatus('connected');
        }
      }, 2000);
    } catch {
      updateStatus('error');
    }
  }, [streamUrl, updateStatus, status]);

  const disconnect = useCallback(() => {
    if (peerConnectionRef.current) {
      peerConnectionRef.current.close();
      peerConnectionRef.current = null;
    }
    if (videoRef.current) {
      videoRef.current.srcObject = null;
    }
    updateStatus('disconnected');
  }, [updateStatus]);

  const reconnect = useCallback(() => {
    disconnect();
    setTimeout(connect, 500);
  }, [disconnect, connect]);

  const toggleFullscreen = useCallback(async () => {
    if (!containerRef.current) return;

    if (!document.fullscreenElement) {
      await containerRef.current.requestFullscreen();
      setIsFullscreen(true);
    } else {
      await document.exitFullscreen();
      setIsFullscreen(false);
    }
  }, []);

  const toggleMute = useCallback(() => {
    if (videoRef.current) {
      videoRef.current.muted = !isMuted;
      setIsMuted(!isMuted);
    }
  }, [isMuted]);

  useEffect(() => {
    if (autoPlay && streamUrl) {
      connect();
    }
    return () => {
      disconnect();
    };
  }, [streamUrl, autoPlay, connect, disconnect]);

  useEffect(() => {
    const handleFullscreenChange = () => {
      setIsFullscreen(!!document.fullscreenElement);
    };
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange);
  }, []);

  return (
    <div
      ref={containerRef}
      className={cn(
        'relative overflow-hidden rounded-lg bg-slate-900 group',
        className
      )}
    >
      {/* Video Element */}
      <video
        ref={videoRef}
        autoPlay={autoPlay}
        muted={isMuted}
        playsInline
        className="h-full w-full object-cover"
      />

      {/* Status Overlay */}
      {status !== 'connected' && (
        <div className="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/90">
          {status === 'connecting' && (
            <motion.div
              animate={{ rotate: 360 }}
              transition={{ duration: 1, repeat: Infinity, ease: 'linear' }}
            >
              <RefreshCw className="h-8 w-8 text-blue-400" />
            </motion.div>
          )}
          {status === 'disconnected' && (
            <WifiOff className="h-8 w-8 text-slate-500" />
          )}
          {status === 'error' && (
            <WifiOff className="h-8 w-8 text-red-400" />
          )}
          <p className="mt-2 text-xs text-slate-400 capitalize">{status}</p>
          {(status === 'disconnected' || status === 'error') && (
            <Button
              variant="ghost"
              size="sm"
              className="mt-3 gap-2 text-xs"
              onClick={reconnect}
            >
              <RefreshCw className="h-3.5 w-3.5" />
              Reconnect
            </Button>
          )}
        </div>
      )}

      {/* Live Indicator */}
      {status === 'connected' && (
        <div className="absolute top-2 left-2 flex items-center gap-1.5 rounded-md bg-black/60 px-2 py-1">
          <span className="relative flex h-2 w-2">
            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75" />
            <span className="relative inline-flex h-2 w-2 rounded-full bg-red-500" />
          </span>
          <span className="text-[10px] font-medium text-white">LIVE</span>
        </div>
      )}

      {/* Connection Status */}
      <div className="absolute top-2 right-2">
        {status === 'connected' ? (
          <Wifi className="h-4 w-4 text-emerald-400" />
        ) : (
          <WifiOff className="h-4 w-4 text-slate-500" />
        )}
      </div>

      {/* Controls */}
      {showControls && (
        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Button
                variant="ghost"
                size="icon"
                className="h-7 w-7 text-white/80 hover:text-white"
                onClick={toggleMute}
              >
                {isMuted ? (
                  <VolumeX className="h-4 w-4" />
                ) : (
                  <Volume2 className="h-4 w-4" />
                )}
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-7 w-7 text-white/80 hover:text-white"
                onClick={reconnect}
              >
                <RefreshCw className="h-3.5 w-3.5" />
              </Button>
            </div>
            <Button
              variant="ghost"
              size="icon"
              className="h-7 w-7 text-white/80 hover:text-white"
              onClick={toggleFullscreen}
            >
              {isFullscreen ? (
                <Minimize2 className="h-4 w-4" />
              ) : (
                <Maximize2 className="h-4 w-4" />
              )}
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
