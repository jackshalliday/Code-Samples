package com.example.jackhalliday.activitytracker;

import android.content.ContentProvider;
import android.content.ContentUris;
import android.content.ContentValues;
import android.content.UriMatcher;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.net.Uri;
import android.util.Log;

/**
 * Created by jackhalliday on 19/12/2016.
 */

public class MyContentProvider extends ContentProvider {

    private DBHelper dbHelper;
    private static final UriMatcher uriMatcher;

    static {
        //Add URI cases to perform matches when querying DB
        uriMatcher = new UriMatcher(UriMatcher.NO_MATCH);
        uriMatcher.addURI(ContentProviderContract.AUTHORITY, "user", 1);
        uriMatcher.addURI(ContentProviderContract.AUTHORITY, "user/#", 2);
        uriMatcher.addURI(ContentProviderContract.AUTHORITY, "movement", 3);
        uriMatcher.addURI(ContentProviderContract.AUTHORITY, "movement/#", 4);
        uriMatcher.addURI(ContentProviderContract.AUTHORITY, "results", 5);
        uriMatcher.addURI(ContentProviderContract.AUTHORITY, "results/#", 6);
    }

    @Override
    public boolean onCreate() {
        Log.d("activityTracker", "ContentProvider onCreate");

        //Initialise DB with version number.
        this.dbHelper = new DBHelper(this.getContext(), "activityTracker", 28);
        return true;
    }

    @Override
    public String getType(Uri uri) {
        Log.d("activityTracker", "ContentProvider getType");

        if (uri.getLastPathSegment() == null) {
            return "vnd.android.cursor.dir/MyContentProvider.data.text";
        } else {
            return "vnd.android.cursor.item/MyContentProvider.data.text";
        }
    }

    @Override
    public Uri insert(Uri uri, ContentValues values) {
        Log.d("activityTracker", "ContentProvider insert");

        //Get database and table we are dealing with
        SQLiteDatabase db = dbHelper.getWritableDatabase();
        String tableName = uri.getLastPathSegment();

        //Insert the new values into the table.
        long id = db.insert(tableName, null, values);
        db.close();

        //New URI with path to the new row.
        Uri newURI = ContentUris.withAppendedId(uri, id);
        getContext().getContentResolver().notifyChange(newURI, null);

        Log.d("activityTracker", "ContentProvider Inserted Record: " + newURI.getLastPathSegment());
        return newURI;
    }

    @Override
    public Cursor query(Uri uri, String[] projection, String selection, String[] selectionArgs, String sortOrder) {
        Log.d("activityTracker", "ContentProvider query");

        //Get DB we are dealing with
        SQLiteDatabase db = dbHelper.getWritableDatabase();
        String query;

        //Queries to perform.
        switch (uriMatcher.match(uri)) {
            case 1:
                return db.query("user", projection, selection, selectionArgs, null, null, sortOrder);
            case 2:
                query = "SELECT _id, userHeight, userWeight FROM user WHERE _id = " + selection;
                return db.rawQuery(query, selectionArgs);
            case 3:
                query = "SELECT _id, latitude, longitude FROM movement WHERE date = '" + selection + "'";
                return db.rawQuery(query, selectionArgs);
            case 5:
                query = "SELECT _id, distance, steps, calories FROM results" + selection;
                return db.rawQuery(query, selectionArgs);
            default:
                return null;
        }
    }

    @Override
    public int update(Uri uri, ContentValues values, String selection, String[] selectionArgs) {
        Log.d("activityTracker", "ContentProvider update");

        //Get DB and table we are dealing with.
        SQLiteDatabase db = dbHelper.getWritableDatabase();
        String tableName;

        //Where clause to update the row according to the parameters
        String whereClause;

        switch (uriMatcher.match(uri)) {
            case 1:
                tableName = ContentProviderContract.TABLE_URI_USER.getLastPathSegment();
                whereClause = selection;
                break;
            case 5:
                tableName = ContentProviderContract.TABLE_URI_RESULTS.getLastPathSegment();
                whereClause = "date = '" + selection + "'";
                break;
            default:
                tableName = "";
                whereClause = "";
                break;
        }

        //perform update
        long id = db.update(tableName, values, whereClause, selectionArgs);
        db.close();

        Log.d("activityTracker", "ContentProvider Updated Record");
        return 1;
    }

    @Override
    public int delete(Uri uri, String selection, String[] selectionArgs) {
        Log.d("activityTracker", "ContentProvider delete");

        //Get DB and table we are dealing with
        SQLiteDatabase db = dbHelper.getWritableDatabase();

        //Perform delete
        long id = db.delete(uri.getLastPathSegment(), selection, selectionArgs);
        db.close();

        System.out.println("DELETED RECORDS");
        return 1;
    }
}

