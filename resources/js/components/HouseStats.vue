<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getMarkerColorByJenisRumah, getMarkerColorByModule } from '@/utils/markerColors';
import { Home, Building2, Store, Building, Trash2, AlertCircle, Phone, FileText } from 'lucide-vue-next';

const props = defineProps<{
    filterParams?: { rw_id?: number; rt_id?: number };
}>();

const stats = ref({
    RUMAH_TINGGAL: 0,
    KONTRAKAN: 0,
    WARUNG_TOKO_USAHA: 0,
    FASILITAS_UMUM: 0,
    total: 0,
});

const moduleStats = ref({
    bank_sampah: 0,
    aduan_masyarakat: 0,
    layanan_darurat: 0,
    pengajuan_proposal: 0,
});

const isLoading = ref(true);

const getJenisRumahLabel = (jenis: string): string => {
    const labels: Record<string, string> = {
        'RUMAH_TINGGAL': 'Rumah Tinggal',
        'KONTRAKAN': 'Kontrakan',
        'WARUNG_TOKO_USAHA': 'Warung / Toko / Usaha',
        'FASILITAS_UMUM': 'Fasilitas Umum',
    };
    return labels[jenis] || jenis;
};

const getJenisRumahIcon = (jenis: string) => {
    const icons: Record<string, any> = {
        'RUMAH_TINGGAL': Home,
        'KONTRAKAN': Building2,
        'WARUNG_TOKO_USAHA': Store,
        'FASILITAS_UMUM': Building,
    };
    return icons[jenis] || Home;
};

const loadStats = async () => {
    isLoading.value = true;
    try {
        const params: Record<string, any> = {};
        if (props.filterParams?.rw_id) {
            params.rw_id = props.filterParams.rw_id;
        }
        if (props.filterParams?.rt_id) {
            params.rt_id = props.filterParams.rt_id;
        }
        
        const response = await axios.get('/api/houses-stats', { params });
        if (response.data) {
            stats.value = {
                RUMAH_TINGGAL: response.data.stats?.RUMAH_TINGGAL || 0,
                KONTRAKAN: response.data.stats?.KONTRAKAN || 0,
                WARUNG_TOKO_USAHA: response.data.stats?.WARUNG_TOKO_USAHA || 0,
                FASILITAS_UMUM: response.data.stats?.FASILITAS_UMUM || 0,
                total: response.data.total || 0,
            };
            moduleStats.value = {
                bank_sampah: response.data.modules?.bank_sampah || 0,
                aduan_masyarakat: response.data.modules?.aduan_masyarakat || 0,
                layanan_darurat: response.data.modules?.layanan_darurat || 0,
                pengajuan_proposal: response.data.modules?.pengajuan_proposal || 0,
            };
        }
    } catch (error) {
        console.error('Error loading house stats:', error);
    } finally {
        isLoading.value = false;
    }
};

const getModuleLabel = (module: string): string => {
    const labels: Record<string, string> = {
        'bank_sampah': 'Bank Sampah',
        'aduan_masyarakat': 'Aduan Masyarakat',
        'layanan_darurat': 'Layanan Darurat',
        'pengajuan_proposal': 'Pengajuan Proposal',
    };
    return labels[module] || module;
};

const getModuleIcon = (module: string) => {
    const icons: Record<string, any> = {
        'bank_sampah': Trash2,
        'aduan_masyarakat': AlertCircle,
        'layanan_darurat': Phone,
        'pengajuan_proposal': FileText,
    };
    return icons[module] || FileText;
};

const moduleList = [
    'bank_sampah',
    'aduan_masyarakat',
    'layanan_darurat',
    'pengajuan_proposal',
];

const jenisRumahList = [
    'RUMAH_TINGGAL',
    'KONTRAKAN',
    'WARUNG_TOKO_USAHA',
    'FASILITAS_UMUM',
];

// Watch filter changes
watch(() => props.filterParams, () => {
    loadStats();
}, { deep: true });

onMounted(() => {
    loadStats();
});
</script>

<template>
    <div class="w-full">
        <div class="mb-6 text-center">
            <h3 class="mb-2 text-2xl font-semibold text-foreground">Statistik Jenis Rumah</h3>
            <p class="text-muted-foreground">Jumlah rumah berdasarkan jenis di Desa Galuga</p>
        </div>

        <div v-if="isLoading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card v-for="jenis in jenisRumahList" :key="jenis" class="animate-pulse">
                <CardHeader class="pb-3">
                    <div class="h-4 w-24 bg-muted rounded"></div>
                </CardHeader>
                <CardContent>
                    <div class="h-8 w-16 bg-muted rounded"></div>
                </CardContent>
            </Card>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card
                v-for="jenis in jenisRumahList"
                :key="jenis"
                class="hover:shadow-lg transition-shadow"
            >
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :style="{ backgroundColor: getMarkerColorByJenisRumah(jenis) + '20' }"
                        >
                            <component
                                :is="getJenisRumahIcon(jenis)"
                                class="h-5 w-5"
                                :style="{ color: getMarkerColorByJenisRumah(jenis) }"
                            />
                        </div>
                        <CardTitle class="text-base">{{ getJenisRumahLabel(jenis) }}</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-foreground">{{ stats[jenis] }}</span>
                        <span class="text-sm text-muted-foreground">unit</span>
                    </div>
                    <div class="mt-2">
                        <div class="h-2 w-full bg-muted rounded-full overflow-hidden">
                            <div
                                class="h-full transition-all duration-500"
                                :style="{
                                    width: stats.total > 0 ? `${(stats[jenis] / stats.total) * 100}%` : '0%',
                                    backgroundColor: getMarkerColorByJenisRumah(jenis),
                                }"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.total > 0 ? ((stats[jenis] / stats.total) * 100).toFixed(1) : 0 }}% dari total
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Total Card -->
        <Card class="mt-4 border-primary/20 bg-primary/5">
            <CardContent class="pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Rumah</p>
                        <p class="mt-1 text-3xl font-bold text-foreground">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-full bg-primary/10 p-4">
                        <Home class="h-8 w-8 text-primary" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Module Stats Section -->
        <div class="mt-8">
            <div class="mb-6 text-center">
                <h3 class="mb-2 text-2xl font-semibold text-foreground">Statistik Module Lainnya</h3>
                <p class="text-muted-foreground">Jumlah data dari berbagai module di Desa Galuga</p>
            </div>

            <div v-if="isLoading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="module in moduleList" :key="module" class="animate-pulse">
                    <CardHeader class="pb-3">
                        <div class="h-4 w-24 bg-muted rounded"></div>
                    </CardHeader>
                    <CardContent>
                        <div class="h-8 w-16 bg-muted rounded"></div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="module in moduleList"
                    :key="module"
                    class="hover:shadow-lg transition-shadow"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg"
                                :style="{ backgroundColor: getMarkerColorByModule(module.replace('_', '-')) + '20' }"
                            >
                                <component
                                    :is="getModuleIcon(module)"
                                    class="h-5 w-5"
                                    :style="{ color: getMarkerColorByModule(module.replace('_', '-')) }"
                                />
                            </div>
                            <CardTitle class="text-base">{{ getModuleLabel(module) }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-foreground">{{ moduleStats[module] }}</span>
                            <span class="text-sm text-muted-foreground">data</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

