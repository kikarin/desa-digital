<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nama: string;
        kode: string;
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
        updated_at: string;
        updated_by_user: {
            id: number;
            name: string;
        } | null;
        atribut?: Array<{
            id: number;
            nama_atribut: string;
            tipe_data: string;
            is_required: boolean;
            urutan: number;
        }>;
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Jenis Surat', href: '/layanan-surat/jenis-surat' },
    { title: 'Detail Jenis Surat', href: `/layanan-surat/jenis-surat/${props.item.id}` },
];

const fields = [
    { label: 'Nama', value: props.item.nama },
    { label: 'Kode', value: props.item.kode },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/layanan-surat/jenis-surat/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/layanan-surat/jenis-surat/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/layanan-surat/jenis-surat');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};

const handleViewAtribut = () => {
    router.visit(`/layanan-surat/atribut-jenis-surat?filter_jenis_surat_id=${props.item.id}`);
};
</script>

<template>
    <PageShow
        title="Jenis Surat"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/layanan-surat/jenis-surat'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #additional-content>
            <div v-if="item.atribut && item.atribut.length > 0" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Atribut Jenis Surat</h3>
                <div class="space-y-2">
                    <div
                        v-for="atribut in item.atribut"
                        :key="atribut.id"
                        class="p-3 bg-muted rounded-lg flex items-center justify-between"
                    >
                        <div>
                            <span class="font-medium">{{ atribut.nama_atribut }}</span>
                            <span class="text-sm text-muted-foreground ml-2">({{ atribut.tipe_data }})</span>
                            <span v-if="atribut.is_required" class="text-xs text-red-500 ml-2">*Wajib</span>
                        </div>
                        <span class="text-sm text-muted-foreground">Urutan: {{ atribut.urutan }}</span>
                    </div>
                </div>
                <div class="mt-4">
                    <button
                        @click="handleViewAtribut"
                        class="text-primary hover:underline text-sm"
                    >
                        Lihat semua atribut →
                    </button>
                </div>
            </div>
        </template>
    </PageShow>
</template>

