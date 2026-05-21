'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Radio,
  Wifi,
  WifiOff,
  Send,
  RefreshCw,
  Plus,
  CheckCircle,
  XCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { MQTTBroker, MQTTTopic } from '@/types';

const mockBroker: MQTTBroker = {
  id: 'broker-001',
  name: 'PANSIN MQTT Broker',
  host: 'mqtt.pansin.bankbjb.co.id',
  port: 8883,
  username: 'pansin_system',
  useTLS: true,
  status: 'connected',
  lastConnected: '2024-03-15T00:00:00Z',
  topics: [
    { id: 't-001', topic: 'vault/+/status', description: 'Vault status updates', qos: 1, retained: true, lastMessage: '{"status":"closed","temp":22.5}', lastReceived: '2024-03-15T09:30:00Z' },
    { id: 't-002', topic: 'vault/+/alarm', description: 'Alarm events', qos: 2, retained: false, lastMessage: '{"type":"motion","severity":"critical"}', lastReceived: '2024-03-15T09:20:00Z' },
    { id: 't-003', topic: 'vault/+/heartbeat', description: 'Device heartbeat', qos: 0, retained: false, lastMessage: '{"uptime":86400,"signal":95}', lastReceived: '2024-03-15T09:30:00Z' },
    { id: 't-004', topic: 'vault/+/access', description: 'Access events', qos: 1, retained: false, lastMessage: '{"user":"budi","action":"open"}', lastReceived: '2024-03-15T09:15:00Z' },
    { id: 't-005', topic: 'vault/+/environment', description: 'Temperature & humidity', qos: 0, retained: true, lastMessage: '{"temp":22.5,"humidity":45}', lastReceived: '2024-03-15T09:30:00Z' },
    { id: 't-006', topic: 'system/health', description: 'System health check', qos: 1, retained: true, lastMessage: '{"cpu":34,"ram":62,"status":"healthy"}', lastReceived: '2024-03-15T09:30:00Z' },
  ],
};

