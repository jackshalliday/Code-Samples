package com.example.jackhalliday.activitytracker;

import android.app.PendingIntent;
import android.app.Service;
import android.content.ContentValues;
import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.location.Location;
import android.location.LocationManager;
import android.os.Binder;
import android.os.Bundle;
import android.os.IBinder;
import android.os.IInterface;
import android.os.RemoteCallbackList;
import android.util.Log;
import android.widget.Toast;
import java.text.DateFormat;
import java.util.Date;

/**
 * Created by jackhalliday on 20/12/2016.
 */

public class MyBoundService extends Service {

    /*
    Generic values that are multiplied by the user's data to determine unique stride length, calories burnt
    over distance, per step, etc. to give more accurate data.
     */
    static final double STRIDE_LENGTH_MALE = 0.415;
    static final double STRIDE_LENGTH_FEMALE = 0.413;
    static final double CALORIES_PER_MILE = 0.57;

    //Values to deal with conversion stuff to calculate user unique data.
    static final double METRES_IN_MILE = 1609.34;
    static final double KILOS_TO_POUNDS = 2.20462;

    //When tasks are scheduled via AlarmManager, we want to process stuff from yesterday's date, e.g. 24 hours ago.
    static final int TIME_DIFFERENCE = 24*60*60*1000;

    /*
    Value inserted into db when user stops tracking, or GPS disabled, etc. This assists calculating correctness
    of distance travelled
     */
    static final String STOP_TRACKING = "STOP_TRACKING";

    MyLocationListener locationListener;
    RemoteCallbackList<MyBinder> remoteCallbackList = new RemoteCallbackList<MyBinder>();

    /*
    Our last location. If the user has MainActivity open, they are active (isUserActive = true). So instead of
    querying the database everytime a callback is performed (inefficient), we can just perform the calculation
    from previousLocation to new location distance for callback, and deal with the db stuff in scheduled tasks
    by the AlarmManager.
     */
    Location previousLocation;

    //Does the user have the app open in front of them, e.g. MainActivity onStart, onResume, etc.
    private Boolean isUserActive;

    //Deals with ensuring correct values are sent back to the UI in callback by resetting previous location
    private Boolean resetPreviousLocation;

    /*
    We store whether the user is tracking in the User table in db. This helps will generating correct
    UI if the app was killed and restarted for example.
     */
    private int isTracking;

    //user data to be sent to callBack
    private float todayDistance;
    private int todaySteps;
    private int todayCalories;
    private float totalDistance;
    private int totalSteps;
    private int totalCalories;

    //user unique data
    private int userHeight;
    private int userWeight;
    private String userSex;
    private double userStrideLength;
    private double userCaloriesPerStep;

    @Override
    public void onCreate() {
        Log.d("activityTracker", "MyBoundService onCreate");
        super.onCreate();

        locationListener = new MyLocationListener(this);

        //put service in default state.
        this.isUserActive = false;
        this.resetPreviousLocation = true;
        this.isTracking = 0;
        this.todayDistance = 0;
        this.todaySteps = 0;
        this.todayCalories = 0;
        this.totalDistance = 0;
        this.totalSteps = 0;
        this.totalCalories = 0;
    }

    //stuff to send back to the UI if isUserActive = true.
    public void callBackThread() {

        final Thread thread = new Thread(new Runnable() {

            public void run() {
                Log.d("activityTracker", "MyBoundService callBackThread");

                /*
                We need to sleep so that callback can happen when the app is first launched to send
                data back to UI.
                 */
                try{Thread.sleep(1000);}catch (Exception e) {};

                todaySteps = calculateDateSteps(todayDistance);
                todayCalories = calculateDateCalories(todaySteps);

                /*
                Total total values, we need to add whatever user has done today + the their totals
                from previous days
                 */
                float sumDistance = totalDistance + todayDistance;
                int sumSteps = totalSteps + todaySteps;
                int sumCalories = totalCalories + todayCalories;

                doCallbacks(todayDistance, todaySteps, todayCalories, sumDistance, sumSteps, sumCalories, isTracking);
            }
        });

        thread.start();
    }


