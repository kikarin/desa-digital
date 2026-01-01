<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import WelcomeMap from '@/components/WelcomeMap.vue';
import HouseStats from '@/components/HouseStats.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { X } from 'lucide-vue-next';
import axios from 'axios';

const page = usePage();

// Filter state - menggunakan null untuk "tidak ada filter"
const selectedRwId = ref<string | null>(null);
const selectedRtId = ref<string | null>(null);

// Options
const rwOptions = ref<{ value: string; label: string }[]>([]);
const rtOptions = ref<{ value: string; label: string; rw_id: number }[]>([]);

// Filtered RT options berdasarkan RW yang dipilih
const filteredRtOptions = computed(() => {
    if (!selectedRwId.value) {
        return [];
    }
    return rtOptions.value.filter(rt => rt.rw_id === Number(selectedRwId.value));
});

// Load RW dan RT options
const loadFilterOptions = async () => {
    try {
        // Load RW
        const rwResponse = await axios.get('/api/rws', { params: { per_page: -1 } });
        if (rwResponse.data?.data) {
            rwOptions.value = rwResponse.data.data.map((rw: any) => ({
                value: String(rw.id),
                label: `RW${rw.nomor_rw} - ${rw.desa}`,
            }));
        }

        // Load RT
        const rtResponse = await axios.get('/api/rts', { params: { per_page: -1 } });
        if (rtResponse.data?.data) {
            rtOptions.value = rtResponse.data.data.map((rt: any) => ({
                value: String(rt.id),
                label: `RT${rt.nomor_rt} - RW${rt.nomor_rw || rt.rw?.nomor_rw || ''}`,
                rw_id: rt.rw_id,
            }));
        }
    } catch (error) {
        console.error('Error loading filter options:', error);
    }
};

// Handler untuk RW change
const handleRwChange = (val: any) => {
    selectedRwId.value = val && val !== 'all' ? String(val) : null;
};

// Handler untuk RT change
const handleRtChange = (val: any) => {
    selectedRtId.value = val && val !== 'all' ? String(val) : null;
};

// Watch RW change - reset RT
watch(selectedRwId, (newRwId) => {
    if (!newRwId || newRwId === 'all') {
        selectedRtId.value = null;
    } else {
        // Reset RT jika RW berubah
        const currentRt = rtOptions.value.find(rt => String(rt.value) === selectedRtId.value);
        if (currentRt && currentRt.rw_id !== Number(newRwId)) {
            selectedRtId.value = null;
        }
    }
});

// Filter object untuk pass ke child components
const filterParams = computed(() => {
    const params: { rw_id?: number; rt_id?: number } = {};
    if (selectedRwId.value && selectedRwId.value !== 'all') {
        params.rw_id = Number(selectedRwId.value);
    }
    if (selectedRtId.value && selectedRtId.value !== 'all') {
        params.rt_id = Number(selectedRtId.value);
    }
    return params;
});

// Clear filter
const clearFilter = () => {
    selectedRwId.value = null;
    selectedRtId.value = null;
};

