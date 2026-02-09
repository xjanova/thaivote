<template>
    <MainLayout>
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">ตรวจสอบรายงานผลเลือกตั้ง แบบ 69</h1>
                <p class="text-gray-500 mt-2">
                    ข้อมูลจาก ectreport69.ect.go.th | {{ ectData.election_name }} |
                    {{ ectData.election_date }}
                </p>
                <p class="text-sm text-gray-400 mt-1">
                    รายงาน ณ เวลา {{ formatDateTime(ectData.report_time) }} | นับแล้ว
                    {{ ectData.counted_constituencies }}/{{ ectData.total_constituencies }} เขต
                </p>
            </div>

            <!-- National Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-xs text-gray-500 mb-1">ผู้มีสิทธิเลือกตั้ง</p>
                    <p class="text-xl font-bold text-gray-900">
                        {{ fmt(ectData.national_summary.eligible_voters) }}
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-xs text-gray-500 mb-1">ผู้มาใช้สิทธิ</p>
                    <p class="text-xl font-bold text-blue-600">
                        {{ fmt(ectData.national_summary.total_voters) }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{
                            pct(
                                ectData.national_summary.total_voters,
                                ectData.national_summary.eligible_voters
                            )
                        }}%
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-xs text-gray-500 mb-1">บัตรดี</p>
                    <p class="text-xl font-bold text-green-600">
                        {{ fmt(ectData.national_summary.good_ballots) }}
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-xs text-gray-500 mb-1">บัตรเสีย</p>
                    <p class="text-xl font-bold text-red-500">
                        {{ fmt(ectData.national_summary.bad_ballots) }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{
                            pct(
                                ectData.national_summary.bad_ballots,
                                ectData.national_summary.total_voters
                            )
                        }}%
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-xs text-gray-500 mb-1">ไม่ประสงค์ลงคะแนน</p>
                    <p class="text-xl font-bold text-orange-500">
                        {{ fmt(ectData.national_summary.no_vote) }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{
                            pct(
                                ectData.national_summary.no_vote,
                                ectData.national_summary.good_ballots
                            )
                        }}%
                    </p>
                </div>
            </div>

            <!-- Integrity Score & AI Analysis Button -->
            <div class="flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-1 rounded-xl shadow-sm border p-6" :class="scoreColorClass">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium opacity-80">
                                คะแนนความน่าเชื่อถือของข้อมูล
                            </p>
                            <p class="text-4xl font-bold mt-2">
                                {{ anomalyResult?.summary?.integrity_score ?? '...' }}/100
                            </p>
                        </div>
                        <div class="text-6xl opacity-30">
                            <span v-if="(anomalyResult?.summary?.integrity_score ?? 100) >= 80"
                                >&#10003;</span
                            >
                            <span v-else-if="(anomalyResult?.summary?.integrity_score ?? 100) >= 50"
                                >&#9888;</span
                            >
                            <span v-else>&#10007;</span>
                        </div>
                    </div>
                    <div class="mt-3 text-sm opacity-70">
                        พบความผิดปกติ {{ anomalyResult?.summary?.total_anomalies ?? 0 }} รายการ ใน
                        {{ anomalyResult?.summary?.provinces_with_anomalies ?? 0 }} จังหวัด
                    </div>
                </div>

                <button
                    class="px-8 py-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition flex items-center justify-center gap-3 font-medium"
                    :disabled="analyzing"
                    @click="runAnalysis"
                >
                    <svg
                        v-if="analyzing"
                        class="animate-spin h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                        />
                    </svg>
                    {{ analyzing ? 'กำลังวิเคราะห์...' : 'AI วิเคราะห์ผลคะแนน' }}
                </button>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex gap-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        :class="[
                            'pb-3 text-sm font-medium border-b-2 transition-colors',
                            activeTab === tab.id
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.name }}
                        <span
                            v-if="tab.badge"
                            class="ml-1 px-2 py-0.5 text-xs rounded-full"
                            :class="tab.badgeClass"
                        >
                            {{ tab.badge }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Tab: Province Table -->
            <div v-if="activeTab === 'provinces'" class="space-y-4">
                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-4 mb-4">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="ค้นหาจังหวัด..."
                        class="flex-1 px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <select
                        v-model="regionFilter"
                        class="px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">ทุกภาค</option>
                        <option value="north">ภาคเหนือ</option>
                        <option value="northeast">ภาคตะวันออกเฉียงเหนือ</option>
                        <option value="central">ภาคกลาง</option>
                        <option value="east">ภาคตะวันออก</option>
                        <option value="west">ภาคตะวันตก</option>
                        <option value="south">ภาคใต้</option>
                    </select>
                    <select
                        v-model="anomalyFilter"
                        class="px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">ทั้งหมด</option>
                        <option value="has_anomaly">เฉพาะจังหวัดที่มีความผิดปกติ</option>
                        <option value="no_anomaly">เฉพาะจังหวัดปกติ</option>
                    </select>
                </div>

                <!-- Province Summary Table -->
                <div class="overflow-x-auto bg-white rounded-xl shadow-sm border">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left font-medium text-gray-500 cursor-pointer hover:text-gray-700"
                                    @click="sortBy('name_th')"
                                >
                                    จังหวัด {{ sortIcon('name_th') }}
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">ภาค</th>
                                <th
                                    class="px-4 py-3 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700"
                                    @click="sortBy('total_constituencies')"
                                >
                                    เขต {{ sortIcon('total_constituencies') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700"
                                    @click="sortBy('eligible_voters')"
                                >
                                    ผู้มีสิทธิ {{ sortIcon('eligible_voters') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700"
                                    @click="sortBy('total_voters')"
                                >
                                    ผู้มาใช้สิทธิ {{ sortIcon('total_voters') }}
                                </th>
                                <th
                                    class="px-4 py-3 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700"
                                    @click="sortBy('turnout')"
                                >
                                    อัตราใช้สิทธิ {{ sortIcon('turnout') }}
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">
                                    บัตรดี
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">
                                    บัตรเสีย
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">
                                    ไม่ประสงค์
                                </th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500">
                                    สถานะ
                                </th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="province in filteredProvinces"
                                :key="province.id"
                                :class="{ 'bg-red-50': provinceHasAnomaly(province.name_th) }"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ province.name_th }}
                                    <span class="text-gray-400 text-xs ml-1">{{
                                        province.name_en
                                    }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full"
                                        :class="regionClass(province.region)"
                                    >
                                        {{ regionName(province.region) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ province.total_constituencies }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ fmt(province.summary.eligible_voters) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ fmt(province.summary.total_voters) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span
                                        :class="
                                            turnoutColor(
                                                province.summary.total_voters,
                                                province.summary.eligible_voters
                                            )
                                        "
                                    >
                                        {{
                                            pct(
                                                province.summary.total_voters,
                                                province.summary.eligible_voters
                                            )
                                        }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-green-600">
                                    {{ fmt(province.summary.good_ballots) }}
                                </td>
                                <td class="px-4 py-3 text-right text-red-500">
                                    {{ fmt(province.summary.bad_ballots) }}
                                </td>
                                <td class="px-4 py-3 text-right text-orange-500">
                                    {{ fmt(province.summary.no_vote) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="provinceHasAnomaly(province.name_th)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700"
                                    >
                                        &#9888; พบ
                                        {{ getProvinceAnomalyCount(province.name_th) }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700"
                                    >
                                        &#10003; ปกติ
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                        @click="toggleProvinceDetail(province.id)"
                                    >
                                        {{ expandedProvince === province.id ? 'ซ่อน' : 'ดูรายเขต' }}
                                    </button>
                                </td>
                            </tr>
                            <!-- Expanded constituency detail -->
                            <template v-if="expandedProvince">
                                <tr
                                    v-for="province in filteredProvinces.filter(
                                        (p) => p.id === expandedProvince
                                    )"
                                    :key="'detail-' + province.id"
                                >
                                    <td colspan="11" class="px-0 py-0">
                                        <div class="bg-gray-50 border-t border-b">
                                            <div class="px-6 py-4">
                                                <h3 class="font-bold text-gray-800 mb-3">
                                                    รายละเอียดเขตเลือกตั้ง - {{ province.name_th }}
                                                </h3>
                                                <div class="overflow-x-auto">
                                                    <table
                                                        class="min-w-full divide-y divide-gray-200 text-xs"
                                                    >
                                                        <thead class="bg-gray-100">
                                                            <tr>
                                                                <th
                                                                    class="px-3 py-2 text-left font-medium text-gray-500"
                                                                >
                                                                    เขต
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    หน่วย
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    ผู้มีสิทธิ
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    ผู้มาใช้สิทธิ
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    % ใช้สิทธิ
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    บัตรดี
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    บัตรเสีย
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-right font-medium text-gray-500"
                                                                >
                                                                    ไม่ประสงค์
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-left font-medium text-gray-500"
                                                                >
                                                                    ผู้สมัคร (คะแนน)
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-center font-medium text-gray-500"
                                                                >
                                                                    ตรวจสอบ
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="divide-y divide-gray-200 bg-white"
                                                        >
                                                            <tr
                                                                v-for="c in province.constituencies"
                                                                :key="c.number"
                                                                :class="{
                                                                    'bg-red-50':
                                                                        constituencyHasAnomaly(
                                                                            province.name_th,
                                                                            c.number
                                                                        ),
                                                                }"
                                                            >
                                                                <td class="px-3 py-2 font-medium">
                                                                    เขต {{ c.number }}
                                                                </td>
                                                                <td class="px-3 py-2 text-right">
                                                                    {{ c.counted_stations }}/{{
                                                                        c.total_stations
                                                                    }}
                                                                </td>
                                                                <td class="px-3 py-2 text-right">
                                                                    {{ fmt(c.eligible_voters) }}
                                                                </td>
                                                                <td class="px-3 py-2 text-right">
                                                                    {{ fmt(c.total_voters) }}
                                                                </td>
                                                                <td class="px-3 py-2 text-right">
                                                                    <span
                                                                        :class="
                                                                            turnoutColor(
                                                                                c.total_voters,
                                                                                c.eligible_voters
                                                                            )
                                                                        "
                                                                    >
                                                                        {{
                                                                            pct(
                                                                                c.total_voters,
                                                                                c.eligible_voters
                                                                            )
                                                                        }}%
                                                                    </span>
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2 text-right text-green-600"
                                                                >
                                                                    {{ fmt(c.good_ballots) }}
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2 text-right text-red-500"
                                                                >
                                                                    {{ fmt(c.bad_ballots) }}
                                                                    <span
                                                                        v-if="
                                                                            pctNum(
                                                                                c.bad_ballots,
                                                                                c.total_voters
                                                                            ) > 10
                                                                        "
                                                                        class="text-red-700 font-bold"
                                                                    >
                                                                        ({{
                                                                            pct(
                                                                                c.bad_ballots,
                                                                                c.total_voters
                                                                            )
                                                                        }}%)
                                                                    </span>
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2 text-right text-orange-500"
                                                                >
                                                                    {{ fmt(c.no_vote) }}
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <div
                                                                        v-for="cand in c.candidates"
                                                                        :key="cand.number"
                                                                        class="flex items-center gap-2 mb-1"
                                                                    >
                                                                        <span
                                                                            class="w-2 h-2 rounded-full"
                                                                            :style="{
                                                                                backgroundColor:
                                                                                    cand.party_color,
                                                                            }"
                                                                        />
                                                                        <span
                                                                            :class="{
                                                                                'font-bold':
                                                                                    cand.is_winner,
                                                                            }"
                                                                        >
                                                                            {{ cand.name }}
                                                                            <span
                                                                                class="text-gray-400"
                                                                                >({{
                                                                                    cand.party
                                                                                }})</span
                                                                            >
                                                                        </span>
                                                                        <span
                                                                            class="ml-auto font-mono"
                                                                            :class="{
                                                                                'text-red-600 font-bold':
                                                                                    cand.votes ===
                                                                                        0 ||
                                                                                    cand.votes >
                                                                                        c.good_ballots,
                                                                            }"
                                                                        >
                                                                            {{ fmt(cand.votes) }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-3 py-2 text-center">
                                                                    <span
                                                                        v-if="
                                                                            constituencyHasAnomaly(
                                                                                province.name_th,
                                                                                c.number
                                                                            )
                                                                        "
                                                                        class="text-red-600"
                                                                        title="พบความผิดปกติ"
                                                                    >
                                                                        &#9888;
                                                                    </span>
                                                                    <span
                                                                        v-else
                                                                        class="text-green-500"
                                                                        title="ปกติ"
                                                                    >
                                                                        &#10003;
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Anomalies -->
            <div v-if="activeTab === 'anomalies'" class="space-y-6">
                <!-- Anomaly Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <p class="text-xs text-red-600 font-medium">วิกฤต (Critical)</p>
                        <p class="text-3xl font-bold text-red-700">
                            {{ anomalyResult?.summary?.critical_count ?? 0 }}
                        </p>
                        <p class="text-xs text-red-400 mt-1">ต้องตรวจสอบทันที</p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <p class="text-xs text-yellow-600 font-medium">เตือน (Warning)</p>
                        <p class="text-3xl font-bold text-yellow-700">
                            {{ anomalyResult?.summary?.warning_count ?? 0 }}
                        </p>
                        <p class="text-xs text-yellow-500 mt-1">ควรตรวจสอบเพิ่มเติม</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <p class="text-xs text-blue-600 font-medium">ข้อมูล (Info)</p>
                        <p class="text-3xl font-bold text-blue-700">
                            {{ anomalyResult?.summary?.info_count ?? 0 }}
                        </p>
                        <p class="text-xs text-blue-400 mt-1">เพื่อทราบ</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <p class="text-xs text-gray-600 font-medium">จังหวัดที่พบ</p>
                        <p class="text-3xl font-bold text-gray-700">
                            {{ anomalyResult?.summary?.provinces_with_anomalies ?? 0 }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            จาก {{ ectData.provinces.length }} จังหวัด
                        </p>
                    </div>
                </div>

                <!-- Anomaly Type Filter -->
                <div class="flex flex-wrap gap-2">
                    <button
                        :class="[
                            'px-3 py-1.5 text-xs rounded-full border transition',
                            anomalyTypeFilter === ''
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
                        ]"
                        @click="anomalyTypeFilter = ''"
                    >
                        ทั้งหมด
                    </button>
                    <button
                        v-for="type in anomalyTypes"
                        :key="type.id"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-full border transition',
                            anomalyTypeFilter === type.id
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
                        ]"
                        @click="anomalyTypeFilter = type.id"
                    >
                        {{ type.name }}
                    </button>
                </div>

                <!-- Anomaly List -->
                <div class="overflow-x-auto bg-white rounded-xl shadow-sm border">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">ระดับ</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">
                                    สถานที่
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">
                                    ประเภท
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">
                                    รายละเอียด
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="(anomaly, idx) in filteredAnomalies"
                                :key="idx"
                                :class="severityRowClass(anomaly.severity)"
                            >
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full font-medium"
                                        :class="severityBadgeClass(anomaly.severity)"
                                    >
                                        {{ severityLabel(anomaly.severity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ anomaly.location }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ anomalyTypeName(anomaly.type) }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ anomaly.message }}
                                </td>
                            </tr>
                            <tr v-if="filteredAnomalies.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                    {{
                                        anomalyResult
                                            ? 'ไม่พบความผิดปกติในประเภทที่เลือก'
                                            : 'กด "AI วิเคราะห์ผลคะแนน" เพื่อเริ่มตรวจสอบ'
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: AI Analysis -->
            <div v-if="activeTab === 'analysis'" class="space-y-6">
                <div
                    v-if="!aiAnalysis"
                    class="text-center py-16 bg-white rounded-xl shadow-sm border"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                        />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">ยังไม่ได้วิเคราะห์</h3>
                    <p class="mt-2 text-gray-500">
                        กดปุ่ม "AI วิเคราะห์ผลคะแนน" ด้านบนเพื่อเริ่มวิเคราะห์
                    </p>
                </div>

                <div v-else class="space-y-6">
                    <!-- Party Votes Summary -->
                    <div
                        v-if="aiAnalysis.party_votes?.length"
                        class="bg-white rounded-xl shadow-sm border p-6"
                    >
                        <h3 class="font-bold text-gray-800 mb-4">ผลคะแนนรายพรรค</h3>
                        <div class="space-y-3">
                            <div
                                v-for="(pv, idx) in aiAnalysis.party_votes.slice(0, 10)"
                                :key="idx"
                                class="flex items-center gap-3"
                            >
                                <span class="text-gray-400 w-6 text-right">{{ idx + 1 }}.</span>
                                <span
                                    class="w-3 h-3 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: pv.party_color }"
                                />
                                <span class="font-medium text-gray-800 w-40">{{ pv.party }}</span>
                                <div class="flex-1">
                                    <div class="h-6 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :style="{
                                                width:
                                                    pctNum(
                                                        pv.total_votes,
                                                        aiAnalysis.party_votes[0].total_votes
                                                    ) + '%',
                                                backgroundColor: pv.party_color,
                                            }"
                                        />
                                    </div>
                                </div>
                                <span class="font-mono text-sm text-gray-700 w-28 text-right">
                                    {{ fmt(pv.total_votes) }}
                                </span>
                                <span class="text-sm text-gray-500 w-16 text-right">
                                    {{ pv.constituencies_won }} เขต
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Analysis Sections -->
                    <div
                        v-for="section in aiAnalysis.sections"
                        :key="section.title"
                        class="bg-white rounded-xl shadow-sm border p-6"
                    >
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                            <span v-if="section.icon === 'alert'" class="text-red-500"
                                >&#9888;</span
                            >
                            <span v-else-if="section.icon === 'chart'" class="text-blue-500"
                                >&#128202;</span
                            >
                            <span v-else-if="section.icon === 'party'" class="text-purple-500"
                                >&#127942;</span
                            >
                            <span v-else-if="section.icon === 'recommend'" class="text-green-500"
                                >&#128161;</span
                            >
                            <span v-else-if="section.icon === 'region'" class="text-orange-500"
                                >&#127759;</span
                            >
                            {{ section.title }}
                        </h3>
                        <div class="text-gray-700 whitespace-pre-line leading-relaxed text-sm">
                            {{ section.content }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Party Results -->
            <div v-if="activeTab === 'party'" class="space-y-4">
                <div class="overflow-x-auto bg-white rounded-xl shadow-sm border">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">#</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">พรรค</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">
                                    คะแนนรวม
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">
                                    % ของบัตรดี
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">
                                    ชนะเขต
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="(pv, idx) in partyResults"
                                :key="idx"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-3 text-gray-500">{{ idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-3 h-3 rounded-full"
                                            :style="{ backgroundColor: pv.party_color }"
                                        />
                                        <span class="font-medium text-gray-900">{{
                                            pv.party
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    {{ fmt(pv.total_votes) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{
                                        pct(pv.total_votes, ectData.national_summary.good_ballots)
                                    }}%
                                </td>
                                <td class="px-4 py-3 text-right font-bold">
                                    {{ pv.constituencies_won }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { ectReport69Data } from '@/data/ectReport69Data.js';
import axios from 'axios';

// Data
const ectData = ref(ectReport69Data);
const activeTab = ref('provinces');
const searchQuery = ref('');
const regionFilter = ref('');
const anomalyFilter = ref('');
const anomalyTypeFilter = ref('');
const expandedProvince = ref(null);
const analyzing = ref(false);
const anomalyResult = ref(null);
const aiAnalysis = ref(null);
const sortField = ref('');
const sortDirection = ref('asc');

// Anomaly type definitions
const anomalyTypes = [
    { id: 'voters_exceed_eligible', name: 'ผู้ใช้สิทธิ > ผู้มีสิทธิ' },
    { id: 'ballot_sum_mismatch', name: 'บัตรรวมไม่ตรง' },
    { id: 'vote_sum_mismatch', name: 'คะแนนรวมไม่ตรง' },
    { id: 'winner_exceeds_good_ballots', name: 'ผู้ชนะ > บัตรดี' },
    { id: 'abnormal_high_turnout', name: 'ใช้สิทธิสูงผิดปกติ' },
    { id: 'abnormal_low_turnout', name: 'ใช้สิทธิต่ำผิดปกติ' },
    { id: 'abnormal_bad_ballot_rate', name: 'บัตรเสียสูง' },
    { id: 'zero_votes_candidate', name: 'ได้ 0 คะแนน' },
    { id: 'abnormal_winner_percentage', name: 'ชนะสูงผิดปกติ' },
];

const tabs = computed(() => [
    { id: 'provinces', name: 'ข้อมูลรายจังหวัด (77 จังหวัด)', badge: null },
    {
        id: 'anomalies',
        name: 'ความผิดปกติที่พบ',
        badge: anomalyResult.value?.summary?.total_anomalies ?? null,
        badgeClass:
            (anomalyResult.value?.summary?.critical_count ?? 0) > 0
                ? 'bg-red-100 text-red-700'
                : 'bg-yellow-100 text-yellow-700',
    },
    { id: 'analysis', name: 'AI วิเคราะห์', badge: null },
    { id: 'party', name: 'ผลรายพรรค', badge: null },
]);

// Region helpers
const regionNames = {
    north: 'เหนือ',
    northeast: 'อีสาน',
    central: 'กลาง',
    east: 'ตะวันออก',
    west: 'ตะวันตก',
    south: 'ใต้',
};

const regionClasses = {
    north: 'bg-green-100 text-green-700',
    northeast: 'bg-red-100 text-red-700',
    central: 'bg-yellow-100 text-yellow-700',
    east: 'bg-blue-100 text-blue-700',
    west: 'bg-purple-100 text-purple-700',
    south: 'bg-pink-100 text-pink-700',
};

const regionName = (r) => regionNames[r] || r;
const regionClass = (r) => regionClasses[r] || 'bg-gray-100 text-gray-700';

// Format helpers
const fmt = (n) => (n != null ? Number(n).toLocaleString() : '0');
const pctNum = (a, b) => (b > 0 ? Number(((a / b) * 100).toFixed(2)) : 0);
const pct = (a, b) => pctNum(a, b).toFixed(2);
const formatDateTime = (dt) => {
    if (!dt) {
        return '-';
    }
    try {
        return new Date(dt).toLocaleString('th-TH');
    } catch {
        return dt;
    }
};

const turnoutColor = (voters, eligible) => {
    const p = pctNum(voters, eligible);
    if (p > 100) {
        return 'text-red-600 font-bold';
    }
    if (p > 95) {
        return 'text-red-500 font-medium';
    }
    if (p < 30) {
        return 'text-orange-500 font-medium';
    }
    return 'text-gray-700';
};

// Score color
const scoreColorClass = computed(() => {
    const score = anomalyResult.value?.summary?.integrity_score ?? 100;
    if (score >= 80) {
        return 'bg-green-50 border-green-200 text-green-800';
    }
    if (score >= 50) {
        return 'bg-yellow-50 border-yellow-200 text-yellow-800';
    }
    return 'bg-red-50 border-red-200 text-red-800';
});

// Sorting
const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const sortIcon = (field) => {
    if (sortField.value !== field) {
        return '';
    }
    return sortDirection.value === 'asc' ? ' \u25B2' : ' \u25BC';
};

// Filtered provinces
const filteredProvinces = computed(() => {
    let provinces = [...ectData.value.provinces];

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        provinces = provinces.filter(
            (p) => p.name_th.includes(q) || p.name_en.toLowerCase().includes(q)
        );
    }

    if (regionFilter.value) {
        provinces = provinces.filter((p) => p.region === regionFilter.value);
    }

    if (anomalyFilter.value === 'has_anomaly') {
        provinces = provinces.filter((p) => provinceHasAnomaly(p.name_th));
    } else if (anomalyFilter.value === 'no_anomaly') {
        provinces = provinces.filter((p) => !provinceHasAnomaly(p.name_th));
    }

    if (sortField.value) {
        provinces.sort((a, b) => {
            let va, vb;
            if (sortField.value === 'name_th') {
                va = a.name_th;
                vb = b.name_th;
            } else if (sortField.value === 'eligible_voters') {
                va = a.summary.eligible_voters;
                vb = b.summary.eligible_voters;
            } else if (sortField.value === 'total_voters') {
                va = a.summary.total_voters;
                vb = b.summary.total_voters;
            } else if (sortField.value === 'turnout') {
                va = pctNum(a.summary.total_voters, a.summary.eligible_voters);
                vb = pctNum(b.summary.total_voters, b.summary.eligible_voters);
            } else if (sortField.value === 'total_constituencies') {
                va = a.total_constituencies;
                vb = b.total_constituencies;
            } else {
                va = a[sortField.value];
                vb = b[sortField.value];
            }
            if (va < vb) {
                return sortDirection.value === 'asc' ? -1 : 1;
            }
            if (va > vb) {
                return sortDirection.value === 'asc' ? 1 : -1;
            }
            return 0;
        });
    }

    return provinces;
});

// Anomaly helpers
const provinceAnomalyMap = computed(() => {
    const map = {};
    if (!anomalyResult.value?.anomalies) {
        return map;
    }
    for (const a of anomalyResult.value.anomalies) {
        if (!map[a.province]) {
            map[a.province] = [];
        }
        map[a.province].push(a);
    }
    return map;
});

const provinceHasAnomaly = (name) => !!provinceAnomalyMap.value[name]?.length;
const getProvinceAnomalyCount = (name) => provinceAnomalyMap.value[name]?.length ?? 0;

const constituencyHasAnomaly = (province, constNum) => {
    const list = provinceAnomalyMap.value[province] || [];
    return list.some((a) => a.constituency === constNum);
};

const filteredAnomalies = computed(() => {
    const all = anomalyResult.value?.anomalies ?? [];
    if (!anomalyTypeFilter.value) {
        return all;
    }
    return all.filter((a) => a.type === anomalyTypeFilter.value);
});

// Severity helpers
const severityLabel = (s) => {
    const labels = { critical: 'วิกฤต', warning: 'เตือน', info: 'ข้อมูล' };
    return labels[s] || s;
};

const severityBadgeClass = (s) => {
    const classes = {
        critical: 'bg-red-100 text-red-700',
        warning: 'bg-yellow-100 text-yellow-700',
        info: 'bg-blue-100 text-blue-700',
    };
    return classes[s] || 'bg-gray-100 text-gray-700';
};

const severityRowClass = (s) => {
    const classes = {
        critical: 'bg-red-50',
        warning: 'bg-yellow-50',
        info: '',
    };
    return classes[s] || '';
};

const anomalyTypeName = (type) => {
    const found = anomalyTypes.find((t) => t.id === type);
    return found ? found.name : type;
};

// Party results (computed from data)
const partyResults = computed(() => {
    const map = {};
    for (const province of ectData.value.provinces) {
        for (const c of province.constituencies) {
            for (const cand of c.candidates) {
                if (!map[cand.party]) {
                    map[cand.party] = {
                        party: cand.party,
                        party_color: cand.party_color,
                        total_votes: 0,
                        constituencies_won: 0,
                    };
                }
                map[cand.party].total_votes += cand.votes;
                if (cand.is_winner) {
                    map[cand.party].constituencies_won++;
                }
            }
        }
    }
    return Object.values(map).sort((a, b) => b.total_votes - a.total_votes);
});

// Toggle province detail
const toggleProvinceDetail = (id) => {
    expandedProvince.value = expandedProvince.value === id ? null : id;
};

// Run AI analysis
const runAnalysis = async () => {
    analyzing.value = true;
    try {
        const response = await axios.post('/api/ect69/analyze', {
            provinces: ectData.value.provinces,
            national_summary: ectData.value.national_summary,
        });
        anomalyResult.value = response.data.anomalies;
        anomalyResult.value.summary.integrity_score = response.data.ai_analysis.overall_score;
        aiAnalysis.value = response.data.ai_analysis;
    } catch {
        // Fallback: run client-side analysis
        runClientSideAnalysis();
    }
    analyzing.value = false;
};

// Client-side fallback analysis
const runClientSideAnalysis = () => {
    const anomalies = [];
    let critical = 0;
    let warning = 0;
    let info = 0;
    const typeCounts = {};
    const provincesSet = new Set();

    for (const province of ectData.value.provinces) {
        for (const c of province.constituencies) {
            const checks = runConstituencyChecks(province.name_th, c);
            for (const check of checks) {
                anomalies.push(check);
                provincesSet.add(check.province);
                if (check.severity === 'critical') {
                    critical++;
                }
                if (check.severity === 'warning') {
                    warning++;
                }
                if (check.severity === 'info') {
                    info++;
                }
                typeCounts[check.type] = (typeCounts[check.type] || 0) + 1;
            }
        }
    }

    const score = Math.max(0, Math.min(100, 100 - critical * 15 - warning * 5 - info));

    anomalyResult.value = {
        summary: {
            total_anomalies: anomalies.length,
            critical_count: critical,
            warning_count: warning,
            info_count: info,
            provinces_with_anomalies: provincesSet.size,
            anomaly_types: typeCounts,
            integrity_score: score,
        },
        anomalies,
    };

    // Build AI analysis client-side
    aiAnalysis.value = buildClientAIAnalysis(anomalies, score);
};

const runConstituencyChecks = (provinceName, c) => {
    const results = [];
    const loc = `${provinceName} เขต ${c.number}`;
    const candidateSum = c.candidates.reduce((s, x) => s + x.votes, 0);
    const ballotSum = c.good_ballots + c.bad_ballots;

    if (c.total_voters > c.eligible_voters && c.eligible_voters > 0) {
        results.push({
            type: 'voters_exceed_eligible',
            severity: 'critical',
            location: loc,
            province: provinceName,
            constituency: c.number,
            message: `ผู้มาใช้สิทธิ (${fmt(c.total_voters)}) มากกว่าผู้มีสิทธิเลือกตั้ง (${fmt(c.eligible_voters)})`,
        });
    }
    if (ballotSum !== c.total_voters && c.total_voters > 0) {
        const sev =
            Math.abs(ballotSum - c.total_voters) > c.total_voters * 0.01 ? 'critical' : 'warning';
        results.push({
            type: 'ballot_sum_mismatch',
            severity: sev,
            location: loc,
            province: provinceName,
            constituency: c.number,
            message: `บัตรดี (${fmt(c.good_ballots)}) + บัตรเสีย (${fmt(c.bad_ballots)}) = ${fmt(ballotSum)} \u2260 ผู้มาใช้สิทธิ (${fmt(c.total_voters)})`,
        });
    }
    if (candidateSum + c.no_vote !== c.good_ballots && c.good_ballots > 0) {
        const total = candidateSum + c.no_vote;
        const sev =
            Math.abs(total - c.good_ballots) > c.good_ballots * 0.01 ? 'critical' : 'warning';
        results.push({
            type: 'vote_sum_mismatch',
            severity: sev,
            location: loc,
            province: provinceName,
            constituency: c.number,
            message: `คะแนนรวม (${fmt(candidateSum)}) + ไม่ประสงค์ (${fmt(c.no_vote)}) = ${fmt(total)} \u2260 บัตรดี (${fmt(c.good_ballots)})`,
        });
    }
    const winner = [...c.candidates].sort((a, b) => b.votes - a.votes)[0];
    if (winner && winner.votes > c.good_ballots && c.good_ballots > 0) {
        results.push({
            type: 'winner_exceeds_good_ballots',
            severity: 'critical',
            location: loc,
            province: provinceName,
            constituency: c.number,
            message: `คะแนนผู้ชนะ ${winner.name} (${fmt(winner.votes)}) มากกว่าบัตรดี (${fmt(c.good_ballots)})`,
        });
    }
    if (c.eligible_voters > 0) {
        const turnout = (c.total_voters / c.eligible_voters) * 100;
        if (turnout > 95) {
            results.push({
                type: 'abnormal_high_turnout',
                severity: turnout > 100 ? 'critical' : 'warning',
                location: loc,
                province: provinceName,
                constituency: c.number,
                message: `อัตราผู้มาใช้สิทธิสูงผิดปกติ ${turnout.toFixed(1)}%`,
            });
        }
        if (turnout < 30 && turnout > 0) {
            results.push({
                type: 'abnormal_low_turnout',
                severity: 'warning',
                location: loc,
                province: provinceName,
                constituency: c.number,
                message: `อัตราผู้มาใช้สิทธิต่ำผิดปกติ ${turnout.toFixed(1)}%`,
            });
        }
    }
    if (c.total_voters > 0) {
        const badRate = (c.bad_ballots / c.total_voters) * 100;
        if (badRate > 10) {
            results.push({
                type: 'abnormal_bad_ballot_rate',
                severity: 'warning',
                location: loc,
                province: provinceName,
                constituency: c.number,
                message: `อัตราบัตรเสียสูงผิดปกติ ${badRate.toFixed(1)}%`,
            });
        }
    }
    for (const cand of c.candidates) {
        if (cand.votes === 0 && c.total_voters > 100) {
            results.push({
                type: 'zero_votes_candidate',
                severity: 'info',
                location: loc,
                province: provinceName,
                constituency: c.number,
                message: `ผู้สมัคร ${cand.name} (${cand.party}) ได้ 0 คะแนน`,
            });
        }
    }
    return results;
};

const buildClientAIAnalysis = (anomalies, score) => {
    const ns = ectData.value.national_summary;
    const turnout = pct(ns.total_voters, ns.eligible_voters);
    const badRate = pct(ns.bad_ballots, ns.total_voters);
    const criticals = anomalies.filter((a) => a.severity === 'critical');
    const criticalProvinces = [...new Set(criticals.map((a) => a.province))];

    return {
        overall_score: score,
        turnout_percent: turnout,
        bad_ballot_percent: badRate,
        party_votes: partyResults.value,
        sections: [
            {
                title: 'ภาพรวมการเลือกตั้ง',
                icon: 'chart',
                content: `ผู้มีสิทธิเลือกตั้งทั้งหมด ${fmt(ns.eligible_voters)} คน มีผู้มาใช้สิทธิ ${fmt(ns.total_voters)} คน คิดเป็น ${turnout}%\nบัตรเสีย ${fmt(ns.bad_ballots)} ใบ (${badRate}%)\nไม่ประสงค์ลงคะแนน ${fmt(ns.no_vote)} คน`,
            },
            {
                title: 'วิเคราะห์ผลคะแนนรายพรรค',
                icon: 'party',
                content: partyResults.value
                    .slice(0, 10)
                    .map(
                        (p, i) =>
                            `อันดับ ${i + 1}: ${p.party} - ${fmt(p.total_votes)} คะแนน (${pct(p.total_votes, ns.good_ballots)}%) ชนะ ${p.constituencies_won} เขต`
                    )
                    .join('\n'),
            },
            {
                title: 'สรุปความผิดปกติที่ตรวจพบ',
                icon: 'alert',
                content: anomalies.length
                    ? `พบความผิดปกติทั้งหมด ${anomalies.length} รายการ\n- ระดับวิกฤต: ${criticals.length} รายการ\n- ระดับเตือน: ${anomalies.filter((a) => a.severity === 'warning').length} รายการ\n- ระดับข้อมูล: ${anomalies.filter((a) => a.severity === 'info').length} รายการ${
                          criticals.length
                              ? `\n\nความผิดปกติระดับวิกฤต:\n${criticals.map((a) => `- ${a.location}: ${a.message}`).join('\n')}`
                              : ''
                      }`
                    : 'ไม่พบความผิดปกติในข้อมูลผลเลือกตั้ง',
            },
            {
                title: 'ข้อเสนอแนะจากการวิเคราะห์',
                icon: 'recommend',
                content: criticals.length
                    ? `1. ควรตรวจสอบเขตที่มีความผิดปกติระดับวิกฤต (${criticals.length} เขต) โดยเร่งด่วน\n2. จังหวัดที่ต้องตรวจสอบเป็นพิเศษ: ${criticalProvinces.join(', ')}\n3. ข้อมูลที่ผิดปกติไม่ได้หมายความว่ามีการทุจริตเสมอไป อาจเกิดจากข้อผิดพลาดในการบันทึกข้อมูล\n4. ควรเปรียบเทียบข้อมูลกับแบบ 69 ฉบับจริงที่ กกต. ประกาศอย่างเป็นทางการ`
                    : 'ข้อมูลผลเลือกตั้งมีความสอดคล้องกันดี ไม่พบสิ่งผิดปกติที่ต้องตรวจสอบเพิ่มเติม',
            },
        ],
    };
};

// Auto-run analysis on mount
onMounted(() => {
    runClientSideAnalysis();
});
</script>
