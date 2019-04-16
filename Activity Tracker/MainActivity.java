package com.example.jackhalliday.activitytracker;

import android.Manifest;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.ComponentName;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.ServiceConnection;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.graphics.Color;
import android.graphics.drawable.Drawable;
import android.os.IBinder;
import android.support.v4.app.ActivityCompat;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.content.Context;
import android.location.LocationManager;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.RelativeLayout;
import android.widget.ScrollView;
import android.widget.TabHost;
import android.widget.TextView;
import android.widget.Toast;

import org.w3c.dom.Text;

import java.text.DateFormat;
import java.util.Date;

public class MainActivity extends AppCompatActivity {

    //Colours to set the background of the activity, when it's either 'welcome screen' or 'home screen'
    public static final String APP_COLOUR_BLUE = "#ff0099cc";
    public static final String APP_COLOUR_WHITE = "#ffffffff";
    public static final String DISTANCE_UNIT = " km";
    public static final String ENERGY_UNIT = " kcals";

    //Default text for TextViews before service does callback for user data.
    static final String LOADING_TEXT = "- - -";

    //TabHost tab titles.
    private final String TAB_TODAY = "today";
    private final String TAB_ALL_TIME = "all time";

    //Request code when coming back to MainActivity from InputUserDetails
    static final int INPUT_USER_DETAILS_REQUEST_CODE = 1;

    /*
    IDs to assist checking if GPS permissions granted, depending on
    whether the the user is on 'welcome screen' or 'home screen'
     */
    static final int PERMISSIONS_REQUEST_ACCESS_FINE_LOCATION_SETUP = 2;
    static final int PERMISSIONS_REQUEST_ACCESS_FINE_LOCATION_TRACK = 3;

    //PendingIntent unique identifiers
    static final int LOCATION_PENDING_INTENT_ID = 4;
    static final int ALARM_PENDING_INTENT_ID = 5;

    //IDs to deal with outcome of an alert presented to the user.
    private final int ALERT_TYPE_START_TRACKING = 6;
    private final int ALERT_TYPE_GPS_NOT_ENABLED = 7;

    ServiceConnection serviceConnection;
    private MyBoundService.MyBinder myService = null;

    PendingIntent locationPendingIntent;
    LocationManager locationManager;

    //AlarmManager stuff to automate scheduled tasks
    PendingIntent alarmPendingIntent;
    AlarmManager alarmManager;

    //Intent to pass user data into InputUserDetails to alter, e.g. settings menu
    Intent settingsIntent;

    Toast toast;

    Button setUpButton;
    Button startTracking;
    Button stopTracking;
    TextView todayDistanceTV;
    TextView totalDistanceTV;
    TextView todayStepsTV;
    TextView totalStepsTV;
    TextView todayCaloriesTV;
    TextView totalCaloriesTV;
    ProgressBar progressBar;
    View lineView;

    /*
    user data from db which can then be passed into InputUserDetails. The reason why these are here
    is to deal with efficiency of the app in terms of the number of db queries performed. I need
    to check if the user actually exists (e.g. they've set up their 'account') so I might as well
    grab the data when checking this.
     */
    private int userHeight;
    private int userWeight;
    private String userSex;

    /*
    Warning message about the app using system resources. We don't want to tell the user every
    time they press 'start tracking' so this prevents that unless activity destroyed.
     */
    private Boolean warningMessage;

    //We want to keep these for onSaveInstanceState when orientation of device changes.
    private float todayDistance;
    private int todaySteps;
    private int todayCalories;
    private float totalDistance;
    private int totalSteps;
    private int totalCalories;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        Log.d("activityTracker", "MainActivity onCreate");

        super.onCreate(savedInstanceState);

        setContentView(R.layout.activity_main);

        locationPendingIntent = PendingIntent.getBroadcast(MainActivity.this, LOCATION_PENDING_INTENT_ID, new Intent(MainActivity.this, MyBroadcastReceiver.class), PendingIntent.FLAG_UPDATE_CURRENT);
        locationManager = (LocationManager) getSystemService(Context.LOCATION_SERVICE);

        settingsIntent = new Intent(MainActivity.this, InputUserDetails.class);

