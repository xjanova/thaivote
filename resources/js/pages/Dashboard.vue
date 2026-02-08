<template>
    <div class="min-h-screen bg-gray-950 text-white">
        <!-- Live Banner -->
        <div v-if="isLive" class="live-banner">
            <div class="container mx-auto px-4 flex items-center justify-center gap-3">
                <span class="live-dot"></span>
                <span class="font-bold tracking-wider text-sm">LIVE</span>
                <span class="text-white/60">|</span>
                <span class="text-sm">กำลังนับคะแนน</span>
                <span class="text-white/60">|</span>
                <span class="text-sm text-white/80">อัปเดต: {{ formatTime(lastUpdated) }}</span>
            </div>
        </div>

        <!-- Header -->
        <header class="header-gradient sticky top-0 z-40 backdrop-blur-xl border-b border-white/10">
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="logo-mark">
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold">{{ siteName }}</h1>
                            <p class="text-xs text-white/50">
                                ผลเลือกตั้ง ส.ส. 2569 | 8 กุมภาพันธ์ 2569
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="hidden md:flex items-center gap-2 bg-white/5 rounded-full px-4 py-1.5"
                        >
                            <span class="text-xs text-white/50">แหล่งข้อมูล:</span>
                            <a
                                href="https://ectreport69.ect.go.th"
                                target="_blank"
                                class="text-xs text-orange-400 hover:text-orange-300 transition"
                                >กกต.</a
                            >
                        </div>
                        <template v-if="auth?.user">
                            <Link v-if="auth.user.is_admin" href="/admin" class="btn-glass text-sm"
                                >หลังบ้าน</Link
                            >
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Stats -->
        <section class="hero-section">
            <div class="container mx-auto px-4 py-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl md:text-4xl font-bold mb-2">
                        <span class="text-gradient-orange">ผลการเลือกตั้ง</span>
                        สมาชิกสภาผู้แทนราษฎร
                    </h2>
                    <p class="text-white/50 text-sm">
                        ข้อมูลอย่างไม่เป็นทางการ จาก สำนักงานคณะกรรมการการเลือกตั้ง (กกต.)
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="stat-card">
                        <div class="stat-card-icon orange">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-white/50 mb-1">นับคะแนนแล้ว</p>
                            <p class="text-2xl font-bold text-orange-400">
                                {{ stats?.counting_progress?.toFixed(1) || '0.0' }}%
                            </p>
                        </div>
                        <div class="stat-progress">
                            <div
                                class="stat-progress-bar"
                                :style="{ width: (stats?.counting_progress || 0) + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon blue">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-white/50 mb-1">ผู้มาใช้สิทธิ์</p>
                            <p class="text-2xl font-bold">
                                {{ formatNumber(stats?.total_votes_cast) }}
                            </p>
                            <p class="text-xs text-blue-400">
                                {{ stats?.voter_turnout?.toFixed(1) || '0' }}%
                            </p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon green">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-white/50 mb-1">หน่วยเลือกตั้ง</p>
                            <p class="text-2xl font-bold">
                                {{ formatNumber(stats?.stations_counted) }}
                            </p>
                            <p class="text-xs text-green-400">
                                จาก {{ formatNumber(stats?.stations_total) }}
                            </p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon red">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-white/50 mb-1">บัตรเสีย</p>
                            <p class="text-2xl font-bold text-red-400">
                                {{ formatNumber(stats?.invalid_votes) }}
                            </p>
                            <p class="text-xs text-red-400/60">{{ invalidPercent }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seat Bar Visualization -->
        <section class="container mx-auto px-4 mb-6">
            <div class="glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white/70">
                        การกระจายที่นั่ง (500 ที่นั่ง)
                    </h3>
                    <span class="text-xs text-white/40">เป้าหมายจัดตั้งรัฐบาล: 251 ที่นั่ง</span>
                </div>
                <div class="seat-bar">
                    <div
                        v-for="result in sortedResults.slice(0, 10)"
                        :key="result.party_id"
                        class="seat-bar-segment"
                        :style="{
                            width: Math.max((result.total_seats / 500) * 100, 0.5) + '%',
                            backgroundColor: result.party?.color,
                        }"
                        :title="`${result.party?.name_th}: ${result.total_seats} ที่นั่ง`"
                    ></div>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="text-xs text-white/30">0</span>
                    <div class="relative flex-1">
                        <div class="majority-line" :style="{ left: '50.2%' }">
                            <div class="majority-marker"></div>
                            <span class="majority-label">251</span>
                        </div>
                    </div>
                    <span class="text-xs text-white/30">500</span>
                </div>
            </div>
        </section>

        <!-- Tab Navigation -->
        <section class="container mx-auto px-4 mb-6">
            <div class="tab-nav">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    :class="['tab-btn', { active: activeTab === tab.key }]"
                    @click="activeTab = tab.key"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                    <span>{{ tab.label }}</span>
                </button>
            </div>
        </section>

        <!-- Main Content -->
        <main class="container mx-auto px-4 pb-12">
            <!-- Tab: ภาพรวม (Overview) -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Party Results -->
                    <div class="lg:col-span-2">
                        <div class="glass-card">
                            <div class="card-header">
                                <h2 class="text-lg font-bold flex items-center gap-2">
                                    <svg
                                        class="w-5 h-5 text-orange-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                        />
                                    </svg>
                                    ผลคะแนนรายพรรค
                                </h2>
                                <div class="flex gap-1">
                                    <button
                                        :class="['toggle-btn', { active: resultView === 'seats' }]"
                                        @click="resultView = 'seats'"
                                    >
                                        ที่นั่ง
                                    </button>
                                    <button
                                        :class="['toggle-btn', { active: resultView === 'votes' }]"
                                        @click="resultView = 'votes'"
                                    >
                                        คะแนน
                                    </button>
                                </div>
                            </div>
                            <div class="p-4 space-y-2">
                                <TransitionGroup name="party-list" tag="div" class="space-y-2">
                                    <div
                                        v-for="(result, index) in sortedResults"
                                        :key="result.party_id"
                                        class="party-row"
                                        :class="{ 'party-row-leading': index === 0 }"
                                    >
                                        <!-- Rank -->
                                        <div :class="['rank-badge', `rank-${index + 1}`]">
                                            {{ index + 1 }}
                                        </div>
                                        <!-- Party Logo -->
                                        <div
                                            class="party-logo"
                                            :style="{ backgroundColor: result.party?.color }"
                                        >
                                            <img
                                                v-if="result.party?.logo"
                                                :src="result.party.logo"
                                                :alt="result.party?.name_th"
                                                class="w-full h-full object-contain rounded-lg"
                                                @error="onLogoError($event, result)"
                                            />
                                            <span
                                                v-else
                                                class="text-white font-bold text-[10px] leading-none text-center"
                                                >{{ getPartyPlaceholder(result) }}</span
                                            >
                                        </div>
                                        <!-- Party Info -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-semibold text-sm truncate">
                                                    {{ result.party?.name_th }}
                                                </h4>
                                                <span
                                                    class="text-[10px] text-white/30 bg-white/5 px-1.5 py-0.5 rounded"
                                                >
                                                    #{{ result.party?.party_number }}
                                                </span>
                                            </div>
                                            <div class="party-progress-bar mt-1">
                                                <div
                                                    class="party-progress-fill"
                                                    :style="{
                                                        width:
                                                            (result.total_seats / 500) * 100 + '%',
                                                        backgroundColor: result.party?.color,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                        <!-- Seats -->
                                        <div class="text-right">
                                            <p
                                                class="text-xl font-bold"
                                                :style="{ color: result.party?.color }"
                                            >
                                                {{ result.total_seats }}
                                            </p>
                                            <p class="text-[10px] text-white/40">ที่นั่ง</p>
                                        </div>
                                        <!-- Votes -->
                                        <div class="text-right min-w-[90px] hidden sm:block">
                                            <p class="text-sm font-semibold">
                                                {{ formatNumber(result.total_votes) }}
                                            </p>
                                            <p class="text-[10px] text-white/40">
                                                {{ result.vote_percentage?.toFixed(2) }}%
                                            </p>
                                        </div>
                                        <!-- Badge -->
                                        <div class="w-20 text-right hidden md:block">
                                            <span
                                                v-if="result.total_seats >= 251"
                                                class="badge-majority"
                                                >เสียงข้างมาก</span
                                            >
                                            <span v-else-if="index === 0" class="badge-leading"
                                                >นำอยู่</span
                                            >
                                        </div>
                                    </div>
                                </TransitionGroup>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Constituency vs Party List -->
                        <div class="glass-card p-6">
                            <h3
                                class="text-sm font-bold text-white/70 mb-4 flex items-center gap-2"
                            >
                                <svg
                                    class="w-4 h-4 text-orange-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"
                                    />
                                </svg>
                                แบ่งเขต vs บัญชีรายชื่อ
                            </h3>
                            <div class="flex items-center justify-center gap-6">
                                <div class="text-center">
                                    <div class="type-circle constituency">
                                        <span class="text-2xl font-bold">{{
                                            totalConstituencySeats
                                        }}</span>
                                    </div>
                                    <p class="text-xs text-white/50 mt-2">แบ่งเขต</p>
                                    <p class="text-[10px] text-white/30">/400</p>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-px h-8 bg-white/10"></div>
                                    <span class="text-white/20 text-xs py-1">VS</span>
                                    <div class="w-px h-8 bg-white/10"></div>
                                </div>
                                <div class="text-center">
                                    <div class="type-circle partylist">
                                        <span class="text-2xl font-bold">{{
                                            totalPartyListSeats
                                        }}</span>
                                    </div>
                                    <p class="text-xs text-white/50 mt-2">บัญชีรายชื่อ</p>
                                    <p class="text-[10px] text-white/30">/100</p>
                                </div>
                            </div>
                        </div>

                        <!-- Breaking News -->
                        <div class="glass-card">
                            <div class="card-header">
                                <h3 class="text-sm font-bold flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 bg-red-500 rounded-full animate-pulse"
                                    ></span>
                                    ข่าวด่วน
                                </h3>
                            </div>
                            <div class="divide-y divide-white/5">
                                <a
                                    v-for="news in breakingNews"
                                    :key="news.id"
                                    :href="news.url"
                                    class="block p-4 hover:bg-white/5 transition-colors"
                                >
                                    <p class="text-[10px] text-white/30 mb-1">
                                        {{ formatTime(news.published_at) }}
                                    </p>
                                    <h4 class="text-sm font-medium line-clamp-2">
                                        {{ news.title }}
                                    </h4>
                                </a>
                                <div
                                    v-if="!breakingNews.length"
                                    class="p-4 text-center text-sm text-white/30"
                                >
                                    ยังไม่มีข่าวด่วน
                                </div>
                            </div>
                        </div>

                        <!-- Live Feed -->
                        <div class="glass-card">
                            <div class="card-header">
                                <h3 class="text-sm font-bold flex items-center gap-2">
                                    <svg
                                        class="w-4 h-4 text-orange-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"
                                        />
                                    </svg>
                                    อัปเดตล่าสุด
                                </h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto p-4 space-y-3">
                                <div v-for="update in liveFeed" :key="update.id" class="flex gap-3">
                                    <div
                                        class="w-1.5 h-1.5 mt-1.5 rounded-full bg-orange-400 flex-shrink-0"
                                    ></div>
                                    <div>
                                        <p class="text-xs text-white/70">{{ update.message }}</p>
                                        <p class="text-[10px] text-white/30 mt-0.5">
                                            {{ formatTime(update.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="!liveFeed.length"
                                    class="text-center text-sm text-white/30"
                                >
                                    รอการอัปเดต...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: แบ่งเขต (Constituency) -->
            <div v-if="activeTab === 'constituency'" class="space-y-6">
                <div class="glass-card">
                    <div class="card-header">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-orange-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                            </svg>
                            ส.ส. แบ่งเขต (400 ที่นั่ง)
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <div
                            v-for="(result, index) in constituencySorted"
                            :key="result.party_id"
                            class="party-row"
                            :class="{ 'party-row-leading': index === 0 }"
                        >
                            <div :class="['rank-badge', `rank-${index + 1}`]">{{ index + 1 }}</div>
                            <div
                                class="party-logo"
                                :style="{ backgroundColor: result.party?.color }"
                            >
                                <img
                                    v-if="result.party?.logo"
                                    :src="result.party.logo"
                                    :alt="result.party?.name_th"
                                    class="w-full h-full object-contain rounded-lg"
                                    @error="onLogoError($event, result)"
                                />
                                <span
                                    v-else
                                    class="text-white font-bold text-[10px] leading-none text-center"
                                    >{{ getPartyPlaceholder(result) }}</span
                                >
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm truncate">
                                    {{ result.party?.name_th }}
                                </h4>
                                <div class="party-progress-bar mt-1">
                                    <div
                                        class="party-progress-fill"
                                        :style="{
                                            width: (result.constituency_seats / 400) * 100 + '%',
                                            backgroundColor: result.party?.color,
                                        }"
                                    ></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-xl font-bold"
                                    :style="{ color: result.party?.color }"
                                >
                                    {{ result.constituency_seats || 0 }}
                                </p>
                                <p class="text-[10px] text-white/40">ที่นั่ง</p>
                            </div>
                            <div class="text-right min-w-[90px] hidden sm:block">
                                <p class="text-sm font-semibold">
                                    {{ formatNumber(result.constituency_votes) }}
                                </p>
                                <p class="text-[10px] text-white/40">คะแนน</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: บัญชีรายชื่อ (Party List) -->
            <div v-if="activeTab === 'partylist'" class="space-y-6">
                <div class="glass-card">
                    <div class="card-header">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-blue-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            ส.ส. บัญชีรายชื่อ (100 ที่นั่ง)
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <div
                            v-for="(result, index) in partyListSorted"
                            :key="result.party_id"
                            class="party-row"
                            :class="{ 'party-row-leading': index === 0 }"
                        >
                            <div :class="['rank-badge', `rank-${index + 1}`]">{{ index + 1 }}</div>
                            <div
                                class="party-logo"
                                :style="{ backgroundColor: result.party?.color }"
                            >
                                <img
                                    v-if="result.party?.logo"
                                    :src="result.party.logo"
                                    :alt="result.party?.name_th"
                                    class="w-full h-full object-contain rounded-lg"
                                    @error="onLogoError($event, result)"
                                />
                                <span
                                    v-else
                                    class="text-white font-bold text-[10px] leading-none text-center"
                                    >{{ getPartyPlaceholder(result) }}</span
                                >
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm truncate">
                                    {{ result.party?.name_th }}
                                </h4>
                                <div class="party-progress-bar mt-1">
                                    <div
                                        class="party-progress-fill"
                                        :style="{
                                            width: (result.party_list_seats / 100) * 100 + '%',
                                            backgroundColor: result.party?.color,
                                        }"
                                    ></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-xl font-bold"
                                    :style="{ color: result.party?.color }"
                                >
                                    {{ result.party_list_seats || 0 }}
                                </p>
                                <p class="text-[10px] text-white/40">ที่นั่ง</p>
                            </div>
                            <div class="text-right min-w-[90px] hidden sm:block">
                                <p class="text-sm font-semibold">
                                    {{ formatNumber(result.party_list_votes) }}
                                </p>
                                <p class="text-[10px] text-white/40">คะแนน</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: แผนที่ (Map) -->
            <div v-if="activeTab === 'map'" class="space-y-6">
                <div class="glass-card">
                    <div class="card-header">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-orange-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                                />
                            </svg>
                            แผนที่ผลเลือกตั้ง
                        </h2>
                    </div>
                    <div class="p-0">
                        <ThailandMap
                            :election-id="electionId"
                            @province-selected="onProvinceSelected"
                            @constituency-selected="onConstituencySelected"
                        />
                    </div>
                </div>
            </div>

            <!-- Tab: รายจังหวัด (Provinces) -->
            <div v-if="activeTab === 'provinces'" class="space-y-6">
                <!-- Region Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div
                        v-for="region in regionData"
                        :key="region.key"
                        class="region-card"
                        :style="{ '--region-color': region.color }"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-sm">{{ region.name_th }}</h3>
                            <span class="text-xs text-white/40"
                                >{{ region.totalSeats }} ที่นั่ง</span
                            >
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="party in region.topParties?.slice(0, 3)"
                                :key="party.id"
                                class="flex items-center gap-2"
                            >
                                <div
                                    class="w-2.5 h-2.5 rounded-sm"
                                    :style="{ backgroundColor: party.color }"
                                ></div>
                                <span class="text-xs text-white/60 flex-1 truncate">{{
                                    party.abbreviation || party.name_th
                                }}</span>
                                <span class="text-xs font-bold">{{ party.seats }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Province Search -->
                <div class="glass-card p-4">
                    <input
                        v-model="provinceSearch"
                        type="text"
                        placeholder="ค้นหาจังหวัด..."
                        class="search-input"
                    />
                </div>

                <!-- Province Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a
                        v-for="province in filteredProvinces"
                        :key="province.id"
                        :href="`/results/${province.id}`"
                        class="province-card"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-bold text-sm">{{ province.name_th }}</h4>
                            <span class="text-[10px] text-white/30"
                                >{{ province.total_constituencies }} เขต</span
                            >
                        </div>
                        <div v-if="province.parties?.length" class="space-y-1.5">
                            <div
                                v-for="party in province.parties.slice(0, 3)"
                                :key="party.party_id"
                                class="flex items-center gap-2"
                            >
                                <div
                                    class="w-2 h-2 rounded-sm"
                                    :style="{ backgroundColor: party.party?.color }"
                                ></div>
                                <span class="text-xs text-white/60 flex-1 truncate">{{
                                    party.party?.abbreviation
                                }}</span>
                                <span class="text-xs font-semibold"
                                    >{{ party.seats_won }} ที่นั่ง</span
                                >
                                <span class="text-[10px] text-white/30"
                                    >{{ party.vote_percentage }}%</span
                                >
                            </div>
                        </div>
                        <div v-else class="text-xs text-white/20 text-center py-2">รอข้อมูล</div>
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/5 bg-gray-950">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="font-bold mb-3">ThaiVote 2569</h3>
                        <p class="text-sm text-white/40">ระบบรายงานผลเลือกตั้งแบบเรียลไทม์</p>
                        <p class="text-xs text-white/20 mt-2">
                            ข้อมูลอ้างอิงจาก กกต. (ectreport69.ect.go.th)
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sm mb-3">ลิงก์</h4>
                        <ul class="space-y-1.5 text-sm text-white/40">
                            <li>
                                <a href="/live" class="hover:text-white transition">ถ่ายทอดสด</a>
                            </li>
                            <li><a href="/map" class="hover:text-white transition">แผนที่</a></li>
                            <li>
                                <a href="/parties" class="hover:text-white transition"
                                    >พรรคการเมือง</a
                                >
                            </li>
                            <li><a href="/news" class="hover:text-white transition">ข่าวสาร</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sm mb-3">แหล่งข้อมูล</h4>
                        <ul class="space-y-1.5 text-sm text-white/40">
                            <li>
                                <a
                                    href="https://ectreport69.ect.go.th"
                                    target="_blank"
                                    class="hover:text-orange-400 transition"
                                    >ECT Report 69</a
                                >
                            </li>
                            <li>
                                <a
                                    href="https://www.ect.go.th"
                                    target="_blank"
                                    class="hover:text-orange-400 transition"
                                    >สำนักงาน กกต.</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-white/5 mt-8 pt-6 text-center text-white/20 text-xs">
                    <p>&copy; 2569 ThaiVote | ผลการเลือกตั้งอย่างไม่เป็นทางการ</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useResultsStore } from '@/stores/results';
import { parties as partyData, getPartyByNumber } from '@/data/parties';
import ThailandMap from '@/components/map/ThailandMap.vue';
import axios from 'axios';

const page = usePage();
const auth = computed(() => page.props.auth);

const props = defineProps({
    electionId: { type: Number, default: 1 },
});

const resultsStore = useResultsStore();

// State
const resultView = ref('seats');
const activeTab = ref('overview');
const isLive = ref(true);
const lastUpdated = ref(new Date());
const breakingNews = ref([]);
const liveFeed = ref([]);
const provinceSearch = ref('');
const provincesData = ref([]);
const regionData = ref([]);
const settings = ref({});

// Tab Icons as render functions
const OverviewIcon = {
    render: () =>
        h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            }),
        ]),
};
const ConstIcon = {
    render: () =>
        h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
            }),
        ]),
};
const ListIcon = {
    render: () =>
        h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            }),
        ]),
};
const MapIcon = {
    render: () =>
        h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            }),
        ]),
};
const ProvinceIcon = {
    render: () =>
        h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            }),
        ]),
};

