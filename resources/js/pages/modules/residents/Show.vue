<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const { toast } = useToast();

const props = defineProps<{
    item: Record<string, any>;
    fields: Array<{ label: string; value: string }>;
    actionFields: Array<{ label: string; value: string }>;
    aset?: Array<{
        id: number;
        nomor_rumah: string;
        jenis_rumah: string;
        rt: string;
        rw: string;
        desa: string;
    }>;
}>();

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Warga', href: '/data-warga/residents' },
    { title: 'Detail Warga', href: `/data-warga/residents/${props.item.id}` },
];

const handleEdit = () => {
    router.visit(`/data-warga/residents/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/data-warga/residents/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/data-warga/residents');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Warga"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/data-warga/residents'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #custom>
            <div v-if="props.aset && props.aset.length > 0" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Aset (Rumah yang Dimiliki)</h3>
                <div class="border rounded-lg overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nomor Rumah</TableHead>
                                <TableHead>Jenis Rumah</TableHead>
                                <TableHead>RT</TableHead>
                                <TableHead>RW</TableHead>
                                <TableHead>Desa</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="house in props.aset" :key="house.id">
                                <TableCell>{{ house.nomor_rumah }}</TableCell>
                                <TableCell>{{ house.jenis_rumah.replace(/_/g, ' ') }}</TableCell>
                                <TableCell>{{ house.rt }}</TableCell>
                                <TableCell>{{ house.rw }}</TableCell>
                                <TableCell>{{ house.desa }}</TableCell>
                                <TableCell>
                                    <button
                                        @click="router.visit(`/data-warga/houses/${house.id}`)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        Detail
                                    </button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
            <div v-else-if="props.aset && props.aset.length === 0" class="mt-6">
                <p class="text-muted-foreground">Tidak ada aset (rumah yang dimiliki)</p>
            </div>
        </template>
    </PageShow>
</template>