    //Perform the callBacks.
    public void doCallbacks(float todayDistance, int todaySteps, int todayCalories, float totalDistance, int totalSteps, int totalCalories, int isTracking)
    {
        final int n = remoteCallbackList.beginBroadcast();

        for (int i=0; i<n; i++) {

            remoteCallbackList.getBroadcastItem(i).callback.distance(todayDistance, totalDistance);
            remoteCallbackList.getBroadcastItem(i).callback.steps(todaySteps, totalSteps);
            remoteCallbackList.getBroadcastItem(i).callback.calories(todayCalories, totalCalories);
            remoteCallbackList.getBroadcastItem(i).callback.tracking(isTracking);
        }

        remoteCallbackList.finishBroadcast();
    }

    /*
    As mentioned previously, we dont want to query the db everytime a callback is done. We can keep
    track of stuff with varibales in the service instead
     */
    private void updateTodayDistance(float distance) {
        this.todayDistance += distance;
    }

    private void updateTotalDistance(float distance) {

        this.totalDistance += distance;
    }

    private void updateTotalSteps(int steps) {

        this.totalSteps += steps;
    }

    private void updateTotalCalories(int calories) {

        this.totalCalories += calories;
    }

    /*
    This method is responsible for calculating the total distance for a given date, e.g. when the user
    launches the app we want to get everything they've done today OR sheduled tasks for getting data from
    previous date.
     */
    private float calculateDateDistance(Date date) {

        int i = 0;
        double oldLat = 0;
        double oldLong = 0;
        float distance = 0;

        //We are going to get locations back from db and determine distances between them.
        Location oldLocation = new Location("old location");
        Location newLocation = new Location("new location");
        
        String[] queryColumns = new String[] {
                ContentProviderContract.COLUMN_URI_ID,
                ContentProviderContract.COLUMN_URI_LAT,
                ContentProviderContract.COLUMN_URI_LONG,
        };

        final Cursor cursor = getContentResolver().query(ContentProviderContract.TABLE_URI_MOVEMENT, queryColumns, DateFormat.getDateInstance().format(date), null, null);

        //While there is location data for that date.
        while (cursor.moveToNext()) {

            String latitude = cursor.getString(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_LAT));
            String longitude = cursor.getString(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_LONG));

