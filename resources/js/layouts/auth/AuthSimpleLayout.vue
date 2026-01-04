<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-background via-background to-muted/20 p-6 md:p-10">
        <!-- Decorative Background Elements -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-1/4 -top-1/4 h-96 w-96 rounded-full bg-primary/5 blur-3xl"></div>
            <div class="absolute -bottom-1/4 -right-1/4 h-96 w-96 rounded-full bg-accent/5 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-md">
            <Card class="border-2 shadow-2xl backdrop-blur-sm">
                <CardHeader class="space-y-6 pb-8">
                    <div class="flex flex-col items-center gap-4">
                        <Link :href="route('home')" class="group flex flex-col items-center gap-3 font-medium transition-transform hover:scale-105">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 ring-4 ring-primary/5 transition-all group-hover:bg-primary/15 group-hover:ring-primary/10">
                                <AppLogoIcon class="size-10 fill-current text-primary dark:text-white" />
                            </div>
                            <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="space-y-2 text-center">
                            <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>
                            <p class="text-muted-foreground text-center text-sm leading-relaxed">{{ description }}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pb-8">
                    <slot />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
