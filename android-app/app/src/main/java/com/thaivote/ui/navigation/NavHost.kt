package com.thaivote.ui.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import com.thaivote.ui.screens.DashboardScreen
import com.thaivote.ui.screens.MapScreen
import com.thaivote.ui.screens.NewsScreen
import com.thaivote.ui.screens.PartyDetailScreen
import com.thaivote.ui.screens.ProvinceDetailScreen

sealed class Screen(val route: String) {
    object Dashboard : Screen("dashboard")
    object Map : Screen("map")
    object News : Screen("news")
    object ProvinceDetail : Screen("province/{provinceId}") {
        fun createRoute(provinceId: Int) = "province/$provinceId"
    }
    object PartyDetail : Screen("party/{partyId}") {
        fun createRoute(partyId: Int) = "party/$partyId"
    }
}

@Composable
fun ThaiVoteNavHost(navController: NavHostController) {
    NavHost(
        navController = navController,
        startDestination = Screen.Dashboard.route
    ) {
        composable(Screen.Dashboard.route) {
            DashboardScreen(
                onNavigateToMap = { navController.navigate(Screen.Map.route) },
                onNavigateToNews = { navController.navigate(Screen.News.route) },
                onNavigateToProvince = { provinceId ->
                    navController.navigate(Screen.ProvinceDetail.createRoute(provinceId))
                },
                onNavigateToParty = { partyId ->
                    navController.navigate(Screen.PartyDetail.createRoute(partyId))
                }
            )
        }

        composable(Screen.Map.route) {
            MapScreen(
                onNavigateBack = { navController.popBackStack() },
                onNavigateToProvince = { provinceId ->
                    navController.navigate(Screen.ProvinceDetail.createRoute(provinceId))
                }
            )
        }

        composable(Screen.News.route) {
            NewsScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.ProvinceDetail.route) { backStackEntry ->
            val provinceId = backStackEntry.arguments?.getString("provinceId")?.toIntOrNull() ?: 0
            ProvinceDetailScreen(
                provinceId = provinceId,
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.PartyDetail.route) { backStackEntry ->
            val partyId = backStackEntry.arguments?.getString("partyId")?.toIntOrNull() ?: 0
            PartyDetailScreen(
                partyId = partyId,
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}
