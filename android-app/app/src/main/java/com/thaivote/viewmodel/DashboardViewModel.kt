package com.thaivote.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.thaivote.data.repository.ElectionRepository
import com.thaivote.ui.screens.BreakingNews
import com.thaivote.ui.screens.DashboardStats
import com.thaivote.ui.screens.PartyResult
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class DashboardUiState(
    val isLoading: Boolean = false,
    val isLive: Boolean = true,
    val lastUpdated: String = "",
    val stats: DashboardStats = DashboardStats(),
    val partyResults: List<PartyResult> = emptyList(),
    val breakingNews: List<BreakingNews> = emptyList(),
    val error: String? = null
)

@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val repository: ElectionRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(DashboardUiState())
    val uiState: StateFlow<DashboardUiState> = _uiState.asStateFlow()

    init {
        loadData()
        connectToWebSocket()
    }

    fun refresh() {
        loadData()
    }

    private fun loadData() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }

            try {
                val election = repository.getActiveElection()
                val results = repository.getNationalResults(election.id)
                val news = repository.getBreakingNews()

                _uiState.update { state ->
                    state.copy(
                        isLoading = false,
                        lastUpdated = formatTime(System.currentTimeMillis()),
                        stats = DashboardStats(
                            countingProgress = election.stats?.countingProgress ?: 0f,
                            totalVotes = formatNumber(election.stats?.totalVotesCast ?: 0)
                        ),
                        partyResults = results.mapIndexed { index, result ->
                            PartyResult(
                                rank = index + 1,
                                partyId = result.partyId,
                                partyName = result.party.nameTh,
                                partyColor = result.party.color,
                                leaderName = result.party.leaderName ?: "",
                                seats = result.totalSeats,
                                votes = result.totalVotes,
                                percentage = result.votePercentage
                            )
                        },
                        breakingNews = news.map { article ->
                            BreakingNews(
                                id = article.id,
                                title = article.title,
                                source = article.source.name,
                                time = formatRelativeTime(article.publishedAt)
                            )
                        }
                    )
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    private fun connectToWebSocket() {
        viewModelScope.launch {
            repository.subscribeToUpdates().collect { update ->
                _uiState.update { state ->
                    state.copy(
                        lastUpdated = formatTime(System.currentTimeMillis()),
                        partyResults = update.nationalResults?.mapIndexed { index, result ->
                            PartyResult(
                                rank = index + 1,
                                partyId = result.partyId,
                                partyName = result.party.nameTh,
                                partyColor = result.party.color,
                                leaderName = result.party.leaderName ?: "",
                                seats = result.totalSeats,
                                votes = result.totalVotes,
                                percentage = result.votePercentage
                            )
                        } ?: state.partyResults
                    )
                }
            }
        }
    }

    private fun formatTime(timestamp: Long): String {
        val sdf = java.text.SimpleDateFormat("HH:mm:ss", java.util.Locale("th", "TH"))
        return sdf.format(java.util.Date(timestamp))
    }

    private fun formatNumber(number: Int): String {
        return java.text.NumberFormat.getInstance(java.util.Locale("th", "TH")).format(number)
    }

    private fun formatRelativeTime(timestamp: Long): String {
        val diff = System.currentTimeMillis() - timestamp
        return when {
            diff < 60000 -> "เมื่อสักครู่"
            diff < 3600000 -> "${diff / 60000} นาทีที่แล้ว"
            diff < 86400000 -> "${diff / 3600000} ชั่วโมงที่แล้ว"
            else -> "${diff / 86400000} วันที่แล้ว"
        }
    }
}
