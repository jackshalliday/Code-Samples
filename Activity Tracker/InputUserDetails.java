package com.example.jackhalliday.activitytracker;

import android.app.Activity;
import android.content.ContentValues;
import android.content.DialogInterface;
import android.content.Intent;
import android.graphics.Color;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

public class InputUserDetails extends AppCompatActivity {

    //Strings to set values of user sex.
    public static final String USER_MALE = "m";
    public static final String USER_FEMALE = "f";

    //Button colours
    public static final String ACTIVE_BUTTON = "#FFFF4081";
    public static final String INACTIVE_BUTTON = "#FFD6D7D7";

    //Title of activity displayed. Are we setting up account or updating details.
    public static final String ACTIVITY_TITLE_SETTINGS = "Settings";
    public static final String ACTIVITY_TITLE_SETUP = "Setup";

    //Constants for message of Alert to display to user on prompt
    public static final String ALERT_RESPONSE_POSITIVE_OK = "ok";
    public static final String ALERT_RESPONSE_NEGATIVE_CANCEL = "cancel";

    //IDs to deal with outcome of an alert presented to the user.
    private final int ALERT_TYPE_INPUT_ERROR = 1;
    private final int ALERT_TYPE_DELETE_DATA = 2;

    Toast toast;

    Button selectMale;
    Button selectFemale;
    Button deleteButton;
    EditText userHeightET;
    EditText userWeightET;
    TextView activityTitleTV;

    private int userHeight;
    private int userWeight;
    private String userSex;

    //Title to be displayed on Activity.
    private String activityTitle;

