<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    can?: {
        Add?: boolean;
        Edit?: boolean;
        Delete?: boolean;
        Detail?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Kartu Keluarga', href: '/data-warga/families' },
];

const columns = [
    { key: 'no_kk', label: 'No. KK', searchable: true, orderable: true, visible: true },
    {
        key: 'status',
        label: 'Status',
        searchable: true,
        orderable: true,
        visible: true,
        format: (row: any) => {
            if (row.status === 'AKTIF') {
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">AKTIF</span>';
            } else if (row.status === 'NON_AKTIF') {
                return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">NON_AKTIF</span>';
            }
            return row.status || '-';
        },
    },
    { key: 'nomor_rumah', label: 'Nomor Rumah', searchable: true, orderable: true, visible: true },
    { key: 'rt', label: 'RT', searchable: true, orderable: true, visible: true },
    { key: 'rw', label: 'RW', searchable: true, orderable: true, visible: true },
    { key: 'desa', label: 'Desa', searchable: true, orderable: true, visible: true },
    { key: 'kecamatan', label: 'Kecamatan', searchable: true, orderable: true, visible: true },
    { key: 'kabupaten', label: 'Kabupaten', searchable: true, orderable: true, visible: true },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const showSetKepalaKeluargaDialog = ref(false);
const selectedFamily = ref<any>(null);
const residentsList = ref<Array<{ value: number; label: string }>>([]);
const selectedResidentId = ref<number | null>(null);
const loadingResidents = ref(false);
const filterOptions = ref<{ rw?: Array<{ value: number; label: string }>; rt?: Array<{ value: number; label: string }> }>({});

onMounted(async () => {
    try {
        const response = await axios.get('/api/families');
        if (response.data.filterOptions) {
            filterOptions.value = response.data.filterOptions;
        }
    } catch (error) {
        console.error('Gagal mengambil filter options:', error);
    }
});

const handleSetKepalaKeluarga = async (row: any) => {
    selectedFamily.value = row;
    selectedResidentId.value = row.kepala_keluarga_id || null;
    loadingResidents.value = true;

    try {
        const response = await axios.get(`/api/families/${row.id}/residents`);
        residentsList.value = response.data.data.map((resident: any) => ({
            value: resident.id,
            label: `${resident.nik} - ${resident.nama}`,
        }));
    } catch (error) {
        console.error('Gagal mengambil data residents:', error);
        toast({ title: 'Gagal mengambil data residents', variant: 'destructive' });
    } finally {
        loadingResidents.value = false;
    }

    showSetKepalaKeluargaDialog.value = true;
};

const saveKepalaKeluarga = async () => {
    if (!selectedFamily.value || !selectedResidentId.value) return;

    try {
        await axios.put(`/api/families/${selectedFamily.value.id}/set-kepala-keluarga`, {
            kepala_keluarga_id: selectedResidentId.value,
        });

        toast({ title: 'Kepala keluarga berhasil diupdate', variant: 'success' });
        showSetKepalaKeluargaDialog.value = false;
        pageIndex.value.fetchData();
    } catch (error: any) {
        console.error('Gagal update kepala keluarga:', error);
        const message = error.response?.data?.message || 'Gagal update kepala keluarga';
        toast({ title: message, variant: 'destructive' });
    }
};

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-warga/families/${row.id}`),
        permission: props.can?.Detail,
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-warga/families/${row.id}/edit`),
        permission: props.can?.Edit,
    },
    {
        label: 'Set Kepala Keluarga',
        onClick: () => handleSetKepalaKeluarga(row),
        permission: props.can?.Edit,
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: props.can?.Delete,
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/data-warga/families/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value.fetchData();

        toast({
            title: response.data?.message || 'Data berhasil dihapus',
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);
        const message = error.response?.data?.message || 'Gagal menghapus data';
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};

const deleteFamilies = async (row: any) => {
    await router.delete(`/data-warga/families/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageIndex
        title="Kartu Keluarga"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/data-warga/families/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/families"
        ref="pageIndex"
        :on-delete-row-confirm="deleteFamilies"
        :can="props.can"
        :show-filter="true"
        :filter-options="filterOptions"
    />

    <Dialog v-model:open="showSetKepalaKeluargaDialog">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Set Kepala Keluarga</DialogTitle>
                <DialogDescription>
                    Pilih warga yang akan dijadikan kepala keluarga untuk KK: {{ selectedFamily?.no_kk }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div v-if="loadingResidents" class="text-center py-4">
                    Memuat data...
                </div>
                <div v-else-if="residentsList.length === 0" class="text-center py-4 text-muted-foreground">
                    Tidak ada warga di rumah ini
                </div>
                <div v-else>
                    <label class="text-sm font-medium">Pilih Warga</label>
                    <Select
                        :model-value="selectedResidentId ? String(selectedResidentId) : null"
                        @update:model-value="(val: string) => (selectedResidentId = val ? Number(val) : null)"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih warga" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="resident in residentsList"
                                :key="resident.value"
                                :value="String(resident.value)"
                            >
                                {{ resident.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="showSetKepalaKeluargaDialog = false">
                    Batal
                </Button>
                <Button @click="saveKepalaKeluarga" :disabled="!selectedResidentId || loadingResidents">
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

