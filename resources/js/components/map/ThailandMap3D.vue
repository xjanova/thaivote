<template>
    <div ref="container" class="thailand-map-3d">
        <!-- Loading overlay -->
        <Transition name="fade">
            <div v-if="isLoading" class="loading-overlay">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p class="mt-4 text-white text-lg">กำลังโหลดแผนที่...</p>
                </div>
            </div>
        </Transition>

        <!-- Controls -->
        <div class="controls-panel">
            <button class="control-btn" title="หมุนอัตโนมัติ" @click="toggleAutoRotate">
                <svg
                    :class="{ 'animate-spin': autoRotate }"
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
            </button>
            <button class="control-btn" title="รีเซ็ตมุมมอง" @click="resetCamera">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                    />
                </svg>
            </button>
            <button class="control-btn" title="โหมด 2D/3D" @click="toggle2D3D">
                <span class="text-xs font-bold">{{ is3DMode ? '3D' : '2D' }}</span>
            </button>
        </div>

        <!-- Province Info Panel -->
        <Transition name="slide-up">
            <div v-if="selectedProvince" class="province-info-panel">
                <div
                    class="panel-header"
                    :style="{ backgroundColor: getWinnerColor(selectedProvince) }"
                >
                    <h3 class="text-2xl font-bold text-white">{{ selectedProvince.name_th }}</h3>
                    <p class="text-white/80">{{ selectedProvince.name_en }}</p>
                </div>
                <div class="panel-content">
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="stat-box">
                            <span class="stat-value">{{ selectedProvince.constituencies }}</span>
                            <span class="stat-label">เขต</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value">{{
                                formatNumber(selectedProvince.population)
                            }}</span>
                            <span class="stat-label">ประชากร</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value"
                                >{{ getCountingProgress(selectedProvince) }}%</span
                            >
                            <span class="stat-label">นับแล้ว</span>
                        </div>
                    </div>

                    <!-- Party Results -->
                    <div v-if="provinceResults.length" class="space-y-3">
                        <div
                            v-for="(result, index) in provinceResults.slice(0, 5)"
                            :key="result.party_id"
                            class="party-result"
                            :style="{ animationDelay: `${index * 0.1}s` }"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="party-badge"
                                    :style="{ backgroundColor: result.party?.color }"
                                >
                                    {{ result.party?.abbreviation?.substring(0, 2) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-medium">{{ result.party?.name_th }}</span>
                                        <span class="text-lg font-bold">{{
                                            result.seats_won
                                        }}</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div
                                            class="progress-fill"
                                            :style="{
                                                width: `${result.vote_percentage}%`,
                                                backgroundColor: result.party?.color,
                                            }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>{{ formatNumber(result.total_votes) }} คะแนน</span>
                                        <span>{{ result.vote_percentage?.toFixed(1) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        class="mt-4 w-full py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                        @click="$emit('viewDetails', selectedProvince)"
                    >
                        ดูรายละเอียดเพิ่มเติม
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Legend -->
        <div class="legend-panel">
            <h5 class="text-sm font-semibold mb-2 text-gray-600">พรรคที่นำ</h5>
            <div class="legend-items">
                <div
                    v-for="party in topParties"
                    :key="party.id"
                    class="legend-item"
                    @click="highlightParty(party)"
                >
                    <div class="legend-color" :style="{ backgroundColor: party.color }"></div>
                    <span class="legend-name">{{ party.abbreviation }}</span>
                    <span class="legend-count">{{ party.seats }}</span>
                </div>
            </div>
        </div>

        <!-- Real-time indicator -->
        <div class="realtime-indicator">
            <span class="pulse-dot"></span>
            <span class="text-sm">LIVE</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';
import gsap from 'gsap';
import { provinces as provinceData, provincePaths, regions } from '@/data/provinces';

const props = defineProps({
    electionId: { type: Number, required: true },
    results: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['provinceSelected', 'viewDetails']);

// Refs
const container = ref(null);
const isLoading = ref(true);
const autoRotate = ref(true);
const is3DMode = ref(true);
const selectedProvince = ref(null);

// Three.js objects
let scene, camera, renderer, controls;
const provinceMeshes = {};
let raycaster, mouse;
let animationId;

// Province results
const provinceResults = computed(() => {
    if (!selectedProvince.value || !props.results.provinces) {
        return [];
    }
    const provinceData = props.results.provinces[selectedProvince.value.id];
    return provinceData?.parties?.sort((a, b) => b.seats_won - a.seats_won) || [];
});

// Top parties
const topParties = computed(() => {
    if (!props.results.national) {
        return [];
    }
    return props.results.national.parties?.slice(0, 8) || [];
});

// Initialize Three.js scene
const initScene = () => {
    // Scene
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0a1a);

    // Add fog for depth
    scene.fog = new THREE.FogExp2(0x0a0a1a, 0.002);

    // Camera
    camera = new THREE.PerspectiveCamera(
        45,
        container.value.clientWidth / container.value.clientHeight,
        0.1,
        1000
    );
    camera.position.set(0, 80, 120);

    // Renderer
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
    });
    renderer.setSize(container.value.clientWidth, container.value.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.value.appendChild(renderer.domElement);

    // Controls
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.maxPolarAngle = Math.PI / 2.2;
    controls.minDistance = 50;
    controls.maxDistance = 200;
    controls.autoRotate = autoRotate.value;
    controls.autoRotateSpeed = 0.5;

    // Raycaster for mouse interaction
    raycaster = new THREE.Raycaster();
    mouse = new THREE.Vector2();

    // Lights
    addLights();

    // Create map
    createThailandMap();

    // Add particles
    addParticles();

    // Add grid
    addGrid();

    // Event listeners
    window.addEventListener('resize', onWindowResize);
    renderer.domElement.addEventListener('click', onMouseClick);
    renderer.domElement.addEventListener('mousemove', onMouseMove);

    isLoading.value = false;
};

const addLights = () => {
    // Ambient light
    const ambientLight = new THREE.AmbientLight(0x404070, 0.5);
    scene.add(ambientLight);

    // Main directional light
    const mainLight = new THREE.DirectionalLight(0xffffff, 1);
    mainLight.position.set(50, 100, 50);
    mainLight.castShadow = true;
    mainLight.shadow.mapSize.width = 2048;
    mainLight.shadow.mapSize.height = 2048;
    scene.add(mainLight);

    // Colored accent lights
    const orangeLight = new THREE.PointLight(0xff6b00, 0.5, 100);
    orangeLight.position.set(-30, 30, 30);
    scene.add(orangeLight);

    const blueLight = new THREE.PointLight(0x0066ff, 0.5, 100);
    blueLight.position.set(30, 30, -30);
    scene.add(blueLight);
};

const createThailandMap = () => {
    const mapGroup = new THREE.Group();

    // Thailand approximate bounds
    const bounds = { minX: 97, maxX: 106, minY: 5, maxY: 21 };
    const scale = 8;
    const centerX = (bounds.minX + bounds.maxX) / 2;
    const centerY = (bounds.minY + bounds.maxY) / 2;

    provinceData.forEach((province, index) => {
        // Create province geometry (simplified as extruded hexagon)
        const geometry = createProvinceGeometry(province, bounds, scale, centerX, centerY);

        // Material with party color or region color
        const color = getProvinceColor(province);
        const material = new THREE.MeshPhysicalMaterial({
            color: new THREE.Color(color),
            metalness: 0.2,
            roughness: 0.4,
            clearcoat: 0.3,
            clearcoatRoughness: 0.2,
            transparent: true,
            opacity: 0.9,
        });

        const mesh = new THREE.Mesh(geometry, material);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        mesh.userData = { province, index };

        // Position based on province code
        const pos = getProvincePosition(province, bounds, scale, centerX, centerY);
        mesh.position.set(pos.x, 0, pos.z);

        // Animate entrance
        mesh.scale.set(0, 0, 0);
        gsap.to(mesh.scale, {
            x: 1,
            y: 1,
            z: 1,
            duration: 0.5,
            delay: index * 0.02,
            ease: 'back.out(1.5)',
        });

        provinceMeshes[province.code] = mesh;
        mapGroup.add(mesh);
    });

    scene.add(mapGroup);
};

const createProvinceGeometry = (province, bounds, scale, centerX, centerY) => {
    // Create extruded shape based on constituency count
    const height = Math.max(2, province.constituencies * 0.5);
    const size = Math.sqrt(province.population / 100000) * 0.8 + 1;

    // Hexagonal shape for each province
    const shape = new THREE.Shape();
    const sides = 6;
    const radius = size;

    for (let i = 0; i < sides; i++) {
        const angle = (i / sides) * Math.PI * 2 - Math.PI / 2;
        const x = Math.cos(angle) * radius;
        const y = Math.sin(angle) * radius;
        if (i === 0) {
            shape.moveTo(x, y);
        } else {
            shape.lineTo(x, y);
        }
    }
    shape.closePath();

    const extrudeSettings = {
        steps: 1,
        depth: height,
        bevelEnabled: true,
        bevelThickness: 0.2,
        bevelSize: 0.2,
        bevelSegments: 2,
    };

    return new THREE.ExtrudeGeometry(shape, extrudeSettings);
};

const getProvincePosition = (province, bounds, scale, centerX, centerY) => {
    // Approximate positions based on region and index
    const regionPositions = {
        north: { baseX: -15, baseZ: -40, spread: 12 },
        northeast: { baseX: 25, baseZ: -25, spread: 15 },
        central: { baseX: 0, baseZ: 0, spread: 12 },
        east: { baseX: 30, baseZ: 15, spread: 10 },
        west: { baseX: -25, baseZ: 10, spread: 10 },
        south: { baseX: 0, baseZ: 45, spread: 12 },
    };

    const regionData = regionPositions[province.region] || regionPositions.central;
    const regionProvinces = provinceData.filter((p) => p.region === province.region);
    const indexInRegion = regionProvinces.findIndex((p) => p.id === province.id);

    // Spiral layout within region
    const angle = (indexInRegion / regionProvinces.length) * Math.PI * 2;
    const distance = regionData.spread * (0.3 + (indexInRegion % 3) * 0.3);

    return {
        x: regionData.baseX + Math.cos(angle) * distance,
        z: regionData.baseZ + Math.sin(angle) * distance,
    };
};

const getProvinceColor = (province) => {
    if (props.results.provinces && props.results.provinces[province.id]) {
        const winner = props.results.provinces[province.id].parties?.[0];
        if (winner?.party?.color) {
            return winner.party.color;
        }
    }
    return regions[province.region]?.color || '#6B7280';
};

const getWinnerColor = (province) => {
    if (props.results.provinces && props.results.provinces[province.id]) {
        const winner = props.results.provinces[province.id].parties?.[0];
        if (winner?.party?.color) {
            return winner.party.color;
        }
    }
    return regions[province.region]?.color || '#6B7280';
};

const getCountingProgress = (province) => {
    if (props.results.provinces && props.results.provinces[province.id]) {
        return props.results.provinces[province.id].counting_progress || 0;
    }
    return 0;
};

const addParticles = () => {
    const particleCount = 500;
    const geometry = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);

    for (let i = 0; i < particleCount; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 200;
        positions[i * 3 + 1] = Math.random() * 100;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 200;

        // Orange to blue gradient
        const t = Math.random();
        colors[i * 3] = 1 - t * 0.5;
        colors[i * 3 + 1] = 0.4 + t * 0.2;
        colors[i * 3 + 2] = t;
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

    const material = new THREE.PointsMaterial({
        size: 0.5,
        vertexColors: true,
        transparent: true,
        opacity: 0.6,
        blending: THREE.AdditiveBlending,
    });

    const particles = new THREE.Points(geometry, material);
    scene.add(particles);

    // Animate particles
    gsap.to(particles.rotation, {
        y: Math.PI * 2,
        duration: 100,
        repeat: -1,
        ease: 'none',
    });
};

const addGrid = () => {
    const gridHelper = new THREE.GridHelper(200, 50, 0x222244, 0x111122);
    gridHelper.position.y = -5;
    scene.add(gridHelper);
};

const onWindowResize = () => {
    camera.aspect = container.value.clientWidth / container.value.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.value.clientWidth, container.value.clientHeight);
};

const onMouseClick = (event) => {
    const rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObjects(Object.values(provinceMeshes));

    if (intersects.length > 0) {
        const province = intersects[0].object.userData.province;
        selectProvince(province);
    }
};

const onMouseMove = (event) => {
    const rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObjects(Object.values(provinceMeshes));

    // Reset all
    Object.values(provinceMeshes).forEach((mesh) => {
        gsap.to(mesh.position, { y: 0, duration: 0.3 });
        mesh.material.emissive?.setHex(0x000000);
    });

    // Highlight hovered
    if (intersects.length > 0) {
        const mesh = intersects[0].object;
        gsap.to(mesh.position, { y: 3, duration: 0.3 });
        mesh.material.emissive?.setHex(0x222222);
        renderer.domElement.style.cursor = 'pointer';
    } else {
        renderer.domElement.style.cursor = 'grab';
    }
};

const selectProvince = (province) => {
    selectedProvince.value = province;
    emit('provinceSelected', province);

    // Animate camera to province
    const mesh = provinceMeshes[province.code];
    if (mesh) {
        const targetPos = mesh.position.clone();
        gsap.to(camera.position, {
            x: targetPos.x + 30,
            y: 50,
            z: targetPos.z + 50,
            duration: 1,
            ease: 'power2.out',
        });
        gsap.to(controls.target, {
            x: targetPos.x,
            y: 5,
            z: targetPos.z,
            duration: 1,
            ease: 'power2.out',
        });

        // Pulse animation
        gsap.to(mesh.scale, {
            x: 1.2,
            y: 1.2,
            z: 1.2,
            duration: 0.3,
            yoyo: true,
            repeat: 1,
        });
    }
};

const highlightParty = (party) => {
    Object.entries(provinceMeshes).forEach(([code, mesh]) => {
        const province = mesh.userData.province;
        const isPartyProvince =
            props.results.provinces?.[province.id]?.parties?.[0]?.party?.id === party.id;

        gsap.to(mesh.material, {
            opacity: isPartyProvince ? 1 : 0.3,
            duration: 0.5,
        });

        if (isPartyProvince) {
            gsap.to(mesh.position, { y: 5, duration: 0.5 });
        } else {
            gsap.to(mesh.position, { y: 0, duration: 0.5 });
        }
    });
};

const toggleAutoRotate = () => {
    autoRotate.value = !autoRotate.value;
    controls.autoRotate = autoRotate.value;
};

const resetCamera = () => {
    gsap.to(camera.position, {
        x: 0,
        y: 80,
        z: 120,
        duration: 1,
        ease: 'power2.out',
    });
    gsap.to(controls.target, {
        x: 0,
        y: 0,
        z: 0,
        duration: 1,
        ease: 'power2.out',
    });
    selectedProvince.value = null;

    // Reset all provinces
    Object.values(provinceMeshes).forEach((mesh) => {
        gsap.to(mesh.material, { opacity: 0.9, duration: 0.5 });
        gsap.to(mesh.position, { y: 0, duration: 0.5 });
    });
};

const toggle2D3D = () => {
    is3DMode.value = !is3DMode.value;

    if (is3DMode.value) {
        gsap.to(camera.position, {
            x: 0,
            y: 80,
            z: 120,
            duration: 1,
        });
        controls.maxPolarAngle = Math.PI / 2.2;
    } else {
        gsap.to(camera.position, {
            x: 0,
            y: 150,
            z: 0,
            duration: 1,
        });
        controls.maxPolarAngle = 0;
    }
};

const formatNumber = (num) => {
    if (!num) {
        return '0';
    }
    if (num >= 1000000) {
        return `${(num / 1000000).toFixed(1)}M`;
    }
    if (num >= 1000) {
        return `${(num / 1000).toFixed(0)}K`;
    }
    return num.toLocaleString('th-TH');
};

// Animation loop
const animate = () => {
    animationId = requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
};

// Update colors when results change
watch(
    () => props.results,
    () => {
        Object.entries(provinceMeshes).forEach(([code, mesh]) => {
            const province = mesh.userData.province;
            const newColor = getProvinceColor(province);
            gsap.to(mesh.material.color, {
                r: new THREE.Color(newColor).r,
                g: new THREE.Color(newColor).g,
                b: new THREE.Color(newColor).b,
                duration: 1,
            });
        });
    },
    { deep: true }
);

// Lifecycle
onMounted(() => {
    initScene();
    animate();
});

onUnmounted(() => {
    cancelAnimationFrame(animationId);
    window.removeEventListener('resize', onWindowResize);
    renderer?.dispose();
});
</script>

<style scoped>
@reference "tailwindcss";

.thailand-map-3d {
    @apply relative w-full h-[700px] lg:h-[900px] rounded-3xl overflow-hidden;
    background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3a 50%, #0a0a1a 100%);
}

.loading-overlay {
    @apply absolute inset-0 bg-black/80 flex items-center justify-center z-50;
}

.loading-spinner {
    @apply text-center;
}

.spinner {
    @apply w-16 h-16 border-4 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto;
}

.controls-panel {
    @apply absolute top-4 right-4 z-20 flex flex-col gap-2;
}

.control-btn {
    @apply w-12 h-12 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all border border-white/10;
}

.province-info-panel {
    @apply absolute bottom-4 left-4 w-80 bg-white/10 backdrop-blur-xl rounded-2xl overflow-hidden z-20 border border-white/20;
}

.panel-header {
    @apply p-4;
}

.panel-content {
    @apply p-4 bg-black/40;
}

.stat-box {
    @apply bg-white/10 rounded-xl p-3 text-center;
}

.stat-value {
    @apply block text-2xl font-bold text-white;
}

.stat-label {
    @apply text-xs text-white/60;
}

.party-result {
    @apply bg-white/5 rounded-xl p-3;
    animation: fadeIn 0.5s ease forwards;
}

.party-badge {
    @apply w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm;
}

.progress-bar {
    @apply w-full h-2 bg-white/10 rounded-full overflow-hidden;
}

.progress-fill {
    @apply h-full rounded-full transition-all duration-1000;
}

.legend-panel {
    @apply absolute top-4 left-4 bg-white/10 backdrop-blur-md rounded-xl p-4 z-20 border border-white/10;
}

.legend-items {
    @apply space-y-2 max-h-60 overflow-y-auto;
}

.legend-item {
    @apply flex items-center gap-2 cursor-pointer hover:bg-white/10 rounded-lg px-2 py-1 transition-colors;
}

.legend-color {
    @apply w-4 h-4 rounded;
}

.legend-name {
    @apply text-white text-sm flex-1;
}

.legend-count {
    @apply text-white/60 text-sm font-bold;
}

.realtime-indicator {
    @apply absolute top-4 left-1/2 -translate-x-1/2 flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-full z-20;
}

.pulse-dot {
    @apply w-3 h-3 bg-white rounded-full animate-pulse;
}

/* Animations */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.5s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease forwards;
}
</style>
