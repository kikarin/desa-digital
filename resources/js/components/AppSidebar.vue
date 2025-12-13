<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import * as LucideIcons from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import AppLogo from './AppLogo.vue';

const mainNavItems = ref<NavItem[]>([]);
const settingNavItems = ref<NavItem[]>([]);
const isLoading = ref(false);

// Mapping icon names to components
const iconMap: Record<string, any> = {
    LayoutGrid: LucideIcons.LayoutGrid,
    FolderKanban: LucideIcons.FolderKanban,
    FileStack: LucideIcons.FileStack,
    Users: LucideIcons.Users,
    Settings: LucideIcons.Settings,
    FileText: LucideIcons.FileText,
    Folder: LucideIcons.Folder,
    Shield: LucideIcons.Shield,
    User: LucideIcons.User,
    List: LucideIcons.List,
    Plus: LucideIcons.Plus,
    Edit: LucideIcons.Edit,
    Trash: LucideIcons.Trash,
    Search: LucideIcons.Search,
    Filter: LucideIcons.Filter,
    Download: LucideIcons.Download,
    Upload: LucideIcons.Upload,
    Menu: LucideIcons.Menu,
    Home: LucideIcons.Home,
    BarChart: LucideIcons.BarChart,
    PieChart: LucideIcons.PieChart,
    Calendar: LucideIcons.Calendar,
    ShieldCheck: LucideIcons.ShieldCheck,
    BookUser: LucideIcons.BookUser,
    House: LucideIcons.House,
};

const fetchMenus = async () => {
    if (isLoading.value) return;

    try {
        isLoading.value = true;
        const response = await axios.get('/api/users-menu');

        console.log('API Response:', response.data);

        // Handle response format dari UsersMenuController::getMenus()
        // Response format: { success: true, data: menus, message: '...' }
        let menus = response.data?.data || response.data;

        // Jika bukan array, coba ambil dari property lain
        if (!Array.isArray(menus)) {
            if (menus && Array.isArray(menus.menus)) {
                menus = menus.menus;
            } else {
                console.error('Invalid menu data format:', menus);
                mainNavItems.value = [];
                settingNavItems.value = [];
                return;
            }
        }

        // Transform menu data to NavItem format
        const transformMenuToNavItem = (menu: any): NavItem => {
            const navItem: NavItem = {
                title: menu.nama || menu.name || 'Unknown',
                href: menu.url || '#',
                icon: menu.icon ? (iconMap[menu.icon] || LucideIcons[menu.icon as keyof typeof LucideIcons]) : undefined,
            };

            // Handle children - sekarang sudah dalam format array dari backend
            if (menu.children && Array.isArray(menu.children) && menu.children.length > 0) {
                navItem.children = menu.children.map(transformMenuToNavItem);
            }

            return navItem;
        };

        // Split menus into main and settings based on urutan
        const mainMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan <= 10;
        });

        const settingMenus = menus.filter((menu: any) => {
            const urutan = menu.urutan || 0;
            return urutan > 10;
        });

        mainNavItems.value = mainMenus.map(transformMenuToNavItem);
        settingNavItems.value = settingMenus.map(transformMenuToNavItem);

        console.log('Main Menus:', mainNavItems.value);
        console.log('Setting Menus:', settingNavItems.value);
    } catch (error) {
        console.error('Error fetching menus:', error);

        // Set empty arrays on error
        mainNavItems.value = [];
        settingNavItems.value = [];

        // You might want to show a notification to user here
        // toast.error('Failed to load menu items');
    } finally {
        isLoading.value = false;
    }
};

// Refresh menu setiap 5 menit
const refreshInterval = 5 * 60 * 1000; // 5 menit dalam milidetik
let intervalId: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    fetchMenus();
    // Set interval untuk refresh menu
    intervalId = setInterval(fetchMenus, refreshInterval);
});

// Cleanup interval on unmount
onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- Show loading state if needed -->
            <div v-if="isLoading && mainNavItems.length === 0 && settingNavItems.length === 0" class="text-muted-foreground px-4 py-2 text-sm">
                Loading menus...
            </div>

            <!-- Main navigation -->
            <NavMain v-if="mainNavItems.length > 0" :items="mainNavItems" section-title="Menu" section-id="main" />

            <!-- Settings navigation -->
            <NavMain v-if="settingNavItems.length > 0" :items="settingNavItems" section-title="Settings" section-id="setting" />

            <!-- Empty state -->
            <div v-if="!isLoading && mainNavItems.length === 0 && settingNavItems.length === 0" class="text-muted-foreground px-4 py-2 text-sm">
                No menu items available
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
