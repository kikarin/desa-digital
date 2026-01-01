<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

// Import leaflet-draw - harus diimport setelah L
import 'leaflet-draw';
import 'leaflet-draw/dist/leaflet.draw.css';

const props = defineProps<{
    modelValue?: number[][]; // Array of [lat, lng] pairs
    height?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[][]];
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let drawnLayer: L.FeatureGroup | null = null;
let drawControl: any = null;

// Batas wilayah Desa Galuga (sama seperti di WelcomeMap)
const galugaLat = -6.5641311;
const galugaLng = 106.6438673;

const outerMask: [number, number][] = [
    [-7.5, 105.5],
    [-7.5, 108.5],
    [-5.5, 108.5],
    [-5.5, 105.5],
];

const galugaBoundary: [number, number][] = [
    // Garis 1
    [-6.5577674, 106.6478618],
    [-6.5578207, 106.6478725],
    [-6.557874, 106.6479718],
    [-6.558335, 106.6479637],
    [-6.5584309, 106.6480549],
    [-6.5593262, 106.6480254],
    [-6.5595181, 106.648071],
    [-6.5597419, 106.6482829],
    [-6.5598911, 106.6484304],
    [-6.560107, 106.6484546],
    [-6.5601602, 106.6484412],
    [-6.5602935, 106.6483178],
    [-6.5603468, 106.648291],
    [-6.5604027, 106.648299],
    [-6.560448, 106.6483205],
    [-6.560496, 106.6483527],
    [-6.5605466, 106.6484653],
    [-6.5605733, 106.6484868],
    [-6.5606745, 106.6484921],
    [-6.5607731, 106.6484492],
    [-6.560917, 106.648189],
    [-6.5610076, 106.6481461],
    [-6.5615405, 106.6483178],
    [-6.5617617, 106.648181],
    [-6.5621907, 106.6485297],
    [-6.5623453, 106.648527],
    [-6.5626544, 106.6484224],
    [-6.5627663, 106.6484519],
    [-6.5629661, 106.6485055],
    [-6.5630674, 106.6485994],
    [-6.5631953, 106.6488274],
    [-6.5632672, 106.6489159],
    [-6.5633445, 106.6489454],
    [-6.5634351, 106.6489749],
    [-6.5635124, 106.6489857],
    [-6.563571, 106.648983],
    [-6.5636323, 106.6489454],
    [-6.5636722, 106.6489454],
    [-6.5638028, 106.6488757],
    [-6.5638987, 106.648763],
    [-6.5639494, 106.648704],
    [-6.5640027, 106.6486772],
    [-6.5640426, 106.6486853],
    [-6.5640773, 106.6486692],
    [-6.5644263, 106.6487684],
    [-6.5650845, 106.648806],
    [-6.5651313, 106.6488846],
    [-6.5651473, 106.649008],
    [-6.5651126, 106.6491749],
    [-6.5651286, 106.6492936],
    [-6.5651852, 106.6493875],
    [-6.5653471, 106.6495357],
    [-6.5654563, 106.6495712],
    [-6.5656196, 106.649586],
    [-6.5657428, 106.6497208],
    [-6.5658908, 106.6498148],
    [-6.565992, 106.6498256],
    [-6.5662265, 106.6497853],
    [-6.5664077, 106.6497183],
    [-6.5664716, 106.6497209],
    [-6.5666129, 106.6498336],
    [-6.5668314, 106.6500562],
    [-6.5669513, 106.6501689],
    [-6.5670712, 106.6502386],
    [-6.5671405, 106.6502708],
    [-6.5675108, 106.6504076],
    [-6.5676121, 106.6504666],
    [-6.5677027, 106.6505176],
    [-6.56774, 106.6505497],
    [-6.5678759, 106.6505417],
    [-6.5683395, 106.6503271],
    [-6.5683875, 106.6500482],
    [-6.568598, 106.64978],
    [-6.5685714, 106.6495895],
    [-6.568622, 106.6495037],
    [-6.5686993, 106.6494635],
    [-6.5689737, 106.6490289],
    [-6.5697504, 106.6489498],
    [-6.569873, 106.6487218],
    [-6.5704779, 106.6486802],
    [-6.5708669, 106.6486159],
    [-6.5717622, 106.6488787],
    [-6.5718288, 106.6487205],
    [-6.5720766, 106.6486427],
    [-6.572431, 106.6482565],
    [-6.5734249, 106.6471621],
    [-6.5758595, 106.6451643],
    [-6.5757056, 106.6445662],
    [-6.575675, 106.6423614],
    [-6.575639, 106.6422005],
    [-6.5753179, 106.6412966],
    [-6.5750888, 106.6408473],
    [-6.5750422, 106.6404959],
    [-6.5749942, 106.6400466],
    [-6.5745812, 106.6394056],
    [-6.5738498, 106.639985],
    [-6.5734767, 106.6400708],
    [-6.5728719, 106.6400761],
    [-6.5724855, 106.6398535],
    [-6.5718593, 106.6387914],
    [-6.571028, 106.638271],
    [-6.5698236, 106.6365893],
    [-6.5690748, 106.6360984],
    [-6.5687471, 106.636077],
    [-6.5682674, 106.6361172],
    [-6.5680503, 106.6366215],
    [-6.5681875, 106.6384085],
    [-6.5681216, 106.6394093],
    [-6.5670494, 106.6397487],
    [-6.5661531, 106.6398414],
    // Garis 2
    [-6.5645428, 106.6392851],
    [-6.5644575, 106.6388988],
    [-6.56644, 106.6370106],
    [-6.5634556, 106.6340065],
    [-6.5629653, 106.6335988],
    [-6.5623258, 106.6331267],
    [-6.5615158, 106.6327405],
    [-6.5606418, 106.6325688],
    [-6.5600449, 106.6327619],
    [-6.5595333, 106.6330194],
    [-6.5589364, 106.6329551],
    [-6.5579345, 106.6330194],
    [-6.5568686, 106.6329765],
    [-6.5561012, 106.6326547],
    [-6.5549927, 106.6334271],
    [-6.5549074, 106.6339207],
    [-6.5556109, 106.6350579],
    [-6.5563357, 106.6354227],
    [-6.5573163, 106.6355514],
    [-6.5579132, 106.6358948],
    [-6.5584035, 106.6360235],
    [-6.5600236, 106.6359162],
    [-6.5605991, 106.6360664],
    [-6.5609615, 106.6364312],
    [-6.5610042, 106.637311],
    [-6.560322, 106.6379762],
    [-6.5593201, 106.6381693],
    [-6.558979, 106.6380834],
    [-6.5586806, 106.638577],
    [-6.558574, 106.6395855],
    // Garis 3
    [-6.5595497, 106.6402292],
    [-6.5597629, 106.6407871],
    [-6.5594857, 106.64186],
    [-6.5585904, 106.643877],
    [-6.5581854, 106.6447568],
    [-6.5576231, 106.6477555],
    // Penutup (kembali ke titik awal)
    [-6.5577674, 106.6478618],
];