const tabs = [
    { key: 'overview', label: 'ภาพรวม', icon: OverviewIcon },
    { key: 'constituency', label: 'ส.ส.แบ่งเขต', icon: ConstIcon },
    { key: 'partylist', label: 'บัญชีรายชื่อ', icon: ListIcon },
    { key: 'map', label: 'แผนที่', icon: MapIcon },
    { key: 'provinces', label: 'รายจังหวัด', icon: ProvinceIcon },
];

// Computed
const siteName = computed(() => settings.value.site_name || 'ThaiVote');
const election = computed(() => resultsStore.election);
const stats = computed(() => resultsStore.stats);

const invalidPercent = computed(() => {
    const s = stats.value;
    if (!s?.invalid_votes || !s?.total_votes_cast) {
        return '0.0';
    }
    return ((s.invalid_votes / s.total_votes_cast) * 100).toFixed(1);
});

const sortedResults = computed(() => {
    const results = [...resultsStore.nationalResults].map((r) => ({
        ...r,
        party: r.party || findPartyFromData(r),
    }));
    return results.sort((a, b) => {
        if (resultView.value === 'seats') {
            return b.total_seats - a.total_seats;
        }
        return b.total_votes - a.total_votes;
    });
});

const constituencySorted = computed(() =>
    [...resultsStore.nationalResults]
        .map((r) => ({ ...r, party: r.party || findPartyFromData(r) }))
        .filter((r) => (r.constituency_seats || 0) > 0)
        .sort((a, b) => (b.constituency_seats || 0) - (a.constituency_seats || 0))
);

