import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Building2, List, Users, FileText, Settings as SettingsIcon, BookOpen } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage().props;
    const user = auth?.user || {};
    console.log('User role:', user.rol); // Debug
    const isAdmin = user.rol === 'admin';

    // Navegación base para todos
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Empresas',
            href: '/lista-empresas',
            icon: Building2,
        },
        {
            title: 'Programas',
            href: '/programas',
            icon: BookOpen,
        },
    ];

    // Si es admin, agregar vistas avanzadas
    if (isAdmin) {
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
            },
            {
                title: 'Configuración',
                href: '/settings/profile',
                icon: SettingsIcon,
            },
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