        toast = Toast.makeText(MainActivity.this, "", Toast.LENGTH_LONG);

        //By default the user hasn't been presented with warning message.
        this.warningMessage = false;

        setUpButton = (Button) findViewById(R.id.onClickSetup);
        startTracking = (Button) findViewById(R.id.onClickStartTrack);
        stopTracking = (Button) findViewById(R.id.onClickStopTrack);

        todayDistanceTV = (TextView) findViewById(R.id.textView5);
        totalDistanceTV = (TextView) findViewById(R.id.textView4);
        todayStepsTV = (TextView) findViewById(R.id.textView6);
        totalStepsTV = (TextView) findViewById(R.id.textView8);
        todayCaloriesTV = (TextView) findViewById(R.id.textView7);
        totalCaloriesTV = (TextView) findViewById(R.id.textView9);
        progressBar = (ProgressBar) findViewById(R.id.progressBar3);
        lineView = (View) findViewById(R.id.viewLine);

        //Originally set the TextViews to Loading until service performs callback on data
        todayDistanceTV.setText(LOADING_TEXT);
        totalDistanceTV.setText(LOADING_TEXT);
        todayStepsTV.setText(LOADING_TEXT);
        totalStepsTV.setText(LOADING_TEXT);
        todayCaloriesTV.setText(LOADING_TEXT);
        totalCaloriesTV.setText(LOADING_TEXT);

        //Setup TabHost with corresponding tabs.
        TabHost tabHost = (TabHost)findViewById(R.id.tabHost);
        tabHost.setup();
        TabHost.TabSpec spec = tabHost.newTabSpec(TAB_TODAY);
        spec.setContent(R.id.tab1);
        spec.setIndicator(TAB_TODAY);
        tabHost.addTab(spec);
        spec = tabHost.newTabSpec(TAB_ALL_TIME);
        spec.setContent(R.id.tab2);
        spec.setIndicator(TAB_ALL_TIME);
        tabHost.addTab(spec);