// Load existing boundary jika ada
const loadBoundary = (coordinates: number[][]) => {
    if (!map || !drawnLayer) return;

    drawnLayer.clearLayers();

    if (coordinates && coordinates.length > 0) {
        const latlngs = coordinates.map((coord) => [coord[0], coord[1]] as [number, number]);
        const polygon = L.polygon(latlngs, {
            color: '#3388ff',
            weight: 3,
            fillOpacity: 0.2,
        });

        drawnLayer.addLayer(polygon);
        map.fitBounds(polygon.getBounds(), { padding: [20, 20] });
    }
};

// Setup Leaflet Draw
const initLeafletDraw = () => {
    if (!map || !drawnLayer) return;
    
    try {
        // Setup Leaflet Draw
        // @ts-ignore - L.Control.Draw dari leaflet-draw
        drawControl = new L.Control.Draw({
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: false,
                    shapeOptions: {
                        color: '#3388ff',
                    },
                },
                circle: false,
                rectangle: false,
                marker: false,
                circlemarker: false,
                polyline: false,
            },
            edit: {
                featureGroup: drawnLayer,
                remove: true,
            },
        });

        map.addControl(drawControl);

        // Event ketika polygon selesai digambar
        // @ts-ignore
        map.on(L.Draw.Event.CREATED, (e: any) => {
            const layerType = e.layerType;
            const layer = e.layer;

            if (layerType === 'polygon') {
                // Hapus polygon sebelumnya jika ada
                drawnLayer!.clearLayers();
                drawnLayer!.addLayer(layer);

                // Emit koordinat
                const latlngs = layer.getLatLngs()[0] as L.LatLng[];
                const coordinates = latlngs.map((ll) => [ll.lat, ll.lng]);
                emit('update:modelValue', coordinates);
            }
        });

        // Event ketika polygon diedit
        // @ts-ignore
        map.on(L.Draw.Event.EDITED, (e: any) => {
            const layers = e.layers;
            layers.eachLayer((layer: L.Polygon) => {
                const latlngs = layer.getLatLngs()[0] as L.LatLng[];
                const coordinates = latlngs.map((ll) => [ll.lat, ll.lng]);
                emit('update:modelValue', coordinates);
            });
        });

        // Event ketika polygon dihapus
        // @ts-ignore
        map.on(L.Draw.Event.DELETED, () => {
            drawnLayer!.clearLayers();
            emit('update:modelValue', []);
        });
    } catch (error) {
        console.error('Error initializing leaflet-draw:', error);
    }
};

