package com.thaivote.data.model

import com.google.gson.annotations.SerializedName

data class Election(
    val id: Int,
    val name: String,
    @SerializedName("name_en") val nameEn: String?,
    val type: String,
    val status: String,
    @SerializedName("election_date") val electionDate: String,
    @SerializedName("is_active") val isActive: Boolean,
    val stats: ElectionStats?
)

data class ElectionStats(
    @SerializedName("total_eligible_voters") val totalEligibleVoters: Int,
    @SerializedName("total_votes_cast") val totalVotesCast: Int,
    @SerializedName("valid_votes") val validVotes: Int,
    @SerializedName("invalid_votes") val invalidVotes: Int,
    @SerializedName("voter_turnout") val voterTurnout: Float,
    @SerializedName("counting_progress") val countingProgress: Float,
    @SerializedName("stations_counted") val stationsCounted: Int,
    @SerializedName("stations_total") val stationsTotal: Int
)

data class Party(
    val id: Int,
    @SerializedName("name_th") val nameTh: String,
    @SerializedName("name_en") val nameEn: String,
    val abbreviation: String,
    val logo: String?,
    val color: String,
    @SerializedName("leader_name") val leaderName: String?,
    @SerializedName("leader_photo") val leaderPhoto: String?,
    @SerializedName("party_number") val partyNumber: Int?
)

data class NationalResult(
    @SerializedName("party_id") val partyId: Int,
    val party: Party,
    @SerializedName("constituency_votes") val constituencyVotes: Int,
    @SerializedName("party_list_votes") val partyListVotes: Int,
    @SerializedName("total_votes") val totalVotes: Int,
    @SerializedName("constituency_seats") val constituencySeats: Int,
    @SerializedName("party_list_seats") val partyListSeats: Int,
    @SerializedName("total_seats") val totalSeats: Int,
    @SerializedName("vote_percentage") val votePercentage: Float,
    val rank: Int
)

data class Province(
    val id: Int,
    @SerializedName("name_th") val nameTh: String,
    @SerializedName("name_en") val nameEn: String,
    val code: String,
    val region: String,
    val population: Int,
    @SerializedName("total_constituencies") val totalConstituencies: Int,
    val latitude: Double?,
    val longitude: Double?
)

data class ProvinceResult(
    @SerializedName("party_id") val partyId: Int,
    val party: Party,
    @SerializedName("total_votes") val totalVotes: Int,
    @SerializedName("seats_won") val seatsWon: Int,
    @SerializedName("vote_percentage") val votePercentage: Float
)

data class ProvinceResultResponse(
    val province: Province,
    val results: List<ProvinceResult>,
    val constituencies: List<ConstituencyResult>
)

data class ConstituencyResult(
    val id: Int,
    val number: Int,
    @SerializedName("candidate_id") val candidateId: Int,
    val candidate: Candidate?,
    @SerializedName("party_id") val partyId: Int,
    val party: Party?,
    val votes: Int,
    @SerializedName("vote_percentage") val votePercentage: Float,
    @SerializedName("is_winner") val isWinner: Boolean
)

data class Candidate(
    val id: Int,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    val nickname: String?,
    val photo: String?,
    @SerializedName("party_id") val partyId: Int?
)

data class NewsArticle(
    val id: Int,
    val title: String,
    val excerpt: String?,
    val url: String,
    @SerializedName("image_url") val imageUrl: String?,
    @SerializedName("published_at") val publishedAt: Long,
    val source: NewsSource
)

data class NewsSource(
    val id: Int,
    val name: String,
    val logo: String?
)

data class NewsListResponse(
    val data: List<NewsArticle>,
    @SerializedName("current_page") val currentPage: Int,
    @SerializedName("last_page") val lastPage: Int
)

data class TrendingParty(
    val id: Int,
    @SerializedName("name_th") val nameTh: String,
    val color: String,
    val abbreviation: String,
    val mentions: Int,
    val trend: Float
)

data class GeoJsonResponse(
    val type: String,
    val features: List<GeoJsonFeature>
)

data class GeoJsonFeature(
    val type: String,
    val properties: Map<String, Any>,
    val geometry: GeoJsonGeometry
)

data class GeoJsonGeometry(
    val type: String,
    val coordinates: Any
)

data class ResultUpdate(
    val nationalResults: List<NationalResult>?,
    val stats: ElectionStats?,
    val updatedAt: String
)
