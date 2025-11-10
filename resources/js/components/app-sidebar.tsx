import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Building2,
    List,
    Users,
    FileText,
    Settings as SettingsIcon,
    BookOpen,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage().props as any;
    const user = (auth?.user || {}) as { rol?: string };

    const role = String(user.rol || '').toLowerCase();
    const isAdmin = role === 'admin';
    const isAnalyst = role === 'analista';
    const isSuperAdmin = role === 'super-admin';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Configuración',
            href: '/settings/profile',
            icon: SettingsIcon,
        },
    ];

    if (isAnalyst || isAdmin || isSuperAdmin) {
        mainNavItems.push(
            {
                title: 'Empresas',
                href: '/lista-empresas',
                icon: Building2,
            },
            {
                title: 'Programas',
                href: '/programas',
                icon: BookOpen,
            }
        );
    }

    if (isAdmin || isSuperAdmin) {
        mainNavItems.push(
            {
                title: 'Listado Maestro',
                href: '/listado-maestro',
                icon: List,
            },
            {
                title: 'Usuarios',
                href: '/lista-usuarios',
                icon: Users,
            },
            {
                title: 'Documentos de Empresas',
                href: '/documentos-empresas',
                icon: FileText,
            }
        );
    }

    const footerNavItems: NavItem[] = [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
