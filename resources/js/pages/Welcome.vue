<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const page = usePage();

delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

onMounted(() => {
    if (mapContainer.value) {
        const galugaLat = -6.5641311;
        const galugaLng = 106.6438673;

        const galugaBoundary: [number, number][] = [
  [-6.5574, 106.6298],
  [-6.5559, 106.6312],
  [-6.5532, 106.6347],
  [-6.5521, 106.6395],
  [-6.5538, 106.6459],
  [-6.5581, 106.6508],
  [-6.5637, 106.6532],
  [-6.5704, 106.6519],
  [-6.5761, 106.6473],
  [-6.5789, 106.6405],
  [-6.5764, 106.6338],
  [-6.5718, 106.6301],
  [-6.5574, 106.6298], 
];


        map = L.map(mapContainer.value, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        const boundaryPolygon = L.polygon(galugaBoundary, {
            color: '#dc2626', // Warna merah
            weight: 3,
            fillColor: '#dc2626',
            fillOpacity: 0.1, // Transparan sedikit
            dashArray: '10, 10', // Garis putus-putus
        }).addTo(map);

        // Fit map ke boundary area
        map.fitBounds(boundaryPolygon.getBounds(), {
            padding: [20, 20], // Padding di sekitar boundary
        });
    }
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
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
                <div
                    ref="mapContainer"
                    class="relative z-0 h-[600px] w-full rounded-lg border border-border shadow-lg"
                ></div>
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