// Load options on mount
loadFilterOptions();
</script>
<template>
    <Head title="SIGAP - Sistem Informasi Galuga Pintar">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Header -->
        <header class="sticky top-0 z-[100] w-full border-b bg-card shadow-sm">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img
                            src="/storage/Lambang_Kabupaten_Bogor.png"
                            alt="Logo Kabupaten Bogor"
                            class="h-16 w-auto object-contain"
                        />
                        <div>
                            <h1 class="text-2xl font-bold text-primary">SIGAP</h1>
                            <p class="text-sm text-muted-foreground">Sistem Informasi Galuga Pintar</p>
                        </div>
                    </div>
                    <nav class="flex items-center gap-4">
                <Link
                    v-if="(page.props as any).auth?.user"
                    :href="route('dashboard')"
                            class="rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                                class="rounded-md border border-transparent px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                                Masuk
                    </Link>
                    <Link
                        :href="route('register')"
                                class="rounded-md border border-border bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                                Daftar
                    </Link>
                </template>
            </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="bg-gradient-to-b from-primary/10 to-background py-12">
            <div class="container mx-auto px-4 text-center">
                <h2 class="mb-4 text-4xl font-bold text-foreground">
                    Selamat Datang di SIGAP
                </h2>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    Sistem Informasi Galuga Pintar - Platform digital untuk pelayanan administrasi dan informasi Desa Galuga, Kecamatan Cibungbulang, Kabupaten Bogor
                </p>
            </div>
        </section>

        <!-- Peta Section -->
        <section class="py-8">
            <div class="container mx-auto px-44">
                <div class="mb-6 text-center">
                    <h3 class="mb-2 text-2xl font-semibold text-foreground">Peta Wilayah Desa Galuga</h3>
                    <p class="text-muted-foreground">Lokasi Desa Galuga, Kecamatan Cibungbulang, Kabupaten Bogor, Jawa Barat</p>
                </div>
                
                <!-- Filter Section -->
                <div class="mb-4 flex flex-wrap items-end gap-4 rounded-lg border bg-card p-4">
                    <div class="flex-1 min-w-[200px]">
                        <Label for="filter-rw" class="mb-2 block text-sm font-medium">Filter RW</Label>
                        <Select
                            :model-value="selectedRwId || 'all'"
                            @update:model-value="handleRwChange"
                        >
                            <SelectTrigger id="filter-rw" class="w-full">
                                <SelectValue placeholder="Pilih RW (Semua)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua RW</SelectItem>
                                <SelectItem
                                    v-for="rw in rwOptions"
                                    :key="rw.value"
                                    :value="rw.value"
                                >
                                    {{ rw.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="flex-1 min-w-[200px]">
                        <Label for="filter-rt" class="mb-2 block text-sm font-medium">Filter RT</Label>
                        <Select
                            :model-value="selectedRtId || 'all'"
                            @update:model-value="handleRtChange"
                            :disabled="!selectedRwId || selectedRwId === 'all'"
                        >
                            <SelectTrigger id="filter-rt" class="w-full" :disabled="!selectedRwId || selectedRwId === 'all'">
                                <SelectValue :placeholder="selectedRwId && selectedRwId !== 'all' ? 'Pilih RT (Semua)' : 'Pilih RW terlebih dahulu'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua RT</SelectItem>
                                <SelectItem
                                    v-for="rt in filteredRtOptions"
                                    :key="rt.value"
                                    :value="rt.value"
                                >
                                    {{ rt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <Button
                        v-if="selectedRwId || selectedRtId"
                        variant="outline"
                        @click="clearFilter"
                        class="gap-2"
                    >
                        <X class="h-4 w-4" />
                        Reset Filter
                    </Button>
                </div>
                
                <WelcomeMap height="600px" :filter-params="filterParams" />
            </div>
        </section>

        <!-- Statistik Section -->
        <section class="py-8 bg-muted/30">
            <div class="container mx-auto px-44">
                <HouseStats :filter-params="filterParams" />
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t bg-card py-8">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div>
                        <h4 class="mb-4 text-lg font-semibold text-foreground">SIGAP</h4>
                        <p class="text-sm text-muted-foreground">
                            Sistem Informasi Galuga Pintar - Platform digital untuk pelayanan administrasi Desa Galuga.
                        </p>
                    </div>
                    <div>
                        <h4 class="mb-4 text-lg font-semibold text-foreground">Kontak</h4>
                        <p class="text-sm text-muted-foreground">
                            Desa Galuga<br />
                            Kecamatan Cibungbulang<br />
                            Kabupaten Bogor, Jawa Barat
                        </p>
                    </div>
                    <div>
                        <h4 class="mb-4 text-lg font-semibold text-foreground">Layanan</h4>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li>Pengajuan Surat</li>
                            <li>Program Bantuan</li>
                            <li>Data Warga</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8 border-t pt-8 text-center text-sm text-muted-foreground">
                    <p>&copy; {{ new Date().getFullYear() }} SIGAP - Sistem Informasi Galuga Pintar. All rights reserved.</p>
                </div>
        </div>
        </footer>
    </div>
</template>
