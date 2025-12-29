package com.thaivote

import android.app.Application
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class ThaiVoteApp : Application() {
    override fun onCreate() {
        super.onCreate()
    }
}
