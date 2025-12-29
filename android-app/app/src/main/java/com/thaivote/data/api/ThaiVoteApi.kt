package com.thaivote.data.api

import com.thaivote.data.model.*
import retrofit2.http.GET
import retrofit2.http.Path

interface ThaiVoteApi {
    @GET("elections/active")
    suspend fun getActiveElection(): Election

    @GET("elections/{id}")
    suspend fun getElection(@Path("id") id: Int): Election

    @GET("elections/{electionId}/national-results")
    suspend fun getNationalResults(@Path("electionId") electionId: Int): List<NationalResult>

    @GET("elections/{electionId}/provinces/{provinceId}/results")
    suspend fun getProvinceResults(
        @Path("electionId") electionId: Int,
        @Path("provinceId") provinceId: Int
    ): ProvinceResultResponse

    @GET("provinces")
    suspend fun getProvinces(): List<Province>

    @GET("provinces/geojson")
    suspend fun getProvincesGeoJson(): GeoJsonResponse

    @GET("parties")
    suspend fun getParties(): List<Party>

    @GET("parties/trending")
    suspend fun getTrendingParties(): List<TrendingParty>

    @GET("parties/{id}")
    suspend fun getParty(@Path("id") id: Int): Party

    @GET("news/breaking")
    suspend fun getBreakingNews(): List<NewsArticle>

    @GET("news")
    suspend fun getNews(): NewsListResponse
}