            /*
            If the row has stop tracking we skip over calculating distance and reset where we are calculating from
            e.g. locationA = 5m, locationB = stop tracking, locationC = +50m means that the 45m between A and C wont be
            counted as we stopped tracking at this point.
             */
            if(latitude.equals(STOP_TRACKING) || longitude.equals(STOP_TRACKING)) {
                i = 0;
            }
            else {

                double newLat = Double.parseDouble(latitude);
                double newLong = Double.parseDouble(longitude);

                //As explained above we are basically saying a distance of 0 in this instance.
                if(i == 0) {
                    oldLat = newLat;
                    oldLong = newLong;
                    i++;
                }

                oldLocation.setLatitude(oldLat);
                oldLocation.setLongitude(oldLong);

                newLocation.setLatitude(newLat);
                newLocation.setLongitude(newLong);

                //Sum up total distance for that date.
                distance += oldLocation.distanceTo(newLocation);

                oldLat = newLat;
                oldLong = newLong;
            }
        }

        return distance;
    }

    //Calculated estimate of the number of steps based on user data and calculations.
    private int calculateDateSteps(float todayDistance) {

        return (int) (todayDistance / userStrideLength);
    }

    //Calculated estimate of the number of calories based on user data and calculations.
    private int calculateDateCalories(int todaySteps) {

        return (int) (todaySteps * this.userCaloriesPerStep);
    }

    /*
    Here we are quering the Results table, which have total values of distance, steps, etc. for each date.
    We sum them to get our totals for the 'all time' tab in MainActivity.
     */
    private void calculateTotalDistance() {

        this.totalDistance = 0;
        this.totalSteps = 0;
        this.totalCalories = 0;

        String[] queryColumns = new String[] {
                ContentProviderContract.COLUMN_URI_ID,
                ContentProviderContract.COLUMN_URI_DISTANCE,
                ContentProviderContract.COLUMN_URI_STEPS,
                ContentProviderContract.COLUMN_URI_CALORIES
        };

        final Cursor cursor = getContentResolver().query(ContentProviderContract.TABLE_URI_RESULTS, queryColumns, "", null, null);
        
        while (cursor.moveToNext()) {

            updateTotalDistance(cursor.getFloat(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_DISTANCE)));
            updateTotalSteps(cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_STEPS)));
            updateTotalCalories(cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_CALORIES)));
        }
    }

    /*
    In certain circumstances we need to recalculate stuff for correct MainActivity UI. E.g. when
    MainActivity Binds to service, start tracking button is pressed, etc.
     */
    private void calculateValues() {

        this.todayDistance = calculateDateDistance(new Date());
        this.todaySteps = calculateDateSteps(this.todayDistance);
        this.todayCalories = calculateDateCalories(this.todaySteps);
        calculateTotalDistance();
    }

    /*
    The service behaves in two ways, either when the user is present (callbacks) or not (location updates and
    scheduled tasks in background).
     */
    private void setUserActive(Boolean value) {
        this.isUserActive = value;
    }

    //Query db to get user details and assign to variables.
    private void setUserDetails() {

        final Cursor cursor = getContentResolver().query(ContentProviderContract.TABLE_URI_USER, null, null, null, null);

        while (cursor.moveToNext()) {

            this.userHeight = cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_HEIGHT));
            this.userWeight = cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_WEIGHT));
            this.userSex = cursor.getString(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_SEX));

            //db says whether the user is currently tracking or not
            this.isTracking = cursor.getInt(cursor.getColumnIndex(ContentProviderContract.COLUMN_URI_TRACKING));

            //calculate unique user data
            if(this.userSex.equals(InputUserDetails.USER_MALE)) {
                this.userStrideLength = (this.userHeight * STRIDE_LENGTH_MALE) / 100;
            }
            else {
                this.userStrideLength = (this.userHeight * STRIDE_LENGTH_FEMALE) / 100;
            }

            //unique-to-user conversion factor calculations for their calories per step.
            double userWeightLbs = this.userWeight * KILOS_TO_POUNDS;
            double caloriesPerMile = userWeightLbs * CALORIES_PER_MILE;
            double stepsPerMile = METRES_IN_MILE / this.userStrideLength;
            this.userCaloriesPerStep = caloriesPerMile / stepsPerMile;
        }
    }

    //If the user starts or stops tracking we need to also update the value in db.
    private void setIsTracking(int value) {

        ContentValues newValues = new ContentValues();
        newValues.put(ContentProviderContract.COLUMN_URI_TRACKING, value);

        try {
            getContentResolver().update(ContentProviderContract.TABLE_URI_USER, newValues, null, null);
            this.isTracking = value;

            Log.d("activityTracker", "MyBoundService UPDATE User isTracking Success");
            //Toast.makeText(this, "MyBoundService UPDATE User isTracking Success", Toast.LENGTH_SHORT).show();
        }
        catch(Exception e) {
            Log.d("activityTracker", "MyBoundService UPDATE User isTracking ERROR");
            //Toast.makeText(this, "MyBoundService UPDATE User isTracking ERROR", Toast.LENGTH_SHORT).show();
        }
    }

    /*
    As explained above we insert a 'stop tracking' row into the db when tracking is disabled to increase
    reliability of results.
     */
    private void setNoMovement() {

        ContentValues newValues = new ContentValues();
        newValues.put(ContentProviderContract.COLUMN_URI_DATE, DateFormat.getDateInstance().format(new Date()));
        newValues.put(ContentProviderContract.COLUMN_URI_LAT, STOP_TRACKING);
        newValues.put(ContentProviderContract.COLUMN_URI_LONG, STOP_TRACKING);

        try {
            this.getContentResolver().insert(ContentProviderContract.TABLE_URI_MOVEMENT, newValues);

            Log.d("activityTracker", "MainActivity Stop tracking added to db");
            //Toast.makeText(this, "MainActivity Stop tracking added to db", Toast.LENGTH_SHORT).show();
        }
        catch (Exception e) {
            Log.d("activityTracker", "MainActivity Error adding Stop tracking to db");
            //Toast.makeText(this, "MainActivity Error adding Stop tracking to db", Toast.LENGTH_SHORT).show();
        }
    }

    /*
    This method is called either by the AlarmManager every half day or when the user launches the app.
    Scheduled tasks prevent querying the db all the time when the user is using the app through the callBack.
    So instead we can do the longer running stuff at intervals, to keep app efficient.
     */
    private void scheduledTasks() {
        Log.d("activityTracker", "MyBoundService Scheduled Tasks");

        //We want to put the results from yesterdays tracking into the Results table of db.

        Date yesterday = new Date(System.currentTimeMillis()-TIME_DIFFERENCE);

        float yesterdayDistance = calculateDateDistance(yesterday);
        int yesterdaySteps = calculateDateSteps(yesterdayDistance);
        int yesterdayCalories = calculateDateCalories(yesterdaySteps);

        ContentValues newValues = new ContentValues();
        newValues.put(ContentProviderContract.COLUMN_URI_DATE, DateFormat.getDateInstance().format(yesterday));
        newValues.put(ContentProviderContract.COLUMN_URI_DISTANCE, yesterdayDistance);
        newValues.put(ContentProviderContract.COLUMN_URI_STEPS, yesterdaySteps);
        newValues.put(ContentProviderContract.COLUMN_URI_CALORIES, yesterdayCalories);

        String[] queryColumns = new String[] {
                ContentProviderContract.COLUMN_URI_ID,
                ContentProviderContract.COLUMN_URI_DISTANCE,
                ContentProviderContract.COLUMN_URI_STEPS,
                ContentProviderContract.COLUMN_URI_CALORIES
        };

        String selection = " WHERE date = '" + DateFormat.getDateInstance().format(yesterday) + "'";

        final Cursor cursor = getContentResolver().query(ContentProviderContract.TABLE_URI_RESULTS, queryColumns, selection, null, null);

        //If the db doesnt have a row with yesterdays date then we create a new one (insert to db)
        if (cursor.getCount() == 0) {

            try {
                getContentResolver().insert(ContentProviderContract.TABLE_URI_RESULTS, newValues);
                Log.d("activityTracker", "MyBoundService INSERT Scheduled Tasks Complete");
                //Toast.makeText(this, "MyBoundService INSERT Scheduled Tasks Complete", Toast.LENGTH_SHORT).show();
            }
            catch (Exception e) {
                Log.d("activityTracker", "MyBoundService INSERT Scheduled Tasks ERROR");
                //Toast.makeText(this, "MyBoundService INSERT Scheduled Tasks ERROR", Toast.LENGTH_SHORT).show();
            }
        }
        else {

            //otherwise we have performed scheduled tasks before for this date so let's just update the values instead.

            try {
                getContentResolver().update(ContentProviderContract.TABLE_URI_RESULTS, newValues, null, null);
                Log.d("activityTracker", "MyBoundService UPDATE Scheduled Tasks Complete");
                //Toast.makeText(this, "MyBoundService UPDATE Scheduled Tasks Complete", Toast.LENGTH_SHORT).show();
            }
            catch (Exception e) {
                Log.d("activityTracker", "MyBoundService UPDATE Scheduled Tasks ERROR");
                //Toast.makeText(this, "MyBoundService UPDATE Scheduled Tasks ERROR", Toast.LENGTH_SHORT).show();
            }
        }
    }

    //Binder stuff
    public class MyBinder extends Binder implements IInterface
    {
        @Override
        public IBinder asBinder() {
            return this;
        }

        //User must have app open to trigger this so we need to perform calculations and callback.
        public void startTracking() {

            //update isTracking and db to say user is tracking
            setIsTracking(1);
            calculateValues();
        }

        public void stopTracking() {
            //update isTracking and db to say user is tracking
            setIsTracking(0);

            //put 'stop tracking' row into db for data reliability reasons, explained above.
            setNoMovement();

            /*
            We could have pressed stop whilst walking so previous location should be reset to return
            correct data to UI.
             */
            resetPreviousLocation = true;
            calculateValues();
        }

        public void registerCallback(CallbackInterface callback) {
            this.callback = callback;
            remoteCallbackList.register(MyBinder.this);
        }

        public void unregisterCallback(CallbackInterface callback) {
            remoteCallbackList.unregister(MyBinder.this);
        }

        CallbackInterface callback;
    }

    //Stuff to do when the user has opened the app, aka MainActivity binds to service.
    @Override
    public IBinder onBind(Intent arg0) {
        Log.d("activityTracker", "MyBoundService onBind");

        //the user is present, e.g. looking at the screen..
        setUserActive(true);

        //...so we need to get their details via db
        setUserDetails();

        //...then perform the scheduled tasks, e.g. totals and correct values to callBack to UI, etc.
        scheduledTasks();
        calculateValues();

        //...finally perform the callback to update TextViews in MainActivity.
        callBackThread();
        return new MyBinder();
    }

    /*
    onStartCommand is triggered by the BroadcastReceiver. This method tells the service what to do
    with the intent
     */
    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        Log.d("activityTracker", "MyBoundService onStartCommand");

        //We have received a new location.
        if(intent.hasExtra(LocationManager.KEY_LOCATION_CHANGED)) {
            Log.d("activityTracker", "MyBoundService Location Changed");
            //Toast.makeText(this, "MyBoundService Location Changed", Toast.LENGTH_SHORT).show();

            Bundle bundle = intent.getExtras();
            Location location = (Location) bundle.get(android.location.LocationManager.KEY_LOCATION_CHANGED);

            //put the new location into the db
            locationListener.onLocationChanged(location);

            //if the user is active we need to callBack to UI.
            if(isUserActive) {

                /*
                if the user pressed stop tracking we need to reset previous location as this will cause
                wrong distances, etc. to be sent back to the GUI. E.g. they moved location whilst it wasnt
                tracking
                 */
                if(resetPreviousLocation) {

                    //As we have reset the distance will be 0.
                    previousLocation = location;
                    resetPreviousLocation = false;
                }
                else {

                    //calculate distance between previous and new location.
                    this.todayDistance += previousLocation.distanceTo(location);
                }

                previousLocation = location;
                callBackThread();
            }
        }
        //The user has enabled/disabled the GPS.
        else if(intent.hasExtra(LocationManager.KEY_PROVIDER_ENABLED)){

            //determine whether it was switched on or off
            if(intent.getBooleanExtra(LocationManager.KEY_PROVIDER_ENABLED, false)) {
                Log.d("activityTracker", "MyBoundService GPS ON");
                //Toast.makeText(this, "MyBoundService GPS ON", Toast.LENGTH_SHORT).show();

                /*
                ...it was turned back on whilst the user was tracking so update the db to say we
                are tracking again.
                 */
                setIsTracking(1);

                if(isUserActive) {
                    calculateValues();
                    callBackThread();
                }
                else {
                    //Notification sent through the locationListener.
                    locationListener.onProviderEnabled(String.valueOf(isUserActive));
                }
            }
            else {
                Log.d("activityTracker", "MyBoundService GPS OFF");
                //Toast.makeText(this, "MyBoundService GPS OFF", Toast.LENGTH_SHORT).show();

                /*
                GPS was turned off whilst the user was tracking so update the db to say we
                are NOT tracking.
                 */
                setIsTracking(0);

                if(isUserActive) {
                    calculateValues();
                    callBackThread();
                }
                else {
                    setNoMovement();
                }

                //Notification sent through the locationListener.
                locationListener.onProviderDisabled(String.valueOf(isUserActive));
            }
        }
        //Otherwise if the alarmManager intent was received we need to schedule tasks (every half day)
        else if(intent.hasExtra(MyBroadcastReceiver.ALARM_MANAGER_RECEIVER)) {
            Log.d("activityTracker", "MyBoundService Alarm Manager Received");
            //Toast.makeText(this, "MyBoundService Alarm Manager Received", Toast.LENGTH_SHORT).show();

            scheduledTasks();
        }

        return Service.START_STICKY;
    }

    @Override
    public void onDestroy() {
        Log.d("activityTracker", "MyBoundService onDestroy");
        super.onDestroy();
    }

    @Override
    public void onRebind(Intent intent) {
        Log.d("activityTracker", "MyBoundService onRebind");
        super.onRebind(intent);
    }

    @Override
    public boolean onUnbind(Intent intent) {
        Log.d("activityTracker", "MyBoundService onUnbind");

        //MainActivity is onStop/onDestroy so therefore user not active so we unbind.
        setUserActive(false);

        return super.onUnbind(intent);
    }
}