const partyListSorted = computed(() =>
    [...resultsStore.nationalResults]
        .map((r) => ({ ...r, party: r.party || findPartyFromData(r) }))
        .filter((r) => (r.party_list_seats || 0) > 0)
        .sort((a, b) => (b.party_list_seats || 0) - (a.party_list_seats || 0))
);

const totalConstituencySeats = computed(() =>
    resultsStore.nationalResults.reduce((sum, r) => sum + (r.constituency_seats || 0), 0)
);

const totalPartyListSeats = computed(() =>
    resultsStore.nationalResults.reduce((sum, r) => sum + (r.party_list_seats || 0), 0)
);

const filteredProvinces = computed(() => {
    if (!provinceSearch.value) {
        return provincesData.value;
    }
    const search = provinceSearch.value.toLowerCase();
    return provincesData.value.filter(
        (p) => p.name_th?.includes(search) || p.name_en?.toLowerCase().includes(search)
    );
});

// Methods
function findPartyFromData(result) {
    if (result.party?.party_number) {
        return getPartyByNumber(result.party.party_number) || result.party;
    }
    return result.party;
}

function getPartyPlaceholder(result) {
    const party = result.party;
    if (!party) {
        return '?';
    }
    const data = getPartyByNumber(party.party_number);
    return data?.logo_placeholder || party.abbreviation || party.name_th?.substring(0, 3) || '?';
}

