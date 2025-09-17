import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Files, Users, LayoutGrid, Building2, BookOpenCheck, FileSliders } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard', // Actualizado
        icon: LayoutGrid,
    }, {
        title: 'Lista de Usuarios',
        href: '/lista-usuarios', // Actualizado
        icon: Users,
    }, {
        title: 'Lista de Empresas',
        href: '/lista-empresas', // Actualizado
        icon: Building2,
    }, {
        title: 'Documentos de Usuarios',
        href: '/documentos-usuarios', // Actualizado
        icon: Files,
    }, {
        title: 'Programas',
        href: '/programas', // Actualizado
        icon: BookOpenCheck,
    }, {
        title: 'Listado Maestro',
        href: '/listado-maestro', // Actualizado
        icon: FileSliders,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
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