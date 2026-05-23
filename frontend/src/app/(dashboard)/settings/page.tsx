'use client';

import { Settings, User, Shield, Palette, Database, Globe } from 'lucide-react';
import { useTheme } from 'next-themes';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

function AppearanceSettings() {
  const { theme, setTheme } = useTheme();
  const [compactMode, setCompactMode] = useState(false);
  const [animations, setAnimations] = useState(true);
  const [realtimeIndicators, setRealtimeIndicators] = useState(true);

  return (
    <Card className="border-border/40 bg-card/50">
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium flex items-center gap-2">
          <Palette className="h-4 w-4 text-blue-400" />
          Appearance
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium">Dark Mode</p>
              <p className="text-xs text-muted-foreground">Use dark theme (recommended for monitoring)</p>
            </div>
            <Switch
              checked={theme === 'dark'}
              onCheckedChange={(checked) => setTheme(checked ? 'dark' : 'light')}
            />
          </div>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium">Compact Mode</p>
              <p className="text-xs text-muted-foreground">Reduce spacing for dense information display</p>
            </div>
            <Switch checked={compactMode} onCheckedChange={setCompactMode} />
          </div>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium">Animations</p>
              <p className="text-xs text-muted-foreground">Enable smooth transitions and animations</p>
            </div>
            <Switch checked={animations} onCheckedChange={setAnimations} />
          </div>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium">Realtime Indicators</p>
              <p className="text-xs text-muted-foreground">Show pulse animations for live data</p>
            </div>
            <Switch checked={realtimeIndicators} onCheckedChange={setRealtimeIndicators} />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

export default function SettingsPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Settings</h1>
        <p className="text-sm text-muted-foreground mt-1">
          System configuration and preferences
        </p>
      </div>

      <Tabs defaultValue="general" className="space-y-6">
        <TabsList className="bg-muted/30 border border-border/40">
          <TabsTrigger value="general" className="gap-2 text-xs">
            <Settings className="h-3.5 w-3.5" />
            General
          </TabsTrigger>
          <TabsTrigger value="profile" className="gap-2 text-xs">
            <User className="h-3.5 w-3.5" />
            Profile
          </TabsTrigger>
          <TabsTrigger value="security" className="gap-2 text-xs">
            <Shield className="h-3.5 w-3.5" />
            Security
          </TabsTrigger>
          <TabsTrigger value="appearance" className="gap-2 text-xs">
            <Palette className="h-3.5 w-3.5" />
            Appearance
          </TabsTrigger>
        </TabsList>

        {/* General */}
        <TabsContent value="general" className="space-y-4">
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium flex items-center gap-2">
                <Globe className="h-4 w-4 text-blue-400" />
                System Configuration
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">System Name</label>
                  <Input defaultValue="PANSIN ACCESS" className="bg-background/50 border-border/40" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Organization</label>
                  <Input defaultValue="Bank BJB" className="bg-background/50 border-border/40" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">API Base URL</label>
                  <Input defaultValue="https://api.pansin.bankbjb.co.id" className="bg-background/50 border-border/40 font-mono text-xs" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">WebSocket URL</label>
                  <Input defaultValue="wss://ws.pansin.bankbjb.co.id" className="bg-background/50 border-border/40 font-mono text-xs" />
                </div>
              </div>
              <Separator className="opacity-40" />
              <div className="space-y-3">
                {[
                  { label: 'Auto-lock vault after timeout', desc: 'Automatically lock vault if no activity for 30 minutes', checked: true },
                  { label: 'Require dual authentication', desc: 'Fingerprint + PIN for vault access', checked: true },
                  { label: 'Enable audit logging', desc: 'Log all system activities for compliance', checked: true },
                  { label: 'Auto-reconnect devices', desc: 'Automatically reconnect offline devices', checked: true },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium">{item.label}</p>
                      <p className="text-xs text-muted-foreground">{item.desc}</p>
                    </div>
                    <Switch defaultChecked={item.checked} />
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Profile */}
        <TabsContent value="profile" className="space-y-4">
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium flex items-center gap-2">
                <User className="h-4 w-4 text-blue-400" />
                Profile Information
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Full Name</label>
                  <Input defaultValue="Administrator" className="bg-background/50 border-border/40" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Username</label>
                  <Input defaultValue="admin" className="bg-background/50 border-border/40" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Email</label>
                  <Input defaultValue="admin@bankbjb.co.id" className="bg-background/50 border-border/40" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Phone</label>
                  <Input defaultValue="+6281234567890" className="bg-background/50 border-border/40" />
                </div>
              </div>
              <Button className="bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
                Update Profile
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Security */}
        <TabsContent value="security" className="space-y-4">
          <Card className="border-border/40 bg-card/50">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium flex items-center gap-2">
                <Shield className="h-4 w-4 text-blue-400" />
                Security Settings
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Current Password</label>
                  <Input type="password" placeholder="Enter current password" className="bg-background/50 border-border/40" />
                </div>
                <div />
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">New Password</label>
                  <Input type="password" placeholder="Enter new password" className="bg-background/50 border-border/40" />
                </div>
                <div className="space-y-1.5">
                  <label className="text-xs text-muted-foreground">Confirm Password</label>
                  <Input type="password" placeholder="Confirm new password" className="bg-background/50 border-border/40" />
                </div>
              </div>
              <Separator className="opacity-40" />
              <div className="space-y-3">
                {[
                  { label: 'Two-Factor Authentication', desc: 'Enable 2FA for additional security', checked: true },
                  { label: 'Session Timeout', desc: 'Auto logout after 30 minutes of inactivity', checked: true },
                  { label: 'Login Notifications', desc: 'Get notified on new login attempts', checked: false },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium">{item.label}</p>
                      <p className="text-xs text-muted-foreground">{item.desc}</p>
                    </div>
                    <Switch defaultChecked={item.checked} />
                  </div>
                ))}
              </div>
              <Button className="bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
                Update Security
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Appearance */}
        <TabsContent value="appearance" className="space-y-4">
          <AppearanceSettings />
        </TabsContent>
      </Tabs>
    </div>
  );
}
