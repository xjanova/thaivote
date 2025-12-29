package com.thaivote.data.repository

import com.thaivote.data.api.ThaiVoteApi
import com.thaivote.data.model.*
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ElectionRepository @Inject constructor(
    private val api: ThaiVoteApi
) {
    suspend fun getActiveElection(): Election {
        return api.getActiveElection()
    }

    suspend fun getElection(id: Int): Election {
        return api.getElection(id)
    }

    suspend fun getNationalResults(electionId: Int): List<NationalResult> {
        return api.getNationalResults(electionId)
    }

    suspend fun getProvinceResults(electionId: Int, provinceId: Int): ProvinceResultResponse {
        return api.getProvinceResults(electionId, provinceId)
    }

    suspend fun getProvinces(): List<Province> {
        return api.getProvinces()
    }

    suspend fun getParties(): List<Party> {
        return api.getParties()
    }

    suspend fun getBreakingNews(): List<NewsArticle> {
        return api.getBreakingNews()
    }

    fun subscribeToUpdates(): Flow<ResultUpdate> = flow {
        // WebSocket implementation would go here
        // For now, return empty flow
    }
}
