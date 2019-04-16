package com.example.jackhalliday.activitytracker;

import android.content.Context;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.util.Log;

/**
 * Created by jackhalliday on 19/12/2016.
 */

public class DBHelper extends SQLiteOpenHelper {

    public DBHelper(Context context, String dbName, int dbVersion) {
        super(context, dbName, null, dbVersion);
    }

    //Create the table and starting rows. Only called again if db version upgraded.
    @Override
    public void onCreate(SQLiteDatabase db) {
        Log.d("activityTracker", "DBHelper onCreate");

        db.execSQL("CREATE TABLE user (" + "_id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "userHeight INTEGER, " + "userWeight INTEGER, " + "userSex TEXT, " + "isTracking INTEGER" + ");");

        db.execSQL("CREATE TABLE movement (" + "_id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "date DATETIME DEFAULT CURRENT_DATE, " + "latitude TEXT, " + "longitude TEXT" + ");");

        db.execSQL("CREATE TABLE results (" + "_id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "date DATETIME DEFAULT CURRENT_DATE, " + "distance NUMERIC, " + "steps INTEGER," + "calories INTEGER" + ");");

        //fake data for 'all time' totals in MainActivity.
        db.execSQL("INSERT INTO results (date, distance, steps, calories) VALUES ('Jan 13, 2017', '3232', '3366', '243');");
        db.execSQL("INSERT INTO results (date, distance, steps, calories) VALUES ('Jan 14, 2017', '2163', '2722', '159');");
        db.execSQL("INSERT INTO results (date, distance, steps, calories) VALUES ('Jan 15, 2017', '1245', '1366', '102');");
    }

    //Called if db version upgraded.
    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        Log.d("activityTracker", "DBHelper onUpgrade");

        db.execSQL("DROP TABLE IF EXISTS user;");
        db.execSQL("DROP TABLE IF EXISTS movement;");
        db.execSQL("DROP TABLE IF EXISTS results;");
        onCreate(db);
    }
}