    //Are we updating user details or creating new user? Determines db actions and views to display.
    private Boolean isUpdate;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        Log.d("activityTracker", "InputUserDetails onCreate");

        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_input_user_details);

        toast = Toast.makeText(InputUserDetails.this, "", Toast.LENGTH_LONG);

        //By default set user sex to male, and activity in 'setup' mode, e.g. new user.
        this.userSex = USER_MALE;
        this.activityTitle = ACTIVITY_TITLE_SETUP;
        this.isUpdate = false;

        selectMale = (Button) findViewById(R.id.onClickMale);
        selectFemale = (Button) findViewById(R.id.onClickFemale);
        deleteButton = (Button) findViewById(R.id.onClickDelete);
        userHeightET = (EditText) findViewById(R.id.userHeight);
        userWeightET = (EditText) findViewById(R.id.userWeight);
        activityTitleTV = (TextView) findViewById(R.id.activityTitle);

        /*
        if intent received from MainActivity, aka we want to update user details as user already set up,
        then update the variables and views.
         */
        Intent intent = getIntent();
        if(intent.hasExtra("userHeight") && intent.hasExtra("userWeight") && intent.hasExtra("userSex")) {

            this.userSex = intent.getExtras().getString("userSex");
            this.userHeight = intent.getExtras().getInt("userHeight", 0);
            this.userWeight = intent.getExtras().getInt("userWeight", 0);
            this.activityTitle = ACTIVITY_TITLE_SETTINGS;
            this.isUpdate = true;

            userHeightET.setText(String.valueOf(this.userHeight));
            userWeightET.setText(String.valueOf(this.userWeight));
        }

        /*
        If, for example, orientation of screen changed get onSaveInstanceState stuff and update
        variables and views.
         */
        if(savedInstanceState != null) {

            this.userSex = savedInstanceState.getString("userSex");
            this.userHeight = savedInstanceState.getInt("userHeight");
            this.userWeight = savedInstanceState.getInt("userWeight");
            this.activityTitle = savedInstanceState.getString("activityTitle");
            this.isUpdate = savedInstanceState.getBoolean("isUpdate");

            userHeightET.setText(String.valueOf(this.userHeight));
            userWeightET.setText(String.valueOf(this.userWeight));
        }

        activityTitleTV.setText(this.activityTitle);

        //If the user is updating details then we present a delete button to remove all data from db.
        if(!isUpdate) {
            deleteButton.setVisibility(View.GONE);
        }

        //update views according to data.
        if (this.userSex.equals(USER_MALE)) {

            onClickMale(selectMale);
        }
        else {

            onClickFemale(selectFemale);
        }
    }

    public void onClickMale(View v) {

        this.userSex = USER_MALE;

        selectMale.setBackgroundColor(Color.parseColor(ACTIVE_BUTTON));
        selectFemale.setBackgroundColor(Color.parseColor(INACTIVE_BUTTON));
    }

    public void onClickFemale(View v) {

        this.userSex = USER_FEMALE;

        selectMale.setBackgroundColor(Color.parseColor(INACTIVE_BUTTON));
        selectFemale.setBackgroundColor(Color.parseColor(ACTIVE_BUTTON));
    }

    //This is the next/save button.
    public void onClickNext(View v) {

        //if any of the text views are blank alert user.
        if (userHeightET.getText().toString().equals("")) {

            String message = "Please enter your height";
            alertUser(message, ALERT_RESPONSE_POSITIVE_OK, null, ALERT_TYPE_INPUT_ERROR);
        }
        else if (userWeightET.getText().toString().equals("")) {

            String message = "Please enter your weight";
            alertUser(message, ALERT_RESPONSE_POSITIVE_OK, null, ALERT_TYPE_INPUT_ERROR);
        }
        else {

            //Otherwise get the values and prepare to perform db stuff.
            this.userHeight = Integer.valueOf(userHeightET.getText().toString());
            this.userWeight = Integer.valueOf(userWeightET.getText().toString());

            ContentValues newValues = new ContentValues();
            newValues.put(ContentProviderContract.COLUMN_URI_SEX, this.userSex);
            newValues.put(ContentProviderContract.COLUMN_URI_HEIGHT, this.userHeight);
            newValues.put(ContentProviderContract.COLUMN_URI_WEIGHT, this.userWeight);

            //if we are in update mode, then record already exists so we need to perform update action.
            if(isUpdate) {

                try {
                    getContentResolver().update(ContentProviderContract.TABLE_URI_USER, newValues, null, null);

                    Log.d("activityTracker", "InputUserDetails UPDATE User Details Success");
                    toast.setText("Details updated successfully");
                }
                catch(Exception e) {
                    Log.d("activityTracker", "InputUserDetails UPDATE User Details ERROR");
                    toast.setText("Problem updating details. Please try again");
                }
            }
            else {

                //otherwise we are creating a new user record and so db insert used instead.
                newValues.put(ContentProviderContract.COLUMN_URI_TRACKING, 0);

                try {
                    getContentResolver().insert(ContentProviderContract.TABLE_URI_USER, newValues);

                    Log.d("activityTracker", "InputUserDetails INSERT User Details Success");
                    toast.setText("Details added successfully");
                }
                catch (Exception e) {
                    Log.d("activityTracker", "InputUserDetails INSERT User Details ERROR");
                    toast.setText("Problem adding details. Please try again");
                }
            }

            //feedback to user.
            toast.show();
            closeActivity(false);
        }
    }

    //User may want to delete all of their data
    public void onClickDelete(View v) {

        String message = "All data will be lost. Are you sure?";
        String positiveButton = "delete data";
        alertUser(message, positiveButton, ALERT_RESPONSE_NEGATIVE_CANCEL, ALERT_TYPE_DELETE_DATA);
    }

    /*
    Close activity in accordance to users action. If they have performed a delete then send back boolean
    value so MainActivity can deal with the db delete. The reason why it is carried out in MainActivity
    is because service needs unbinding and tracking needs to be stopped before delete otherwise risk of
    new location coords being put into db during delete.
     */
    private void closeActivity(Boolean deleteData) {

        Bundle bundle = new Bundle();
        bundle.putBoolean("deleteData", deleteData);

        Intent result = new Intent();
        result.putExtras(bundle);

        setResult(Activity.RESULT_OK, result);
        finish();
    }

    //This is basically an Alert Builder to deal with all the prompts that the user might trigger
    private void alertUser(String message, String positiveButton, String negativeButton, final int alertType) {

        AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(this);
        alertDialogBuilder.setMessage(message);

        alertDialogBuilder.setPositiveButton(positiveButton, new DialogInterface.OnClickListener() {

            @Override
            public void onClick(DialogInterface arg0, int arg1) {

                switch(alertType) {

                    case ALERT_TYPE_INPUT_ERROR: //Stay in activity
                        break;
                    case ALERT_TYPE_DELETE_DATA: closeActivity(true);
                        break;
                    default: closeActivity(false);
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

    //E.g. deal with change in screen orientation...keep the state.
    @Override
    public void onSaveInstanceState(Bundle savedInstanceState) {
        Log.d("activityTracker", "InputUserDetails onSaveInstanceState");

        savedInstanceState.putString("userSex", this.userSex);
        savedInstanceState.putInt("userHeight", this.userHeight);
        savedInstanceState.putInt("userWeight", this.userWeight);
        savedInstanceState.putString("activityTitle", this.activityTitle);
        savedInstanceState.putBoolean("isUpdate", this.isUpdate);

        super.onSaveInstanceState(savedInstanceState);
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        Log.d("activityTracker", "InputUserDetails onDestroy");
    }

    @Override
    protected void onPause() {
        super.onPause();
        Log.d("activityTracker", "InputUserDetails onPause");
    }

    @Override
    protected void onResume() {
        super.onResume();
        Log.d("activityTracker", "InputUserDetails onResume");
    }

    @Override
    protected void onStart() {
        super.onStart();
        Log.d("activityTracker", "InputUserDetails onStart");
    }

    @Override
    protected void onStop() {
        super.onStop();
        Log.d("activityTracker", "InputUserDetails onStop");
    }
}