export default function MQTTPage() {
  const [testTopic, setTestTopic] = useState('');
  const [testMessage, setTestMessage] = useState('');
  const [connectionTest, setConnectionTest] = useState<'idle' | 'testing' | 'success' | 'error'>('idle');

  const handleConnectionTest = () => {
    setConnectionTest('testing');
    setTimeout(() => {
      setConnectionTest('success');
      setTimeout(() => setConnectionTest('idle'), 3000);
    }, 2000);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">MQTT Management</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Manage MQTT broker connections and topics
          </p>
        </div>
        <Button variant="outline" size="sm" className="gap-2" onClick={handleConnectionTest}>
          <RefreshCw className={cn('h-4 w-4', connectionTest === 'testing' && 'animate-spin')} />
          Test Connection
        </Button>
      </div>

      {/* Broker Status */}
      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        className="rounded-xl border border-border/40 bg-card/50 p-6"
      >
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-4">
            <div className={cn(
              'flex h-12 w-12 items-center justify-center rounded-xl',
              mockBroker.status === 'connected'
                ? 'bg-emerald-500/20 border border-emerald-500/30'
                : 'bg-red-500/20 border border-red-500/30'
            )}>
              {mockBroker.status === 'connected' ? (
                <Wifi className="h-6 w-6 text-emerald-400" />
              ) : (
                <WifiOff className="h-6 w-6 text-red-400" />
              )}
            </div>
            <div>
              <h3 className="text-lg font-semibold">{mockBroker.name}</h3>
              <p className="text-sm text-muted-foreground mt-0.5">
                {mockBroker.host}:{mockBroker.port}
              </p>
            </div>
          </div>
          <Badge
            variant="outline"
            className={cn(
              mockBroker.status === 'connected'
                ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                : 'bg-red-500/20 text-red-400 border-red-500/30'
            )}
          >
            <span className="relative flex h-2 w-2 mr-1.5">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-current opacity-75" />
              <span className="relative inline-flex h-2 w-2 rounded-full bg-current" />
            </span>
            {mockBroker.status}
          </Badge>
        </div>

        <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div className="rounded-lg bg-muted/30 p-3">
            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Protocol</p>
            <p className="text-sm font-medium mt-1">{mockBroker.useTLS ? 'MQTTS' : 'MQTT'}</p>
          </div>
          <div className="rounded-lg bg-muted/30 p-3">
            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Topics</p>
            <p className="text-sm font-medium mt-1">{mockBroker.topics.length}</p>
          </div>
          <div className="rounded-lg bg-muted/30 p-3">
            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Username</p>
            <p className="text-sm font-medium mt-1">{mockBroker.username}</p>
          </div>
          <div className="rounded-lg bg-muted/30 p-3">
            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Uptime</p>
            <p className="text-sm font-medium mt-1">15d 9h 30m</p>
          </div>
        </div>

        {/* Connection Test Result */}
        {connectionTest === 'success' && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="mt-4 flex items-center gap-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 px-4 py-2"
          >
            <CheckCircle className="h-4 w-4 text-emerald-400" />
            <span className="text-sm text-emerald-400">Connection test successful - latency 12ms</span>
          </motion.div>
        )}
        {connectionTest === 'error' && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="mt-4 flex items-center gap-2 rounded-lg bg-red-500/10 border border-red-500/20 px-4 py-2"
          >
            <XCircle className="h-4 w-4 text-red-400" />
            <span className="text-sm text-red-400">Connection test failed</span>
          </motion.div>
        )}
      </motion.div>

      {/* Topics & Publish */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Topic List */}
        <div className="lg:col-span-2">
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <CardTitle className="text-sm font-medium">Subscribed Topics</CardTitle>
              <Button variant="ghost" size="sm" className="gap-1.5 text-xs">
                <Plus className="h-3.5 w-3.5" />
                Add Topic
              </Button>
            </CardHeader>
            <CardContent className="space-y-2">
              {mockBroker.topics.map((topic) => (
                <div
                  key={topic.id}
                  className="flex items-start justify-between rounded-lg border border-border/30 bg-muted/20 p-3 hover:bg-muted/30 transition-colors"
                >
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <code className="text-xs font-mono text-blue-400">{topic.topic}</code>
                      <Badge variant="outline" className="text-[9px] h-4">
                        QoS {topic.qos}
                      </Badge>
                      {topic.retained && (
                        <Badge variant="outline" className="text-[9px] h-4 bg-amber-500/10 text-amber-400 border-amber-500/30">
                          Retained
                        </Badge>
                      )}
                    </div>
                    <p className="text-[11px] text-muted-foreground">{topic.description}</p>
                    {topic.lastMessage && (
                      <code className="text-[10px] text-muted-foreground/70 block truncate max-w-md">
                        Last: {topic.lastMessage}
                      </code>
                    )}
                  </div>
                  <span className="text-[10px] text-muted-foreground whitespace-nowrap">
                    {topic.lastReceived
                      ? new Date(topic.lastReceived).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                      : 'N/A'}
                  </span>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>

        {/* Publish Test */}
        <div>
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium">Publish Message</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-1.5">
                <label className="text-xs text-muted-foreground">Topic</label>
                <Input
                  placeholder="vault/bdg01/test"
                  value={testTopic}
                  onChange={(e) => setTestTopic(e.target.value)}
                  className="bg-background/50 border-border/40 font-mono text-xs"
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-xs text-muted-foreground">Message (JSON)</label>
                <textarea
                  placeholder='{"key": "value"}'
                  value={testMessage}
                  onChange={(e) => setTestMessage(e.target.value)}
                  className="w-full h-24 rounded-md border border-border/40 bg-background/50 px-3 py-2 text-xs font-mono resize-none focus:outline-none focus:ring-2 focus:ring-ring/20"
                />
              </div>
              <Button className="w-full gap-2" size="sm">
                <Send className="h-3.5 w-3.5" />
                Publish
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
