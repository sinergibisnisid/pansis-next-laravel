'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Bell,
  Mail,
  MessageSquare,
  ToggleLeft,
  Calendar,
  Save,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

export default function NotificationsPage() {
  const [config, setConfig] = useState({
    whatsappEnabled: true,
    whatsappNumber: '+6281234567890',
    emailEnabled: true,
    emailAddress: 'admin@bankbjb.co.id',
    alarmNotification: true,
    maintenanceReminder: true,
    deviceOffline: true,
    dailyReport: true,
    weeklyReport: true,
    monthlyReport: false,
    vaultAccess: true,
    systemAlert: true,
  });

  const updateConfig = (key: string, value: boolean | string) => {
    setConfig((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Notifications</h1>
          <p className="text-sm text-muted-foreground mt-1">
            Configure notification channels and preferences
          </p>
        </div>
        <Button className="gap-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
          <Save className="h-4 w-4" />
          Save Changes
        </Button>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* WhatsApp Configuration */}
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}>
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/20 border border-emerald-500/30">
                    <MessageSquare className="h-4 w-4 text-emerald-400" />
                  </div>
                  <CardTitle className="text-sm font-medium">WhatsApp</CardTitle>
                </div>
                <Switch
                  checked={config.whatsappEnabled}
                  onCheckedChange={(v) => updateConfig('whatsappEnabled', v)}
                />
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-1.5">
                <label className="text-xs text-muted-foreground">Phone Number</label>
                <Input
                  value={config.whatsappNumber}
                  onChange={(e) => updateConfig('whatsappNumber', e.target.value)}
                  placeholder="+62..."
                  className="bg-background/50 border-border/40"
                  disabled={!config.whatsappEnabled}
                />
              </div>
              <p className="text-[11px] text-muted-foreground">
                Notifications will be sent via WhatsApp Business API
              </p>
            </CardContent>
          </Card>
        </motion.div>

        {/* Email Configuration */}
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }}>
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/20 border border-blue-500/30">
                    <Mail className="h-4 w-4 text-blue-400" />
                  </div>
                  <CardTitle className="text-sm font-medium">Email</CardTitle>
                </div>
                <Switch
                  checked={config.emailEnabled}
                  onCheckedChange={(v) => updateConfig('emailEnabled', v)}
                />
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-1.5">
                <label className="text-xs text-muted-foreground">Email Address</label>
                <Input
                  value={config.emailAddress}
                  onChange={(e) => updateConfig('emailAddress', e.target.value)}
                  placeholder="admin@bankbjb.co.id"
                  className="bg-background/50 border-border/40"
                  disabled={!config.emailEnabled}
                />
              </div>
              <p className="text-[11px] text-muted-foreground">
                Email notifications via SMTP relay
              </p>
            </CardContent>
          </Card>
        </motion.div>
      </div>

      {/* Notification Preferences */}
      <Card className="border-border/40 bg-card/50">
        <CardHeader className="pb-3">
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/20 border border-amber-500/30">
              <Bell className="h-4 w-4 text-amber-400" />
            </div>
            <CardTitle className="text-sm font-medium">Notification Events</CardTitle>
          </div>
        </CardHeader>
        <CardContent className="space-y-1">
          {[
            { key: 'alarmNotification', label: 'Alarm Triggered', desc: 'Get notified when an alarm is triggered' },
            { key: 'deviceOffline', label: 'Device Offline', desc: 'Alert when a device goes offline' },
            { key: 'vaultAccess', label: 'Vault Access', desc: 'Notification on vault open/close events' },
            { key: 'maintenanceReminder', label: 'Maintenance Reminder', desc: 'Upcoming maintenance schedule alerts' },
            { key: 'systemAlert', label: 'System Alerts', desc: 'Critical system health notifications' },
          ].map((item) => (
            <div key={item.key} className="flex items-center justify-between py-3">
              <div>
                <p className="text-sm font-medium">{item.label}</p>
                <p className="text-xs text-muted-foreground">{item.desc}</p>
              </div>
              <Switch
                checked={config[item.key as keyof typeof config] as boolean}
                onCheckedChange={(v) => updateConfig(item.key, v)}
              />
            </div>
          ))}
        </CardContent>
      </Card>

      {/* Report Schedule */}
      <Card className="border-border/40 bg-card/50">
        <CardHeader className="pb-3">
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-500/20 border border-purple-500/30">
              <Calendar className="h-4 w-4 text-purple-400" />
            </div>
            <CardTitle className="text-sm font-medium">Scheduled Reports</CardTitle>
          </div>
        </CardHeader>
        <CardContent className="space-y-1">
          {[
            { key: 'dailyReport', label: 'Daily Report', desc: 'Sent every day at 08:00 WIB' },
            { key: 'weeklyReport', label: 'Weekly Report', desc: 'Sent every Monday at 08:00 WIB' },
            { key: 'monthlyReport', label: 'Monthly Report', desc: 'Sent on the 1st of each month' },
          ].map((item) => (
            <div key={item.key} className="flex items-center justify-between py-3">
              <div>
                <p className="text-sm font-medium">{item.label}</p>
                <p className="text-xs text-muted-foreground">{item.desc}</p>
              </div>
              <Switch
                checked={config[item.key as keyof typeof config] as boolean}
                onCheckedChange={(v) => updateConfig(item.key, v)}
              />
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}
