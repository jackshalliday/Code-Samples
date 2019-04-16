package com.example.jackhalliday.activitytracker;

import android.net.Uri;

/**
 * Created by jackhalliday on 19/12/2016.
 */

public class ContentProviderContract {

    public static final String AUTHORITY = "com.example.jackhalliday.activitytracker.MyContentProvider";

    //Tables
    public static final Uri TABLE_URI_USER = Uri.parse("content://"+AUTHORITY+"/user");
    public static final Uri TABLE_URI_MOVEMENT = Uri.parse("content://"+AUTHORITY+"/movement");
    public static final Uri TABLE_URI_RESULTS = Uri.parse("content://"+AUTHORITY+"/results");

    //Columns
    public static final String COLUMN_URI_ID = "_id";

    public static final String COLUMN_URI_HEIGHT = "userHeight";
    public static final String COLUMN_URI_WEIGHT = "userWeight";
    public static final String COLUMN_URI_SEX = "userSex";
    public static final String COLUMN_URI_TRACKING = "isTracking";

    public static final String COLUMN_URI_DATE = "date";
    public static final String COLUMN_URI_LAT = "latitude";
    public static final String COLUMN_URI_LONG = "longitude";

    public static final String COLUMN_URI_DISTANCE = "distance";
    public static final String COLUMN_URI_STEPS = "steps";
    public static final String COLUMN_URI_CALORIES = "calories";
}