onMounted(async () => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value, {
        zoomControl: true,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Tambahkan mask polygon (merah di luar)
    L.polygon(
        [outerMask, galugaBoundary],
        {
            stroke: false,
            fillColor: '#000000',
            fillOpacity: 0.6,
        }
    ).addTo(map);

    // Tambahkan garis batas Galuga
    L.polygon(galugaBoundary, {
        color: '#dc2626',
        weight: 3,
        fillOpacity: 0,
    }).addTo(map);

    // Set view ke Galuga
    map.setView([galugaLat, galugaLng], 14);

    // FeatureGroup untuk menyimpan drawn polygon
    drawnLayer = new L.FeatureGroup();
    map.addLayer(drawnLayer);

    // Initialize Leaflet Draw
    initLeafletDraw();

    // Load existing boundary jika ada
    if (props.modelValue && props.modelValue.length > 0) {
        loadBoundary(props.modelValue);
    }
});

// Watch perubahan dari props
watch(() => props.modelValue, (newValue) => {
    if (newValue && newValue.length > 0 && map && drawnLayer) {
        loadBoundary(newValue);
    } else if ((!newValue || newValue.length === 0) && drawnLayer) {
        drawnLayer.clearLayers();
    }
}, { deep: true });

onUnmounted(() => {
    if (map) {
        if (drawControl) {
            map.removeControl(drawControl);
        }
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Batas Wilayah</CardTitle>
        </CardHeader>
        <CardContent>
            <div
                ref="mapContainer"
                :style="{ height: height || '500px', width: '100%' }"
                class="rounded-lg border"
            ></div>
            <p class="mt-2 text-sm text-muted-foreground">
                <strong>Cara menggambar batas wilayah:</strong><br/>
                1. Klik ikon polygon (ikon terakhir di toolbar)<br/>
                2. Klik di peta untuk membuat titik pertama<br/>
                3. Lanjutkan klik di peta untuk menambahkan lebih banyak titik<br/>
                4. Klik dua kali (double-click) pada titik terakhir untuk menutup polygon, atau klik kembali ke titik pertama<br/>
                5. Setelah polygon tertutup, Anda bisa edit atau hapus menggunakan toolbar edit
            </p>
        </CardContent>
    </Card>
</template>

