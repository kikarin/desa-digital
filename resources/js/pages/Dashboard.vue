<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Bell,
    Building2,
    Calendar,
    FileText,
    Home,
    Users,
    AlertCircle,
    FileCheck,
    ClipboardList,
    Package,
    Newspaper,
    Trash2,
    Search,
    Settings,
    Plus,
    UserPlus,
    FilePlus,
    MessageSquare,
    Phone,
    Gift,
} from 'lucide-vue-next';

interface Props {
    statistics?: any;
    recentActivities?: any[];
}

const props = withDefaults(defineProps<Props>(), {
    statistics: () => ({}),
    recentActivities: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formatChange = (change: number) => {
    const sign = change >= 0 ? '+' : '';
    return `${sign}${change.toFixed(1)}%`;
};

const stats = computed(() => {
    const statsData = [];
    const statsConfig = props.statistics || {};

    // Data Warga
    if (statsConfig.data_warga) {
        statsData.push(
            {
                title: 'Total Warga',
                value: formatNumber(statsConfig.data_warga.residents?.total || 0),
                change: formatChange(statsConfig.data_warga.residents?.change || 0),
                trend: statsConfig.data_warga.residents?.trend || 'up',
                icon: Users,
                color: 'text-blue-500',
                bgColor: 'bg-blue-100 dark:bg-blue-900/20',
                href: '/data-warga/residents',
            },
            {
                title: 'Total Keluarga',
                value: formatNumber(statsConfig.data_warga.families?.total || 0),
                change: formatChange(statsConfig.data_warga.families?.change || 0),
                trend: statsConfig.data_warga.families?.trend || 'up',
                icon: UserPlus,
                color: 'text-green-500',
                bgColor: 'bg-green-100 dark:bg-green-900/20',
                href: '/data-warga/families',
            },
            {
                title: 'Total Rumah',
                value: formatNumber(statsConfig.data_warga.houses?.total || 0),
                change: formatChange(statsConfig.data_warga.houses?.change || 0),
                trend: statsConfig.data_warga.houses?.trend || 'up',
                icon: Home,
                color: 'text-orange-500',
                bgColor: 'bg-orange-100 dark:bg-orange-900/20',
                href: '/data-warga/houses',
            }
        );
    }

    // Layanan Surat
    if (statsConfig.layanan_surat) {
        statsData.push({
            title: 'Pengajuan Surat',
            value: formatNumber(statsConfig.layanan_surat.total || 0),
            change: formatChange(statsConfig.layanan_surat.change || 0),
            trend: statsConfig.layanan_surat.trend || 'up',
            icon: FileText,
            color: 'text-purple-500',
            bgColor: 'bg-purple-100 dark:bg-purple-900/20',
            href: '/layanan-surat/pengajuan-surat',
        });
    }

    // Pengajuan Proposal
    if (statsConfig.pengajuan_proposal) {
        statsData.push({
            title: 'Pengajuan Proposal',
            value: formatNumber(statsConfig.pengajuan_proposal.total || 0),
            change: formatChange(statsConfig.pengajuan_proposal.change || 0),
            trend: statsConfig.pengajuan_proposal.trend || 'up',
            icon: FileCheck,
            color: 'text-indigo-500',
            bgColor: 'bg-indigo-100 dark:bg-indigo-900/20',
            href: '/pengajuan-proposal',
        });
    }

    // Aduan Masyarakat
    if (statsConfig.aduan_masyarakat) {
        statsData.push({
            title: 'Aduan Masyarakat',
            value: formatNumber(statsConfig.aduan_masyarakat.total || 0),
            change: formatChange(statsConfig.aduan_masyarakat.change || 0),
            trend: statsConfig.aduan_masyarakat.trend || 'up',
            icon: MessageSquare,
            color: 'text-red-500',
            bgColor: 'bg-red-100 dark:bg-red-900/20',
            href: '/aduan-masyarakat',
        });
    }

    // Layanan Darurat
    if (statsConfig.layanan_darurat) {
        statsData.push({
            title: 'Layanan Darurat',
            value: formatNumber(statsConfig.layanan_darurat.total || 0),
            change: formatChange(statsConfig.layanan_darurat.change || 0),
            trend: statsConfig.layanan_darurat.trend || 'up',
            icon: Phone,
            color: 'text-yellow-500',
            bgColor: 'bg-yellow-100 dark:bg-yellow-900/20',
            href: '/layanan-darurat',
        });
    }

    // Program Bantuan
    if (statsConfig.program_bantuan) {
        statsData.push({
            title: 'Program Bantuan',
            value: formatNumber(statsConfig.program_bantuan.total_program || 0),
            change: formatChange(statsConfig.program_bantuan.change || 0),
            trend: statsConfig.program_bantuan.trend || 'up',
            icon: Gift,
            color: 'text-pink-500',
            bgColor: 'bg-pink-100 dark:bg-pink-900/20',
            href: '/program-bantuan/program-bantuan',
        });
    }

    // Berita & Pengumuman
    if (statsConfig.berita_pengumuman) {
        statsData.push({
            title: 'Berita & Pengumuman',
            value: formatNumber(statsConfig.berita_pengumuman.total || 0),
            change: formatChange(statsConfig.berita_pengumuman.change || 0),
            trend: statsConfig.berita_pengumuman.trend || 'up',
            icon: Newspaper,
            color: 'text-cyan-500',
            bgColor: 'bg-cyan-100 dark:bg-cyan-900/20',
            href: '/berita-pengumuman',
        });
    }

    // Bank Sampah
    if (statsConfig.bank_sampah) {
        statsData.push({
            title: 'Bank Sampah',
            value: formatNumber(statsConfig.bank_sampah.total || 0),
            change: formatChange(statsConfig.bank_sampah.change || 0),
            trend: statsConfig.bank_sampah.trend || 'up',
            icon: Trash2,
            color: 'text-emerald-500',
            bgColor: 'bg-emerald-100 dark:bg-emerald-900/20',
            href: '/bank-sampah',
        });
    }

    // Users
    if (statsConfig.users) {
        statsData.push({
            title: 'Total Users',
            value: formatNumber(statsConfig.users.total || 0),
            change: formatChange(statsConfig.users.change || 0),
            trend: statsConfig.users.trend || 'up',
            icon: Users,
            color: 'text-slate-500',
            bgColor: 'bg-slate-100 dark:bg-slate-900/20',
            href: '/users',
        });
    }

    return statsData;
});

const quickActions = computed(() => {
    return [
        {
            title: 'Tambah Warga',
            icon: UserPlus,
            href: '/data-warga/residents/create',
        },
        {
            title: 'Pengajuan Surat',
            icon: FilePlus,
            href: '/layanan-surat/pengajuan-surat/create',
        },
        {
            title: 'Aduan Baru',
            icon: MessageSquare,
            href: '/aduan-saya/create',
        },
        {
            title: 'Program Bantuan',
            icon: Gift,
            href: '/program-bantuan/program-bantuan/create',
        },
    ];
});

const handleCardClick = (href: string) => {
    if (href) {
        router.visit(href);
    }
};

const getInitials = (name: string) => {
    if (!name) return 'S';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                        <input
                            type="text"
                            placeholder="Search..."
                            class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring h-10 w-[300px] rounded-md border pr-4 pl-9 text-sm focus-visible:ring-2 focus-visible:outline-none"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon">
                        <Bell class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="icon">
                        <Settings class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Card
                    v-for="stat in stats"
                    :key="stat.title"
                    class="overflow-hidden cursor-pointer transition-all hover:shadow-md"
                    @click="handleCardClick(stat.href)"
                >
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ stat.title }}
                        </CardTitle>
                        <div :class="[stat.bgColor, 'rounded-full p-2']">
                            <component :is="stat.icon" :class="stat.color" class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stat.value }}</div>
                        <div class="flex items-center space-x-2 text-xs mt-1">
                            <component
                                :is="stat.trend === 'up' ? ArrowUpRight : ArrowDownRight"
                                :class="stat.trend === 'up' ? 'text-green-500' : 'text-red-500'"
                                class="h-4 w-4"
                            />
                            <span :class="stat.trend === 'up' ? 'text-green-500' : 'text-red-500'">
                                {{ stat.change }}
                            </span>
                            <span class="text-muted-foreground">dari bulan lalu</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Recent Activities -->
                <Card>
                    <CardHeader>
                        <CardTitle>Aktivitas Terbaru</CardTitle>
                        <CardDescription>Update terbaru dari sistem</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-if="recentActivities && recentActivities.length > 0"
                                v-for="activity in recentActivities"
                                :key="activity.id"
                                class="flex items-start space-x-4"
                            >
                                <Avatar>
                                    <AvatarImage :src="activity.user_file" :alt="activity.user_name" />
                                    <AvatarFallback>{{ getInitials(activity.user_name || 'System') }}</AvatarFallback>
                                </Avatar>
                                <div class="flex-1 space-y-1">
                                    <p class="text-sm font-medium">{{ activity.description }}</p>
                                    <p class="text-muted-foreground text-sm">
                                        {{ activity.event }} - {{ activity.subject_type }}
                                    </p>
                                    <p class="text-muted-foreground text-xs">{{ activity.created_at }}</p>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <p>Tidak ada aktivitas terbaru</p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button
                            variant="outline"
                            class="w-full"
                            @click="router.visit('/menu-permissions/logs')"
                        >
                            Lihat Semua Aktivitas
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Status Overview -->
                <Card>
                    <CardHeader>
                        <CardTitle>Ringkasan Status</CardTitle>
                        <CardDescription>Status pengajuan dan layanan</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-if="statistics.layanan_surat" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Pengajuan Surat</span>
                                    <Badge variant="secondary">{{ statistics.layanan_surat.total || 0 }}</Badge>
                                </div>
                                <div class="flex gap-2 text-xs">
                                    <span class="text-muted-foreground">Menunggu: {{ statistics.layanan_surat.menunggu || 0 }}</span>
                                    <span class="text-green-500">Disetujui: {{ statistics.layanan_surat.disetujui || 0 }}</span>
                                    <span class="text-red-500">Ditolak: {{ statistics.layanan_surat.ditolak || 0 }}</span>
                                </div>
                            </div>
                            <div v-if="statistics.pengajuan_proposal" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Pengajuan Proposal</span>
                                    <Badge variant="secondary">{{ statistics.pengajuan_proposal.total || 0 }}</Badge>
                                </div>
                                <div class="flex gap-2 text-xs">
                                    <span class="text-muted-foreground">Menunggu: {{ statistics.pengajuan_proposal.menunggu || 0 }}</span>
                                    <span class="text-green-500">Disetujui: {{ statistics.pengajuan_proposal.disetujui || 0 }}</span>
                                    <span class="text-red-500">Ditolak: {{ statistics.pengajuan_proposal.ditolak || 0 }}</span>
                                </div>
                            </div>
                            <div v-if="statistics.aduan_masyarakat" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Aduan Masyarakat</span>
                                    <Badge variant="secondary">{{ statistics.aduan_masyarakat.total || 0 }}</Badge>
                                </div>
                                <div class="flex gap-2 text-xs">
                                    <span class="text-muted-foreground">Menunggu: {{ statistics.aduan_masyarakat.menunggu || 0 }}</span>
                                    <span class="text-yellow-500">Diproses: {{ statistics.aduan_masyarakat.diproses || 0 }}</span>
                                    <span class="text-green-500">Selesai: {{ statistics.aduan_masyarakat.selesai || 0 }}</span>
                                </div>
                            </div>
                            <div v-if="statistics.program_bantuan" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Program Bantuan</span>
                                    <Badge variant="secondary">{{ statistics.program_bantuan.total_program || 0 }}</Badge>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Total Penerima: {{ formatNumber(statistics.program_bantuan.total_penerima || 0) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Aksi Cepat</CardTitle>
                    <CardDescription>Tugas umum dan pintasan</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <Button
                            v-for="action in quickActions"
                            :key="action.title"
                            variant="outline"
                            class="gap-2"
                            @click="router.visit(action.href)"
                        >
                            <component :is="action.icon" class="h-4 w-4" />
                            {{ action.title }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