function onLogoError(event, result) {
    event.target.style.display = 'none';
    event.target.parentElement.innerHTML = `<span class="text-white font-bold text-[10px] leading-none text-center">${getPartyPlaceholder(result)}</span>`;
}

const formatNumber = (num) => {
    if (!num) {
        return '0';
    }
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatTime = (date) => {
    if (!date) {
        return '';
    }
    return new Intl.DateTimeFormat('th-TH', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(date));
};

const onProvinceSelected = (province) => {
    console.log('Province selected:', province);
};

const onConstituencySelected = (constituency) => {
    console.log('Constituency selected:', constituency);
};

const fetchNews = async () => {
    try {
        const response = await axios.get('/api/news/breaking');
        breakingNews.value = response.data;
    } catch {
        // No breaking news available
    }
};

const fetchProvinces = async () => {
    try {
        const response = await axios.get('/api/live');
        if (response.data?.data?.provinces) {
            provincesData.value = Object.values(response.data.data.provinces);
        }
        if (response.data?.data?.regions) {
            regionData.value = Object.entries(response.data.data.regions).map(([key, r]) => ({
                key,
                ...r,
            }));
        }
    } catch {
        // Will load on real-time update
    }
};

// Lifecycle
let echoChannel = null;
let refreshInterval = null;

onMounted(async () => {
    // Load settings
    try {
        const response = await axios.get('/admin/settings/api');
        if (response.data?.data) {
            settings.value = response.data.data;
        }
    } catch {
        settings.value = { site_name: 'ThaiVote' };
    }

    await resultsStore.fetchElection(props.electionId);
    await fetchNews();
    await fetchProvinces();

    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(async () => {
        await resultsStore.fetchElection(props.electionId);
        await fetchProvinces();
        lastUpdated.value = new Date();
    }, 30000);

    // Subscribe to real-time updates
    if (window.Echo) {
        echoChannel = window.Echo.channel(`election.${props.electionId}`);
        echoChannel
            .listen('ResultsUpdated', (event) => {
                resultsStore.updateResults(event.results);
                lastUpdated.value = new Date();
                liveFeed.value.unshift({
                    id: Date.now(),
                    message: event.message || 'อัปเดตผลคะแนน',
                    created_at: new Date(),
                });
                if (liveFeed.value.length > 20) {
                    liveFeed.value.pop();
                }
            })
            .listen('NewsPublished', (event) => {
                breakingNews.value.unshift(event.news);
                breakingNews.value = breakingNews.value.slice(0, 10);
            });
    }
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo?.leave(`election.${props.electionId}`);
    }
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style scoped>
@reference "tailwindcss";

/* Live Banner */
.live-banner {
    @apply bg-red-600/90 backdrop-blur-sm text-white py-2;
    animation: livePulse 2s ease-in-out infinite;
}

.live-dot {
    @apply w-2.5 h-2.5 bg-white rounded-full;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes livePulse {
    0%,
    100% {
        background-color: rgba(220, 38, 38, 0.9);
    }
    50% {
        background-color: rgba(185, 28, 28, 0.9);
    }
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(0.8);
    }
}

/* Header */
.header-gradient {
    background: rgba(3, 7, 18, 0.85);
}

.logo-mark {
    @apply w-10 h-10 rounded-xl flex items-center justify-center;
    background: linear-gradient(135deg, #f97316, #ef4444);
}

/* Hero Section */
.hero-section {
    background: linear-gradient(180deg, rgba(249, 115, 22, 0.05) 0%, transparent 100%);
}

.text-gradient-orange {
    background: linear-gradient(135deg, #f97316, #ef4444);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Stat Cards */
.stat-card {
    @apply bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/5 relative overflow-hidden;
    transition:
        transform 0.2s,
        border-color 0.2s;
}

.stat-card:hover {
    @apply border-white/10;
    transform: translateY(-2px);
}

.stat-card-icon {
    @apply w-10 h-10 rounded-xl flex items-center justify-center mb-2;
}

.stat-card-icon.orange {
    background: linear-gradient(135deg, #f97316, #ef4444);
}
.stat-card-icon.blue {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}
.stat-card-icon.green {
    background: linear-gradient(135deg, #22c55e, #14b8a6);
}
.stat-card-icon.red {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.stat-progress {
    @apply absolute bottom-0 left-0 right-0 h-1 bg-white/5;
}

.stat-progress-bar {
    @apply h-full;
    background: linear-gradient(90deg, #f97316, #ef4444);
    transition: width 1s ease-out;
}

/* Seat Bar */
.seat-bar {
    @apply flex h-8 rounded-full overflow-hidden bg-white/5;
}

.seat-bar-segment {
    @apply h-full transition-all duration-1000;
}

.seat-bar-segment:first-child {
    @apply rounded-l-full;
}

.seat-bar-segment:last-child {
    @apply rounded-r-full;
}

.majority-line {
    @apply absolute top-[-8px];
}

.majority-marker {
    @apply w-0.5 h-5 bg-yellow-400/60 mx-auto;
}

.majority-label {
    @apply text-[10px] text-yellow-400/60 block text-center mt-0.5;
}

/* Glass Card */
.glass-card {
    @apply bg-white/5 backdrop-blur-sm rounded-2xl border border-white/5 overflow-hidden;
}

.card-header {
    @apply flex items-center justify-between px-4 py-3 border-b border-white/5;
}

/* Tab Navigation */
.tab-nav {
    @apply flex gap-1 bg-white/5 rounded-xl p-1 overflow-x-auto;
}

.tab-btn {
    @apply flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-white/50 whitespace-nowrap transition-all;
}

.tab-btn:hover {
    @apply text-white/80 bg-white/5;
}

.tab-btn.active {
    @apply text-white;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(239, 68, 68, 0.2));
    border: 1px solid rgba(249, 115, 22, 0.3);
}

/* Toggle Button */
.toggle-btn {
    @apply px-3 py-1 rounded-lg text-xs font-medium text-white/40 transition-all;
}

.toggle-btn:hover {
    @apply text-white/60;
}

.toggle-btn.active {
    @apply text-white;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.3), rgba(239, 68, 68, 0.3));
}

/* Party Row */
.party-row {
    @apply flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all;
}

.party-row:hover {
    @apply bg-white/5;
}

.party-row-leading {
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.08), rgba(239, 68, 68, 0.05));
    border: 1px solid rgba(249, 115, 22, 0.15);
    @apply rounded-xl;
}

/* Rank Badge */
.rank-badge {
    @apply w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.4);
}

.rank-1 {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
}
.rank-2 {
    background: linear-gradient(135deg, #9ca3af, #6b7280);
    color: #fff;
}
.rank-3 {
    background: linear-gradient(135deg, #cd7f32, #a0522d);
    color: #fff;
}

/* Party Logo */
.party-logo {
    @apply w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden;
}

/* Party Progress Bar */
.party-progress-bar {
    @apply w-full h-1.5 bg-white/5 rounded-full overflow-hidden;
}

.party-progress-fill {
    @apply h-full rounded-full transition-all duration-1000;
}

/* Badges */
.badge-majority {
    @apply px-2 py-0.5 bg-green-500/20 text-green-400 rounded-full text-[10px] font-bold;
}

.badge-leading {
    @apply px-2 py-0.5 bg-orange-500/20 text-orange-400 rounded-full text-[10px] font-bold;
}

/* Type Circles */
.type-circle {
    @apply w-20 h-20 rounded-full flex items-center justify-center;
}

.type-circle.constituency {
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(234, 88, 12, 0.2));
    border: 2px solid rgba(249, 115, 22, 0.3);
}

.type-circle.partylist {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.2));
    border: 2px solid rgba(59, 130, 246, 0.3);
}

/* Region Card */
.region-card {
    @apply bg-white/5 rounded-xl p-4 border-l-4 border border-white/5;
    border-left-color: var(--region-color);
}

/* Search */
.search-input {
    @apply w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/30 outline-none;
    transition: border-color 0.2s;
}

.search-input:focus {
    border-color: rgba(249, 115, 22, 0.5);
}

/* Province Card */
.province-card {
    @apply bg-white/5 rounded-xl p-4 border border-white/5 transition-all block;
}

.province-card:hover {
    @apply bg-white/8 border-white/10;
    transform: translateY(-2px);
}

/* Glass Button */
.btn-glass {
    @apply px-4 py-1.5 rounded-lg text-sm font-medium border border-white/10 bg-white/5 transition-all;
}

.btn-glass:hover {
    @apply bg-white/10;
}

/* Animation */
.party-list-move,
.party-list-enter-active,
.party-list-leave-active {
    transition: all 0.5s ease;
}

.party-list-enter-from {
    opacity: 0;
    transform: translateX(30px);
}

.party-list-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}
</style>
