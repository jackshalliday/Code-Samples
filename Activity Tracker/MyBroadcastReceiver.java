package com.example.jackhalliday.activitytracker;

/**
 * Created by jackhalliday on 19/12/2016.
 */

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.location.LocationManager;
import android.util.Log;
import android.widget.Toast;

public class MyBroadcastReceiver extends BroadcastReceiver {

    //String to deal with alarmManager intent stuff
    public static final String ALARM_MANAGER_RECEIVER = "com.example.jackhalliday.activitytracker.ALARM_MANAGER_RECEIVER";

    @Override
    public void onReceive(Context context, Intent intent) {
        Log.d("activityTracker", "MyBroadcastReceiver onReceive");
        //Toast.makeText(context, "MyBoundService INSERT Scheduled Tasks Complete", Toast.LENGTH_SHORT).show();

        if(intent.hasExtra(LocationManager.KEY_LOCATION_CHANGED) || intent.hasExtra(LocationManager.KEY_PROVIDER_ENABLED)
                || intent.hasExtra(ALARM_MANAGER_RECEIVER)) {

            //basically creating a copy of the intent to be passed into the service.
            Intent newIntent = new Intent(context, MyBoundService.class);
            newIntent.putExtras(intent.getExtras());

            //start service
            context.startService(newIntent);

            /*
            ...then destroy service once we are done but only providing the user doesnt have MainActivity active,
            e.g. is binded to the service. Service is kept running until MainActivity stopped or destroyed,
            where it is unbound.
             */
            context.stopService(newIntent);
        }
    }
}