package com.example.jackhalliday.activitytracker;

import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.ContentValues;
import android.content.Context;
import android.content.Intent;
import android.location.Location;
import android.location.LocationListener;
import android.os.Bundle;
import android.support.v4.app.NotificationCompat;
import android.util.Log;
import android.widget.Toast;
import java.text.DateFormat;
import java.util.Date;

import static android.content.Context.NOTIFICATION_SERVICE;

/**
 * Created by jackhalliday on 18/12/2016.
 */

public class MyLocationListener implements LocationListener {

    //need application context to perform db stuff and notification stuff
    private Context context;

    public MyLocationListener(Context context) {
        this.context = context;
    }

    //When a new location is received we want to insert the lat and long into the db along with a date.
    @Override
    public void onLocationChanged(Location location) {
        Log.d("activityTracker", "onLocationChanged");

        Date today = new Date();
        String latitude = String.valueOf(location.getLatitude());
        String longitude = String.valueOf(location.getLongitude());

        ContentValues newValues = new ContentValues();
        newValues.put(ContentProviderContract.COLUMN_URI_DATE, DateFormat.getDateInstance().format(today));
        newValues.put(ContentProviderContract.COLUMN_URI_LAT, latitude);
        newValues.put(ContentProviderContract.COLUMN_URI_LONG, longitude);

        try {
            context.getContentResolver().insert(ContentProviderContract.TABLE_URI_MOVEMENT, newValues);

            Log.d("activityTracker", "Added location details to db");
            //Toast.makeText(context, "Added location details to db", Toast.LENGTH_SHORT).show();

            //create a notification to tell user new location tracked, even if app closed.
            setNotification("Activity Tracker", "New location logged", false);
        }
        catch (Exception e) {
            Log.d("g53mdp", "Error adding location details to db");
            //Toast.makeText(context, "Error adding location details to db", Toast.LENGTH_SHORT).show();

            //alert user if there was a problem too.
            setNotification("Error Tracking", "There was an issue tracking your location.", true);
        }
    }

    //If the user turns ON GPS a notification will be created.
    @Override
    public void onProviderEnabled(String provider) {
        // the user enabled (for example) the GPS
        Log.d("activityTracker", "LocationListener onProviderEnabled: " + provider);

        String title = "Tracking Enabled";
        String message = "Return to app to stop tracking.";

        //Whether the user has the app open determines the priority of the notification, e.g. flash up.
        Boolean isUserActive = Boolean.valueOf(provider);
        setNotification(title, message, !isUserActive);
    }

    //If the user turns OFF GPS a notification will be created.
    @Override
    public void onProviderDisabled(String provider) {
        // the user disabled (for example) the GPS
        Log.d("activityTracker", "LocationListener onProviderDisabled: " + provider);

        String title = "Tracking Disabled";
        String message;

        Boolean isUserActive = Boolean.valueOf(provider);

        //Whether the user has the app open determines the message to be displayed and priority of the notification, e.g. flash up.
        if(isUserActive) {
            message = "Return to app to start tracking.";
        }
        else {
            message = "Turn on Location Services to resume tracking.";
            isUserActive = true;

        }
        setNotification(title, message, isUserActive);
    }

    //This method creates a displays the notifications.
    private void setNotification(String title, String message, Boolean isPriorityHigh) {

        Intent intent = new Intent(context, MainActivity.class);

        /*
        When the user presses the notificaion, we need to deal with the activity stack accordingly.
        Dont want multiple instances of same MainActivity.
         */
        intent.addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);
        PendingIntent pendingIntent = PendingIntent.getActivity(context, 0, intent, 0);

        Notification notification;

        //if we feel the user must see the notification then flash up, e.g. turned GPS off.
        if(isPriorityHigh) {

            notification = new NotificationCompat.Builder(context)
                    .setTicker(("Activity Tracker"))
                    .setSmallIcon(android.R.drawable.ic_menu_report_image)
                    .setDefaults(Notification.DEFAULT_ALL)
                    .setPriority(NotificationCompat.PRIORITY_HIGH)
                    .setContentTitle(title)
                    .setContentText(message)
                    .setContentIntent(pendingIntent)
                    .setAutoCancel(false)
                    .build();
        }
        else {

            //otherwise just appear in notification/status bar, e.g. location logged.
            notification = new NotificationCompat.Builder(context)
                    .setTicker(("Activity Tracker"))
                    .setSmallIcon(android.R.drawable.ic_menu_report_image)
                    .setContentTitle(title)
                    .setContentText(message)
                    .setContentIntent(pendingIntent)
                    .setAutoCancel(false)
                    .build();
        }

        NotificationManager notificationManager = (NotificationManager) context.getSystemService(NOTIFICATION_SERVICE);
        notificationManager.notify(0, notification);
    }

    @Override
    public void onStatusChanged(String provider, int status, Bundle extras) {
        // information about the signal, i.e. number of satellites
        Log.d("activityTracker", "onStatusChanged: " + provider + " " + status);
    }
}
