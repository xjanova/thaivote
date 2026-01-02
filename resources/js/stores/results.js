import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useResultsStore = defineStore('results', () => {
    // State
    const election = ref(null);
    const stats = ref(null);
    const nationalResults = ref([]);
    const provinceResults = ref([]);
    const constituencyResults = ref([]);
    const provinces = ref([]);
    const parties = ref([]);
    const isLoading = ref(false);
    const lastUpdated = ref(null);

    // Getters
    const totalSeats = computed(() =>
        nationalResults.value.reduce((sum, r) => sum + r.total_seats, 0)
    );

    const totalVotes = computed(() =>
        nationalResults.value.reduce((sum, r) => sum + r.total_votes, 0)
    );

    const countingProgress = computed(() => stats.value?.counting_progress || 0);

    const leadingParty = computed(() => {
        if (!nationalResults.value.length) {
            return null;
        }
        return nationalResults.value.sort((a, b) => b.total_seats - a.total_seats)[0];
    });

    // Actions
    const fetchElection = async (electionId) => {
        isLoading.value = true;
        try {
            const response = await axios.get(`/api/elections/${electionId}`);
            election.value = response.data.election;
            stats.value = response.data.stats;
            nationalResults.value = response.data.national_results;
            parties.value = response.data.parties;
            lastUpdated.value = new Date();
        } finally {
            isLoading.value = false;
        }
    };

    const fetchProvinces = async () => {
        const response = await axios.get('/api/provinces');
        provinces.value = response.data;
    };

    const fetchProvinceResults = async (electionId, provinceId) => {
        const response = await axios.get(
            `/api/elections/${electionId}/provinces/${provinceId}/results`
        );
        // Update or add to provinceResults
        const index = provinceResults.value.findIndex(
            (r) => r.election_id === electionId && r.province_id === provinceId
        );
        if (index >= 0) {
            provinceResults.value[index] = response.data;
        } else {
            provinceResults.value.push(response.data);
        }
        return response.data;
    };

    const fetchConstituencyResults = async (electionId, constituencyId) => {
        const response = await axios.get(
            `/api/elections/${electionId}/constituencies/${constituencyId}/results`
        );
        return response.data;
    };

    const getProvinceResults = (provinceId) =>
        provinceResults.value.filter((r) => r.province_id === provinceId);

    const getProvinceCountingProgress = (provinceId) => {
        const results = getProvinceResults(provinceId);
        if (!results.length) {
            return 0;
        }
        const total = results.reduce((sum, r) => sum + r.constituencies_total, 0);
        const counted = results.reduce((sum, r) => sum + r.constituencies_counted, 0);
        return total > 0 ? Math.round((counted / total) * 100) : 0;
    };

    const getConstituencies = (provinceId) =>
        constituencyResults.value.filter((c) => c.province_id === provinceId);

    const getProvincesWonByParty = (partyId) => {
        const partyProvinces = new Set();
        provinceResults.value.forEach((result) => {
            if (result.party_id === partyId && result.seats_won > 0) {
                // Check if this party won the most seats in this province
                const provinceTotal = provinceResults.value.filter(
                    (r) => r.province_id === result.province_id
                );
                const maxSeats = Math.max(...provinceTotal.map((r) => r.seats_won));
                if (result.seats_won === maxSeats) {
                    partyProvinces.add(result.province_id);
                }
            }
        });
        return partyProvinces.size;
    };

    const updateResults = (newResults) => {
        // Handle real-time updates
        if (newResults.national) {
            nationalResults.value = newResults.national;
        }
        if (newResults.stats) {
            stats.value = newResults.stats;
        }
        if (newResults.provinces) {
            newResults.provinces.forEach((update) => {
                const index = provinceResults.value.findIndex(
                    (r) => r.province_id === update.province_id && r.party_id === update.party_id
                );
                if (index >= 0) {
                    provinceResults.value[index] = { ...provinceResults.value[index], ...update };
                } else {
                    provinceResults.value.push(update);
                }
            });
        }
        lastUpdated.value = new Date();
    };

    const getPartyById = (partyId) => parties.value.find((p) => p.id === partyId);

    return {
        // State
        election,
        stats,
        nationalResults,
        provinceResults,
        constituencyResults,
        provinces,
        parties,
        isLoading,
        lastUpdated,

        // Getters
        totalSeats,
        totalVotes,
        countingProgress,
        leadingParty,

        // Actions
        fetchElection,
        fetchProvinces,
        fetchProvinceResults,
        fetchConstituencyResults,
        getProvinceResults,
        getProvinceCountingProgress,
        getConstituencies,
        getProvincesWonByParty,
        updateResults,
        getPartyById,
    };
});
