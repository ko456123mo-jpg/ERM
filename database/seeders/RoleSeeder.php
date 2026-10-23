<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // User Management (Granular)
            'users.view' => 'View users list and profiles',
            'users.create' => 'Create new platform user accounts',
            'users.edit' => 'Edit user account details and names',
            'users.change_email' => 'Change user account email address',
            'users.change_password' => 'Change user account password',
            'users.reset_password' => 'Reset user account password',
            'users.enable' => 'Enable and activate user accounts',
            'users.disable' => 'Disable and deactivate user accounts',
            'users.delete' => 'Delete user accounts',
            'users.change_role' => 'Change role of platform users',

            // Role Management
            'roles.view' => 'View roles and permission mapping',
            'roles.create' => 'Create new security roles',
            'roles.edit' => 'Edit existing roles and permissions',
            'roles.delete' => 'Delete security roles',
            'roles.assign' => 'Assign roles to platform users',

            // Application Management
            'applications.view' => 'View mobile applications list',
            'applications.upload' => 'Upload APK mobile applications',
            'applications.edit' => 'Edit application details',
            'applications.delete' => 'Remove application records',

            // Security Analysis
            'analysis.view' => 'View security scan results',
            'analysis.start' => 'Trigger static/dynamic analysis',
            'analysis.cancel' => 'Cancel running security analysis',
            'reports.view' => 'View security analysis reports',
            'reports.download' => 'Export and download security reports',
            'reports.delete' => 'Delete generated analysis reports',

            // Security Tools
            'tools.mobsf' => 'Execute MobSF static binary analysis',
            'tools.frida' => 'Use Frida dynamic instrumentation',
            'tools.objection' => 'Use Objection runtime inspection',

            // AI Capabilities
            'ai.analysis' => 'Run AI-assisted security analysis',
            'ai.reports' => 'Generate AI security summaries',
            'ai.settings' => 'Configure AI integration settings',

            // System
            'settings.view' => 'View system configuration settings',
            'settings.edit' => 'Update system configuration settings',
            'audit_logs.view' => 'View enterprise security audit logs',
        ];

        $permMap = [];
        foreach ($permissions as $name => $desc) {
            $perm = Permission::firstOrNew(['name' => $name]);
            $perm->description = $desc;
            $perm->save();
            $permMap[$name] = $perm->id;
        }

        // 1. Super Admin Role
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['description' => 'Super Administrator with complete unrestricted access']
        );
        $superAdminRole->permissions()->sync(array_values($permMap));

        // 2. Admin Role
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'System Administrator managing users, roles, and settings']
        );
        $adminPermKeys = [
            'users.view', 'users.create', 'users.edit', 'users.change_email',
            'users.change_password', 'users.reset_password', 'users.enable',
            'users.disable', 'users.delete', 'users.change_role',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign',
            'applications.view', 'applications.upload', 'applications.edit', 'applications.delete',
            'analysis.view', 'analysis.start', 'reports.view', 'reports.download',
            'settings.view', 'settings.edit', 'audit_logs.view'
        ];
        $adminRole->permissions()->sync(array_map(fn($k) => $permMap[$k], $adminPermKeys));

        // 3. Security Analyst Role
        $analystRole = Role::firstOrCreate(
            ['name' => 'Security Analyst'],
            ['description' => 'Security Analyst conducting mobile vulnerability scans']
        );
        $analystPermKeys = [
            'applications.view', 'applications.upload', 'applications.edit',
            'analysis.view', 'analysis.start', 'analysis.cancel',
            'reports.view', 'reports.download',
            'tools.mobsf', 'tools.frida', 'tools.objection',
            'ai.analysis', 'ai.reports'
        ];
        $analystRole->permissions()->sync(array_map(fn($k) => $permMap[$k], $analystPermKeys));

        // 4. Developer Role
        $devRole = Role::firstOrCreate(
            ['name' => 'Developer'],
            ['description' => 'Application developer reviewing scan results and builds']
        );
        $devPermKeys = [
            'applications.view', 'applications.upload',
            'analysis.view', 'analysis.start',
            'reports.view', 'reports.download',
            'tools.mobsf'
        ];
        $devRole->permissions()->sync(array_map(fn($k) => $permMap[$k], $devPermKeys));

        // 5. Standard User Role
        $standardUserRole = Role::firstOrCreate(
            ['name' => 'Standard User'],
            ['description' => 'Standard platform user with access to own applications and reports']
        );
        $standardUserPermKeys = [
            'applications.view',
            'analysis.view',
            'reports.view',
            'reports.download',
        ];
        $standardUserRole->permissions()->sync(array_map(fn($k) => $permMap[$k], $standardUserPermKeys));

        // 6. Viewer Role
        $viewerRole = Role::firstOrCreate(
            ['name' => 'Viewer'],
            ['description' => 'Read-only viewer with view access to authorized applications and reports']
        );
        $viewerPermKeys = [
            'applications.view',
            'analysis.view',
            'reports.view',
        ];
        $viewerRole->permissions()->sync(array_map(fn($k) => $permMap[$k], $viewerPermKeys));
    }
}