        //Dealing with activity lifecycle, e.g. orientation changes.
        if(savedInstanceState != null) {

            //update variables
            this.warningMessage = savedInstanceState.getBoolean("warningMessage");
            this.todayDistance = savedInstanceState.getFloat("todayDistance");
            this.todaySteps = savedInstanceState.getInt("todaySteps");
            this.todayCalories = savedInstanceState.getInt("todayCalories");
            this.totalDistance = savedInstanceState.getFloat("totalDistance");
            this.totalSteps = savedInstanceState.getInt("totalSteps");
            this.totalCalories = savedInstanceState.getInt("totalCalories");

            //update TextViews
            todayDistanceTV.setText(String.format("%.2f", this.todayDistance / 1000) + DISTANCE_UNIT);
            totalDistanceTV.setText(String.format("%.2f", this.totalDistance / 1000) + DISTANCE_UNIT);
            todayStepsTV.setText("" + this.todaySteps);
            totalStepsTV.setText("" + this.totalSteps);
            todayCaloriesTV.setText("" + this.todayCalories + ENERGY_UNIT);
            totalCaloriesTV.setText("" + this.totalCalories + ENERGY_UNIT);
        }
    }

    /*
    If the user is on 'welcome screen' this will take them to setup screen
     */
    public void onClickSetup(View v) {

        System.out.println("hello");

        //first let's check if permissions have been granted to use location, otherwise app not usable.
        if(permissionsOk(PERMISSIONS_REQUEST_ACCESS_FINE_LOCATION_SETUP)) {
            startActivityForResult(settingsIntent, INPUT_USER_DETAILS_REQUEST_CODE);
        }
    }

    /*
    If the user is on 'home screen' this will take them to settings screen.
     */
    public void onClickSettings(View v) {

        /*
        Put Extras to load current values into InputUserDetails to give the user the perception that
        they are updating their details.
         */
        settingsIntent.putExtra("userHeight", this.userHeight);
        settingsIntent.putExtra("userWeight", this.userWeight);
        settingsIntent.putExtra("userSex", this.userSex);

        startActivityForResult(settingsIntent, INPUT_USER_DETAILS_REQUEST_CODE);
    }

    public void onClickStartTrack(View v) {

        //We want to begin tracking, but let's check location permissions granted.
        if(permissionsOk(PERMISSIONS_REQUEST_ACCESS_FINE_LOCATION_TRACK)) {

            //..and whether the GPS is enabled or not.
            if (!isGPSEnabled()) {

                String message = "Please turn on GPS to use this app";
                alertUser(message, InputUserDetails.ALERT_RESPONSE_POSITIVE_OK, null, ALERT_TYPE_GPS_NOT_ENABLED);
            }
            else {
                trackingEnabled(); //Method details below
            }
        }
    }

    private void trackingEnabled() {

        //If the user hasn't been presented with warning message then show them it.
        if(!this.warningMessage) {

            String message = "Warning! App will use phone resources even when it is closed or killed. " +
                    "Return to the app and press 'Stop Tracking' to disable";
            alertUser(message, InputUserDetails.ALERT_RESPONSE_POSITIVE_OK, InputUserDetails.ALERT_RESPONSE_NEGATIVE_CANCEL, ALERT_TYPE_START_TRACKING);
        }
        else {

            //Otherwise lets begin requesting Location updates.
            try {
                locationManager.requestLocationUpdates(locationManager.GPS_PROVIDER,
                        5, // minimum time interval between updates
                        5, // minimum distance between updates, in metres
                        locationPendingIntent);

                //Callbacks, etc. See service for more details.
                myService.startTracking();

                //update views
                startTracking.setVisibility(View.GONE);
                stopTracking.setVisibility(View.VISIBLE);
                progressBar.setVisibility(View.VISIBLE);
                lineView.setVisibility(View.GONE);

                Log.d("activityTracker", "MainActivity Tracking Enabled");
                //Toast.makeText(this, "Tracking Enabled", Toast.LENGTH_LONG).show();
            }
            catch(SecurityException e) {
                Log.d("activityTracker", e.toString());
                //If there is a problem let's close the app.
                finish();
            }
        }
    }

    //Method for checking whether phone's GPS is enabled or not.
    private boolean isGPSEnabled() {

        if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
            return true;
        }
        return false;
    }

    public void onClickStopTrack(View v) {

        //So the user wants to stop tracking, let's remove the location updates.
        try {
            locationManager.removeUpdates(locationPendingIntent);
            myService.stopTracking();
            stopTracking.setVisibility(View.GONE);
            startTracking.setVisibility(View.VISIBLE);
            progressBar.setVisibility(View.GONE);
            lineView.setVisibility(View.VISIBLE);

            Log.d("activityTracker", "MainActivity Tracking Disabled");
            //Toast.makeText(this, "Tracking Disabled", Toast.LENGTH_LONG).show();
        }
        catch (SecurityException e) {
            Log.d("activityTracker", e.toString());
            //If there is a problem let's close the app.
            finish();
        }
    }

    //Method for prompting user to enable location permissions if not agreed.
    private Boolean permissionsOk(int requestResult) {

        //If not granted then prompt user.
        if (ContextCompat.checkSelfPermission(MainActivity.this,
                Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {

            // We can request the permission.
            ActivityCompat.requestPermissions(MainActivity.this, new String[]{Manifest.permission.ACCESS_FINE_LOCATION},
                    requestResult);
            Log.d("activityTracker", "MainActivity Permission Requested");

            return false;
        }

        return true;
    }

    //Dealing with the users choice to accept permissions or not.
    @Override
    public void onRequestPermissionsResult(int requestCode, String permissions[], int[] grantResults) {

        switch (requestCode) {

            /*
            If user is on 'welcome screen' then we basically simulate a button click and check
            everything over again. If all ok then InputUserDetails (setup account) will be launched.
             */
            case PERMISSIONS_REQUEST_ACCESS_FINE_LOCATION_SETUP:

                if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {

                    Log.d("activityTracker", "MainActivity Location Permissions Granted");
                    onClickSetup(setUpButton);

                } else {

                    Log.d("activityTracker", "MainActivity Location Permissions Denied");
                }
                break;

            /*
            If user is on 'home screen' then we simulate the button click to start tracking and
            check everything over again. If all ok tracking will begin.
             */
            case PERMISSIONS_REQUEST_ACCESS_FINE_LOCATION_TRACK:

                if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {

                    Log.d("activityTracker", "MainActivity Location Permissions Granted");
                    onClickStartTrack(startTracking);

                } else {

                    Log.d("activityTracker", "MainActivity Location Permissions Denied");
                }
                break;

            default:
                Log.d("activityTracker", "MainActivity Location Permissions Problem");
                //If there is a problem let's close the app.
                finish();
                break;
        }
    }

    //Always called from onResume to deal with UI, AlarmManager, binding service, etc.
    private void checkUserDetails() {

        //First lets check if the user exists in the db, e.g. they have set up their account.
        final Cursor cursor = getContentResolver().query(ContentProviderContract.TABLE_URI_USER, null, null, null, null);

        /*
        If no record exists then we need to change the View to the 'welcome screen', which in turn
        will request user sets up an account.
         */
        if (cursor.getCount() == 0) {
            Log.d("activityTracker", "MainActivity Input User Details");

            //Make 'welcome screen' views visible and hide 'home screen' views. Change colours, etc.
            RelativeLayout welcomeScreen = (RelativeLayout) findViewById(R.id.welcomeScreen);
            welcomeScreen.setVisibility(View.VISIBLE);
            ScrollView homeScreen = (ScrollView) findViewById(R.id.homeScreen);
            homeScreen.setVisibility(View.GONE);
            View view = (View) findViewById(R.id.activity_main);
            view.setBackgroundColor(Color.parseColor(APP_COLOUR_BLUE));

            getSupportActionBar().hide();
        }

        /*
        Otherwise there is an account so let's present the home screen, etc.
         */
        else {
            Log.d("activityTracker", "MainActivity User Home Screen");

            //Create a service connection if not one already.
            if(serviceConnection == null) {

                serviceConnection = new ServiceConnection() {
                    @Override
                    public void onServiceConnected(ComponentName name, IBinder service) {
                        Log.d("activityTracker", "MainActivity onServiceConnected");
                        myService = (MyBoundService.MyBinder) service;
                        myService.registerCallback(callback);
                    }

                    @Override
                    public void onServiceDisconnected(ComponentName name) {
                        Log.d("activityTracker", "MainActivity onServiceDisconnected");
                        myService.unregisterCallback(callback);
                        myService = null;
                    }
                };
            }

            //bind activity to the service here as this wouldn't be necessary in the 'welcome screen' view.
            this.bindService(new Intent(this, MyBoundService.class), serviceConnection, Context.BIND_AUTO_CREATE);

            //Set an alarm manager to deal with scheduled tasks.
            setAlarmManager();

            /*
            Because a user exists and we have queried the db we may as well grab the user data to be efficient
            by reducing number of queries elsewhere, e.g. in InputUserDetails.
             */
            while (cursor.moveToNext()) {

                this.userHeight = cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_HEIGHT));
                this.userWeight = cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_WEIGHT));
                this.userSex = cursor.getString(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_SEX));

                /*
                We keep track of whether the user is in 'tracking mode' in db so when we return to the activity
                we can set views accordingly, e.g. whether the 'start tracking' or 'stop tracking' button
                should be displayed.
                 */
                if(cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_TRACKING)) == 0) {

                    stopTracking.setVisibility(View.GONE);
                    startTracking.setVisibility(View.VISIBLE);

                    progressBar.setVisibility(View.GONE);
                    lineView.setVisibility(View.VISIBLE);
                }
                else {

                    startTracking.setVisibility(View.GONE);
                    stopTracking.setVisibility(View.VISIBLE);

                    progressBar.setVisibility(View.VISIBLE);
                    lineView.setVisibility(View.GONE);
                }
            }

            //Finally make 'home screen' views visible and hide 'welcome screen' views. Change colours, etc.
            ScrollView homeScreen = (ScrollView) findViewById(R.id.homeScreen);
            homeScreen.setVisibility(View.VISIBLE);
            RelativeLayout welcomeScreen = (RelativeLayout) findViewById(R.id.welcomeScreen);
            welcomeScreen.setVisibility(View.GONE);
            View view = (View) findViewById(R.id.activity_main);
            view.setBackgroundColor(Color.parseColor(APP_COLOUR_WHITE));

            getSupportActionBar().show();
        }
    }

    //Method to deal with setting an AlarmManager to automate tasks every half-day.
    private void setAlarmManager() {

        Intent alarmIntent = new Intent(MainActivity.this, MyBroadcastReceiver.class);
        alarmIntent.putExtra(MyBroadcastReceiver.ALARM_MANAGER_RECEIVER, 0);
        alarmPendingIntent = PendingIntent.getBroadcast(this, ALARM_PENDING_INTENT_ID, alarmIntent, PendingIntent.FLAG_UPDATE_CURRENT);

        alarmManager = (AlarmManager) getSystemService(ALARM_SERVICE);
        alarmManager.setInexactRepeating(AlarmManager.RTC_WAKEUP,
                System.currentTimeMillis() + 60000,
                AlarmManager.INTERVAL_HALF_DAY, alarmPendingIntent);

        Log.d("activityTracker", "MainActivity AlarmManager Set");
    }

    /*
    When the user requests to delete all data from app in InputUserDetails we actually remove it via
    this activity because we want to stop the tracking, service, etc. and unbind before deletion to
    prevent any db inserts occuring during process.
     */
    private void deleteData() {

        try {
            getContentResolver().delete(ContentProviderContract.TABLE_URI_USER, null, null);
            getContentResolver().delete(ContentProviderContract.TABLE_URI_MOVEMENT, null, null);
            getContentResolver().delete(ContentProviderContract.TABLE_URI_RESULTS, null, null);

            Log.d("activityTracker", "All data removed successfully");
            toast.setText("All data removed successfully");
        }
        catch(Exception e) {
            Log.d("activityTracker", "Problem removing data. Please try again");
            toast.setText("Problem removing data. Please try again");
        }

        toast.show();
    }

    //This is basically an Alert Builder to deal with all the prompts that the user might trigger
    private void alertUser(String message, String positiveButton, String negativeButton, final int alertType) {

        AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(this);
        alertDialogBuilder.setMessage(message);

        alertDialogBuilder.setPositiveButton(positiveButton, new DialogInterface.OnClickListener() {

            @Override
            public void onClick(DialogInterface arg0, int arg1) {

                switch(alertType) {

                    case ALERT_TYPE_START_TRACKING:
                        warningMessage = true;
                        trackingEnabled();
                        break;
                    case ALERT_TYPE_GPS_NOT_ENABLED: //Stay in activity
                        break;
                    default: //Stay in activity
                        break;
                }
            }
        });

        alertDialogBuilder.setNegativeButton(negativeButton,new DialogInterface.OnClickListener() {

            @Override
            public void onClick(DialogInterface dialog, int which) {

                //Stay in  activity
            }
        });

        AlertDialog alertDialog = alertDialogBuilder.create();
        alertDialog.show();
    }

    //Callback Interface to deal with user data sent back from service
    CallbackInterface callback = new CallbackInterface() {

        @Override
        public void distance(final float dayDistance, final float totDistance) {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {

                    //set variables for onSaveInstanceState stuff.
                    todayDistance = dayDistance;
                    totalDistance = totDistance;

                    todayDistanceTV.setText(String.format("%.2f", todayDistance / 1000) + DISTANCE_UNIT);
                    totalDistanceTV.setText(String.format("%.2f", totalDistance / 1000) + DISTANCE_UNIT);

                }
            });
        }

        @Override
        public void steps(final int daySteps, final int totSteps) {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {

                    //set variables for onSaveInstanceState stuff.
                    todaySteps = daySteps;
                    totalSteps = totSteps;

                    todayStepsTV.setText("" + todaySteps);
                    totalStepsTV.setText("" + totalSteps);

                }
            });
        }

        @Override
        public void calories(final int dayCalories, final int totCalories) {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {

                    //set variables for onSaveInstanceState stuff.
                    todayCalories = dayCalories;
                    totalCalories = totCalories;

                    todayCaloriesTV.setText(todayCalories + ENERGY_UNIT);
                    totalCaloriesTV.setText(totalCalories + ENERGY_UNIT);

                }
            });
        }

        /*
        This callback deals with setting the correct button views to be displayed if the user has the app
        open but, for example, turns off the GPS.
         */
        @Override
        public void tracking(final int isTracking) {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {

                    //If the db via service says we arent tracking anymore, then present correct button views.
                    if(isTracking == 1) {

                        if(startTracking.getVisibility() == View.VISIBLE) {

                            onClickStartTrack(startTracking);
                        }
                    }
                    else {

                        if(stopTracking.getVisibility() == View.VISIBLE) {

                            onClickStopTrack(stopTracking);
                        }
                    }
                }
            });
        }
    };

    /*
    Deals with when the user returns from InputUserDetails activity, either by deleting data or
    updating data.
     */
    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data)
    {
        if (requestCode == INPUT_USER_DETAILS_REQUEST_CODE) {

            if (resultCode == RESULT_OK) {

                Bundle bundle = data.getExtras();

                //Has the user requested to delete all data?
                if(bundle.getBoolean("deleteData")) {
                    Log.d("activityTracker", "MainActivity Just deleted data");

                    //If so stop tracking (simulate click)...
                    onClickStopTrack(stopTracking);

                    //...and cancel the alarm manager as we wont need to schedule tasks for now.
                    if (alarmManager!= null) {
                        alarmManager.cancel(alarmPendingIntent);
                        Log.d("activityTracker", "MainActivity Cancelled Alarm Manager");
                    }

                    //...and unbind the service
                    if(serviceConnection != null) {
                        unbindService(serviceConnection);
                        serviceConnection = null;
                        Log.d("activityTracker", "MainActivity Cancelled Service Connection");
                    }

                    //...then we can remove data
                    deleteData();

                    /*
                    Reset TextViews as these will still present old data if the user then immediately
                    sets up new account without leaving app.
                     */
                    todayDistanceTV.setText(LOADING_TEXT);
                    totalDistanceTV.setText(LOADING_TEXT);
                    todayStepsTV.setText(LOADING_TEXT);
                    totalStepsTV.setText(LOADING_TEXT);
                    todayCaloriesTV.setText(LOADING_TEXT);
                    totalCaloriesTV.setText(LOADING_TEXT);
                }

                /*
                Reset intent so that correct InputUserDetails screen is loaded next time if data deleted,
                e.g. it needs to show 'setup account' mode.
                 */
                settingsIntent = new Intent(MainActivity.this, InputUserDetails.class);

            }
            else if(resultCode == RESULT_CANCELED) {

                //User pressed back button.
                Log.d("activityTracker", "MainActivity Result Cancelled");
            }

            //update views, service connection, bind service again, etc.
            checkUserDetails();
        }
    }


    //E.g. if the orientation of the device changes, save state.
    @Override
    public void onSaveInstanceState(Bundle savedInstanceState) {
        Log.d("activityTracker", "MainActivity onSaveInstanceState");

        savedInstanceState.putBoolean("warningMessage", this.warningMessage);
        savedInstanceState.putFloat("todayDistance", this.todayDistance);
        savedInstanceState.putInt("todaySteps", this.todaySteps);
        savedInstanceState.putInt("todayCalories", this.todayCalories);
        savedInstanceState.putFloat("totalDistance", this.totalDistance);
        savedInstanceState.putInt("totalSteps", this.totalSteps);
        savedInstanceState.putInt("totalCalories", this.totalCalories);

        super.onSaveInstanceState(savedInstanceState);
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        Log.d("activityTracker", "MainActivity onDestroy");
        if(serviceConnection!=null) {
            unbindService(serviceConnection);
            serviceConnection = null;
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        Log.d("activityTracker", "MainActivity onPause");
    }

    @Override
    protected void onResume() {
        super.onResume();
        Log.d("activityTracker", "MainActivity onResume");

        /*
        Putting this method here means service connection, binding, etc. will be dealt with correctly
        in accordance with the activity lifecycle.
         */
        checkUserDetails();
    }

    @Override
    protected void onStart() {
        super.onStart();
        Log.d("activityTracker", "MainActivity onStart");
    }

    @Override
    protected void onStop() {
        super.onStop();
        Log.d("activityTracker", "MainActivity onStop");

        //Dont want to be using the service if not necessary
        if(serviceConnection!=null) {
            unbindService(serviceConnection);
            serviceConnection = null;
        }
    }
}