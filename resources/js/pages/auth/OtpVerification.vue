<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { AlertCircle, LoaderCircle, Lock, Mail, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, watch } from 'vue';
import { useToast } from '@/components/ui/toast/useToast';

const props = defineProps<{
    status?: string;
    email?: string;
}>();

const { toast } = useToast();
const page = usePage();

const form = useForm({
    code: '',
});

const isResending = computed(() => page.props.value?.ziggy?.location === undefined && false);

const submit = () => {
    form.post(route('otp.verify'), {
        onFinish: () => {
            form.reset('code');
        },
    });
};

const resend = () => {
    router.post(
        route('otp.resend'),
        {},
        {
            preserveScroll: true,
            onSuccess: (pageData) => {
                const statusMessage = (pageData as any)?.props?.status;
                if (statusMessage) {
                    toast({
                        title: statusMessage,
                        variant: 'success',
                    });
                }
            },
        }
    );
};

onMounted(() => {
    if (props.status) {
        toast({
            title: props.status,
            variant: 'success',
        });
    }

    if (form.errors.code) {
        toast({
            title: form.errors.code,
            variant: 'destructive',
        });
    }
});

watch(
    () => form.errors.code,
    (val) => {
        if (val) {
            toast({
                title: val,
                variant: 'destructive',
            });
        }
    }
);
</script>

<template>
    <AuthBase title="Verifikasi OTP" description="Masukkan kode OTP yang dikirim ke email Anda">
        <Head title="Verifikasi OTP" />

        <div class="flex flex-col gap-6">
            <div
                v-if="status"
                class="flex items-center gap-3 rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-950 dark:text-green-200"
            >
                <AlertCircle class="h-5 w-5 shrink-0" />
                <span>{{ status }}</span>
            </div>

            <div class="rounded-lg border bg-card p-5 shadow-sm">
                <div class="mb-4 space-y-1">
                    <p class="text-sm text-muted-foreground">
                        Kami telah mengirim kode OTP ke email:
                    </p>
                    <p class="text-sm font-semibold flex items-center gap-2">
                        <Mail class="h-4 w-4" />
                        <span>{{ email }}</span>
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Kode OTP berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.
                    </p>
                </div>

                <form @submit.prevent="submit" class="grid gap-5">
                    <div class="grid gap-2.5">
                        <Label for="code" class="text-sm font-medium">Kode OTP</Label>
                        <div class="relative">
                            <Lock class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                            <Input
                                id="code"
                                type="text"
                                required
                                autofocus
                                autocomplete="one-time-code"
                                v-model="form.code"
                                placeholder="Masukkan 6 digit kode OTP"
                                class="h-11 pl-10 text-center tracking-[0.4em] uppercase"
                            />
                        </div>
                        <InputError :message="form.errors.code" />
                    </div>

                    <div class="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Belum menerima kode?</span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline disabled:cursor-not-allowed disabled:opacity-60"
                            @click="resend"
                            :disabled="form.processing || isResending"
                        >
                            <RefreshCw class="h-3 w-3" />
                            Kirim ulang kode
                        </button>
                    </div>

                    <Button
                        type="submit"
                        class="mt-1 h-11 w-full font-semibold shadow-sm transition-all hover:shadow-md"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span v-if="!form.processing">Verifikasi &amp; Masuk</span>
                        <span v-else>Memproses...</span>
                    </Button>
                </form>
            </div>

            <Separator class="my-2" />

            <div class="text-muted-foreground text-center text-sm">
                Salah email atau ingin ganti akun?
                <TextLink :href="route('login')" class="font-semibold text-primary hover:underline">
                    Kembali ke halaman login
                </TextLink>
            </div>
        </div>
    </AuthBase>
</template>

